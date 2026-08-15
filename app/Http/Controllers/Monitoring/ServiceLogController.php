<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ServiceLog;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class ServiceLogController extends Controller
{
    /**
     * Display a listing of service logs (Supports Yajra DataTables Server-Side Processing).
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ServiceLog::with('vehicle.location')->select('service_logs.*');

            return DataTables::of($query)
                ->addIndexColumn()
                ->filterColumn('vehicle.name', function ($query, $keyword) {
                    $query->whereHas('vehicle', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                          ->orWhere('license_plate', 'like', "%{$keyword}%");
                    });
                })
                ->make(true);
        }

        // Summary Stats
        $totalCost = ServiceLog::sum('cost');
        $totalServices = ServiceLog::count();
        $inMaintenanceCount = ServiceLog::where('status', 'in_progress')->count();

        // Dropdown Vehicles: ONLY AVAILABLE VEHICLES FOR INPUT SERVICE!
        $availableVehicles = Vehicle::where('status', 'available')->with('location')->orderBy('name')->get();

        return view('monitoring.service_logs.index', compact('availableVehicles', 'totalCost', 'totalServices', 'inMaintenanceCount'));
    }

    /**
     * Get dynamic options and stats for service logs (AJAX).
     */
    public function options()
    {
        return response()->json([
            'available_vehicles' => Vehicle::where('status', 'available')->with('location')->orderBy('name')->get(),
            'all_vehicles' => Vehicle::with('location')->orderBy('name')->get(),
            'stats' => [
                'total_cost' => (float) ServiceLog::sum('cost'),
                'total_services' => ServiceLog::count(),
                'in_maintenance_count' => ServiceLog::where('status', 'in_progress')->count(),
            ]
        ]);
    }

    /**
     * Store a newly created service log.
     */
    public function store(Request $request)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat mencatat servis kendaraan.',
            ], 403);
        }

        $validated = $request->validate([
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')->whereNull('deleted_at')],
            'service_type' => ['required', 'string', 'max:100'],
            'service_date' => ['required', 'date'],
            'cost' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'vehicle_id.required' => 'Pilih kendaraan yang tersedia.',
            'service_type.required' => 'Jenis servis wajib diisi.',
            'service_date.required' => 'Tanggal servis wajib diisi.',
            'cost.required' => 'Biaya servis wajib diisi.',
        ]);

        DB::beginTransaction();
        try {
            // Set status = 'in_progress' in service_logs table
            $serviceLog = ServiceLog::create([
                'vehicle_id' => $validated['vehicle_id'],
                'service_type' => $validated['service_type'],
                'service_date' => $validated['service_date'],
                'cost' => (float) $validated['cost'],
                'status' => 'in_progress',
                'description' => $validated['description'] ?? null,
            ]);

            // AUTOMATICALLY update vehicle status to 'maintenance'
            $vehicle = Vehicle::find($validated['vehicle_id']);
            if ($vehicle) {
                $vehicle->update(['status' => 'maintenance']);
            }

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'INPUT_SERVICE_LOG',
                'entity_type' => 'ServiceLog',
                'entity_id' => $serviceLog->id,
                'description' => 'Mencatat servis ' . $validated['service_type'] . ' untuk kendaraan ' . ($vehicle?->name ?? 'ID ' . $validated['vehicle_id']) . '. Status servis = in_progress & armada diubah ke MAINTENANCE.',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pencatatan servis berhasil disimpan. Status servis menjadi Dalam Servis (in_progress) & armada otomatis diubah ke MAINTENANCE.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data servis: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete the specified vehicle service (Admin Only) -> Updates service_log status to 'completed' & restores vehicle status to 'available'.
     */
    public function complete(Request $request, $id)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat menyelesaikan servis kendaraan.',
            ], 403);
        }

        $serviceLog = ServiceLog::with('vehicle')->findOrFail($id);

        if ($serviceLog->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Catatan servis ini sudah berstatus SELESAI.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Update Service Log status to 'completed'
            $serviceLog->update(['status' => 'completed']);

            // 2. Restore Vehicle status to 'available' if currently in maintenance
            if ($serviceLog->vehicle && $serviceLog->vehicle->status === 'maintenance') {
                $serviceLog->vehicle->update(['status' => 'available']);
            }

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'COMPLETE_SERVICE_LOG',
                'entity_type' => 'ServiceLog',
                'entity_id' => $serviceLog->id,
                'description' => 'Selesaikan servis kendaraan ' . ($serviceLog->vehicle?->name ?? '') . '. Status servis diubah ke COMPLETED & armada dikembalikan ke AVAILABLE.',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Servis kendaraan ' . ($serviceLog->vehicle?->name ?? '') . ' berhasil DISELESAIKAN. Status armada telah dikembalikan ke TERSEDIA.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyelesaikan servis kendaraan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel the specified vehicle service (Admin Only) -> Updates service_log status to 'cancelled' & restores vehicle status to 'available'.
     */
    public function cancel(Request $request, $id)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat membatalkan servis kendaraan.',
            ], 403);
        }

        $serviceLog = ServiceLog::with('vehicle')->findOrFail($id);

        if ($serviceLog->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Servis yang sudah selesai atau dibatalkan tidak dapat dibatalkan lagi.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Update Service Log status to 'cancelled'
            $serviceLog->update(['status' => 'cancelled']);

            // 2. Restore Vehicle status back to 'available' if currently in maintenance
            if ($serviceLog->vehicle && $serviceLog->vehicle->status === 'maintenance') {
                $serviceLog->vehicle->update(['status' => 'available']);
            }

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'CANCEL_SERVICE_LOG',
                'entity_type' => 'ServiceLog',
                'entity_id' => $serviceLog->id,
                'description' => 'Membatalkan transaksi servis kendaraan ' . ($serviceLog->vehicle?->name ?? '') . '. Status armada dikembalikan ke AVAILABLE.',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pencatatan servis kendaraan ' . ($serviceLog->vehicle?->name ?? '') . ' berhasil DIBATALKAN. Status armada telah dikembalikan ke TERSEDIA.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan servis kendaraan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified service log.
     */
    public function show($id)
    {
        $serviceLog = ServiceLog::with('vehicle.location')->findOrFail($id);
        return response()->json($serviceLog);
    }

    /**
     * Update the specified service log.
     */
    public function update(Request $request, ServiceLog $serviceLog)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.',
            ], 403);
        }

        if ($serviceLog->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Servis yang sudah selesai atau dibatalkan tidak dapat diedit lagi.',
            ], 422);
        }

        $validated = $request->validate([
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')->whereNull('deleted_at')],
            'service_type' => ['required', 'string', 'max:100'],
            'service_date' => ['required', 'date'],
            'cost' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'vehicle_id.required' => 'Pilih kendaraan operasional.',
            'service_type.required' => 'Jenis servis wajib diisi.',
            'service_date.required' => 'Tanggal servis wajib diisi.',
            'cost.required' => 'Biaya servis wajib diisi.',
        ]);

        DB::beginTransaction();
        try {
            $serviceLog->update([
                'vehicle_id' => $validated['vehicle_id'],
                'service_type' => $validated['service_type'],
                'service_date' => $validated['service_date'],
                'cost' => (float) $validated['cost'],
                'description' => $validated['description'] ?? null,
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'UPDATE_SERVICE_LOG',
                'entity_type' => 'ServiceLog',
                'entity_id' => $serviceLog->id,
                'description' => 'Memperbarui riwayat servis (ID ' . $serviceLog->id . ')',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data riwayat servis berhasil diperbarui.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data servis: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified service log (Replaced with cancel).
     */
    public function destroy(Request $request, $id)
    {
        return $this->cancel($request, $id);
    }
}
