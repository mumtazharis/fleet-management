<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\FuelLog;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class FuelLogController extends Controller
{
    /**
     * Display a listing of fuel logs (Supports Yajra DataTables Server-Side Processing).
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = FuelLog::with(['vehicle', 'creator'])->select('fuel_logs.*');

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

        // Summary Stats for Top Panel
        $totalCost = FuelLog::sum('total_cost');
        $totalLiters = FuelLog::sum('fuel_amount');
        $totalTransactions = FuelLog::count();

        // Vehicles for modal select dropdown (active vehicles)
        $vehicles = Vehicle::orderBy('name')->get();

        return view('monitoring.fuel_logs.index', compact('vehicles', 'totalCost', 'totalLiters', 'totalTransactions'));
    }

    /**
     * Get dynamic options and stats for fuel logs (AJAX).
     */
    public function options()
    {
        return response()->json([
            'vehicles' => Vehicle::with('location')->orderBy('name')->get(),
            'stats' => [
                'total_cost' => (float) FuelLog::sum('total_cost'),
                'total_liters' => (float) FuelLog::sum('fuel_amount'),
                'total_transactions' => FuelLog::count(),
            ]
        ]);
    }

    /**
     * Store a newly created fuel log.
     */
    public function store(Request $request)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat mencatat pengisian BBM.',
            ], 403);
        }

        $validated = $request->validate([
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')->whereNull('deleted_at')],
            'date' => ['required', 'date'],
            'fuel_amount' => ['required', 'numeric', 'min:0.01'],
            'fuel_price' => ['required', 'numeric', 'min:0'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'odometer_reading' => ['required', 'integer', 'min:0'],
        ], [
            'vehicle_id.required' => 'Pilih kendaraan operasional.',
            'date.required' => 'Tanggal pengisian wajib diisi.',
            'fuel_amount.required' => 'Jumlah liter BBM wajib diisi.',
            'fuel_price.required' => 'Harga per liter BBM wajib diisi.',
            'odometer_reading.required' => 'Angka odometer (KM) saat pengisian BBM wajib diisi.',
            'odometer_reading.integer' => 'Angka odometer (KM) harus berupa angka bulat positif.',
        ]);

        $fuelAmount = (float) $validated['fuel_amount'];
        $fuelPrice = (float) $validated['fuel_price'];
        $totalCost = isset($validated['total_cost']) && $validated['total_cost'] > 0
            ? (float) $validated['total_cost']
            : ($fuelAmount * $fuelPrice);

        DB::beginTransaction();
        try {
            $fuelLog = FuelLog::create([
                'vehicle_id' => $validated['vehicle_id'],
                'date' => $validated['date'],
                'fuel_amount' => $fuelAmount,
                'fuel_price' => $fuelPrice,
                'total_cost' => $totalCost,
                'odometer_reading' => $validated['odometer_reading'] ?? null,
                'created_by' => Auth::id(),
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'INPUT_FUEL_LOG',
                'entity_type' => 'FuelLog',
                'entity_id' => $fuelLog->id,
                'description' => 'Mencatat pengisian BBM ' . number_format($fuelAmount, 2) . ' Liter untuk kendaraan ID ' . $validated['vehicle_id'],
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pencatatan konsumsi BBM berhasil disimpan.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan pencatatan BBM: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified fuel log.
     */
    public function show($id)
    {
        $fuelLog = FuelLog::with(['vehicle.location', 'creator'])->findOrFail($id);
        return response()->json($fuelLog);
    }

    /**
     * Update the specified fuel log.
     */
    public function update(Request $request, FuelLog $fuelLog)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $validated = $request->validate([
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')->whereNull('deleted_at')],
            'date' => ['required', 'date'],
            'fuel_amount' => ['required', 'numeric', 'min:0.01'],
            'fuel_price' => ['required', 'numeric', 'min:0'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'odometer_reading' => ['required', 'integer', 'min:0'],
        ], [
            'vehicle_id.required' => 'Pilih kendaraan operasional.',
            'date.required' => 'Tanggal pengisian wajib diisi.',
            'fuel_amount.required' => 'Jumlah liter BBM wajib diisi.',
            'fuel_price.required' => 'Harga per liter BBM wajib diisi.',
            'odometer_reading.required' => 'Angka odometer (KM) saat pengisian BBM wajib diisi.',
            'odometer_reading.integer' => 'Angka odometer (KM) harus berupa angka bulat positif.',
        ]);

        $fuelAmount = (float) $validated['fuel_amount'];
        $fuelPrice = (float) $validated['fuel_price'];
        $totalCost = isset($validated['total_cost']) && $validated['total_cost'] > 0
            ? (float) $validated['total_cost']
            : ($fuelAmount * $fuelPrice);

        DB::beginTransaction();
        try {
            $fuelLog->update([
                'vehicle_id' => $validated['vehicle_id'],
                'date' => $validated['date'],
                'fuel_amount' => $fuelAmount,
                'fuel_price' => $fuelPrice,
                'total_cost' => $totalCost,
                'odometer_reading' => $validated['odometer_reading'] ?? null,
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'UPDATE_FUEL_LOG',
                'entity_type' => 'FuelLog',
                'entity_id' => $fuelLog->id,
                'description' => 'Memperbarui data pengisian BBM (ID ' . $fuelLog->id . ')',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pencatatan konsumsi BBM berhasil diperbarui.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data BBM: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified fuel log.
     */
    public function destroy(Request $request, $id)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat menghapus pencatatan BBM.',
            ], 403);
        }

        $fuelLog = FuelLog::findOrFail($id);

        DB::beginTransaction();
        try {
            $fuelLog->delete();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'DELETE_FUEL_LOG',
                'entity_type' => 'FuelLog',
                'entity_id' => $id,
                'description' => 'Menghapus data pencatatan pengisian BBM (ID ' . $id . ')',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pencatatan konsumsi BBM berhasil dihapus.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pencatatan BBM.',
            ], 500);
        }
    }
}
