<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ServiceLog;
use App\Models\Vehicle;
use App\Models\VehicleBooking;
use Carbon\Carbon;
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

        // Dropdown Vehicles: Not in permanent maintenance
        $availableVehicles = Vehicle::where('status', '!=', 'maintenance')->with('location')->orderBy('name')->get();

        return view('monitoring.service_logs.index', compact('availableVehicles', 'totalCost', 'totalServices', 'inMaintenanceCount'));
    }

    /**
     * Get dynamic options and stats for service logs with date-range conflict check (AJAX).
     */
    public function options(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $excludeServiceId = $request->input('exclude_id');

        $vehiclesQuery = Vehicle::with('location')->orderBy('name');

        if ($startDate && $endDate) {
            // Find vehicles with overlapping active bookings
            $bookedVehicleIds = VehicleBooking::whereIn('status', ['pending', 'approved', 'in_progress'])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<', $endDate)
                      ->where('end_date', '>', $startDate);
                })
                ->pluck('vehicle_id')
                ->toArray();

            // Find vehicles with overlapping active services
            $serviceQuery = ServiceLog::where('status', 'in_progress');
            if ($excludeServiceId) {
                $serviceQuery->where('id', '!=', $excludeServiceId);
            }
            $servicedVehicleIds = $serviceQuery
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->where(function ($sub) use ($startDate, $endDate) {
                        $sub->whereNotNull('start_date')
                            ->whereNotNull('end_date')
                            ->where('start_date', '<', $endDate)
                            ->where('end_date', '>', $startDate);
                    })->orWhere(function ($sub) use ($startDate, $endDate) {
                        $sub->whereNull('start_date')
                            ->whereBetween('service_date', [
                                Carbon::parse($startDate)->toDateString(),
                                Carbon::parse($endDate)->toDateString()
                            ]);
                    });
                })
                ->pluck('vehicle_id')
                ->toArray();

            $unavailableVehicleIds = array_unique(array_merge($bookedVehicleIds, $servicedVehicleIds));
            $vehiclesQuery->whereNotIn('id', $unavailableVehicleIds);
        }

        return response()->json([
            'available_vehicles' => $vehiclesQuery->get(),
            'all_vehicles' => Vehicle::with('location')->orderBy('name')->get(),
            'stats' => [
                'total_cost' => (float) ServiceLog::sum('cost'),
                'total_services' => ServiceLog::count(),
                'in_maintenance_count' => ServiceLog::where('status', 'in_progress')->count(),
            ]
        ]);
    }

    /**
     * Store a newly created service log with date-range conflict validation.
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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'cost' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'vehicle_id.required' => 'Pilih kendaraan operasional.',
            'service_type.required' => 'Jenis servis wajib diisi.',
            'start_date.required' => 'Tanggal & waktu mulai servis wajib diisi.',
            'end_date.required' => 'Tanggal & waktu selesai servis wajib diisi.',
            'end_date.after' => 'Waktu selesai servis harus setelah waktu mulai servis.',
            'cost.required' => 'Biaya servis wajib diisi.',
        ]);

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);

        // 1. Conflict Check: Service vs active Vehicle Bookings
        $bookingConflict = VehicleBooking::where('vehicle_id', $validated['vehicle_id'])
            ->whereIn('status', ['pending', 'approved', 'in_progress'])
            ->where(function ($q) use ($validated) {
                $q->where('start_date', '<', $validated['end_date'])
                  ->where('end_date', '>', $validated['start_date']);
            })->first();

        if ($bookingConflict) {
            $bStart = Carbon::parse($bookingConflict->start_date)->format('d/m/Y H:i');
            $bEnd = Carbon::parse($bookingConflict->end_date)->format('d/m/Y H:i');
            return response()->json([
                'success' => false,
                'message' => 'Jadwal Bentrok! Kendaraan "' . $vehicle->name . '" sudah memiliki jadwal pemesanan aktif (' . $bookingConflict->booking_code . ') pada rentang waktu ' . $bStart . ' s/d ' . $bEnd . '.',
            ], 422);
        }

        // 2. Conflict Check: Service vs other active Service Logs
        $serviceConflict = ServiceLog::where('vehicle_id', $validated['vehicle_id'])
            ->where('status', 'in_progress')
            ->where(function ($q) use ($validated) {
                $q->where(function ($sub) use ($validated) {
                    $sub->whereNotNull('start_date')
                        ->whereNotNull('end_date')
                        ->where('start_date', '<', $validated['end_date'])
                        ->where('end_date', '>', $validated['start_date']);
                })->orWhere(function ($sub) use ($validated) {
                    $sub->whereNull('start_date')
                        ->whereBetween('service_date', [
                            Carbon::parse($validated['start_date'])->toDateString(),
                            Carbon::parse($validated['end_date'])->toDateString()
                        ]);
                });
            })->first();

        if ($serviceConflict) {
            $sStart = $serviceConflict->start_date ? Carbon::parse($serviceConflict->start_date)->format('d/m/Y H:i') : Carbon::parse($serviceConflict->service_date)->format('d/m/Y');
            $sEnd = $serviceConflict->end_date ? Carbon::parse($serviceConflict->end_date)->format('d/m/Y H:i') : '-';
            return response()->json([
                'success' => false,
                'message' => 'Jadwal Bentrok! Kendaraan "' . $vehicle->name . '" sudah dijadwalkan dalam masa servis lain (' . $serviceConflict->service_type . ') pada rentang waktu ' . $sStart . ($sEnd !== '-' ? ' s/d ' . $sEnd : '') . '.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $serviceLog = ServiceLog::create([
                'vehicle_id' => $validated['vehicle_id'],
                'service_type' => $validated['service_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'service_date' => Carbon::parse($validated['start_date'])->toDateString(),
                'cost' => (float) $validated['cost'],
                'status' => 'in_progress',
                'description' => $validated['description'] ?? null,
            ]);

            // If the service is actively happening right now, update vehicle status to 'maintenance'
            $now = Carbon::now();
            $sStartDt = Carbon::parse($validated['start_date']);
            $sEndDt = Carbon::parse($validated['end_date']);
            if ($sStartDt->lte($now) && $sEndDt->gte($now)) {
                $vehicle->update(['status' => 'maintenance']);
            }

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'INPUT_SERVICE_LOG',
                'entity_type' => 'ServiceLog',
                'entity_id' => $serviceLog->id,
                'description' => 'Mencatat jadwal servis ' . $validated['service_type'] . ' untuk armada ' . $vehicle->name . ' (' . Carbon::parse($validated['start_date'])->format('d/m/Y H:i') . ' s/d ' . Carbon::parse($validated['end_date'])->format('d/m/Y H:i') . ')',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal servis kendaraan berhasil disimpan.',
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

        if ($serviceLog->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya transaksi servis yang sedang berjalan (In Progress) yang dapat diselesaikan.',
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
                'description' => 'Membatalkan jadwal servis kendaraan ' . ($serviceLog->vehicle?->name ?? '') . '. Status armada dikembalikan ke AVAILABLE.',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal servis kendaraan ' . ($serviceLog->vehicle?->name ?? '') . ' berhasil DIBATALKAN.',
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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'cost' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'vehicle_id.required' => 'Pilih kendaraan operasional.',
            'service_type.required' => 'Jenis servis wajib diisi.',
            'start_date.required' => 'Tanggal & waktu mulai servis wajib diisi.',
            'end_date.required' => 'Tanggal & waktu selesai servis wajib diisi.',
            'end_date.after' => 'Waktu selesai servis harus setelah waktu mulai servis.',
            'cost.required' => 'Biaya servis wajib diisi.',
        ]);

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);

        // Check Conflict with Booking
        $bookingConflict = VehicleBooking::where('vehicle_id', $validated['vehicle_id'])
            ->whereIn('status', ['pending', 'approved', 'in_progress'])
            ->where(function ($q) use ($validated) {
                $q->where('start_date', '<', $validated['end_date'])
                  ->where('end_date', '>', $validated['start_date']);
            })->first();

        if ($bookingConflict) {
            $bStart = Carbon::parse($bookingConflict->start_date)->format('d/m/Y H:i');
            $bEnd = Carbon::parse($bookingConflict->end_date)->format('d/m/Y H:i');
            return response()->json([
                'success' => false,
                'message' => 'Jadwal Bentrok! Kendaraan "' . $vehicle->name . '" memiliki jadwal pemesanan aktif (' . $bookingConflict->booking_code . ') pada ' . $bStart . ' s/d ' . $bEnd . '.',
            ], 422);
        }

        // Check Conflict with other Service Logs
        $serviceConflict = ServiceLog::where('vehicle_id', $validated['vehicle_id'])
            ->where('id', '!=', $serviceLog->id)
            ->where('status', 'in_progress')
            ->where(function ($q) use ($validated) {
                $q->where(function ($sub) use ($validated) {
                    $sub->whereNotNull('start_date')
                        ->whereNotNull('end_date')
                        ->where('start_date', '<', $validated['end_date'])
                        ->where('end_date', '>', $validated['start_date']);
                })->orWhere(function ($sub) use ($validated) {
                    $sub->whereNull('start_date')
                        ->whereBetween('service_date', [
                            Carbon::parse($validated['start_date'])->toDateString(),
                            Carbon::parse($validated['end_date'])->toDateString()
                        ]);
                });
            })->first();

        if ($serviceConflict) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal Bentrok! Kendaraan "' . $vehicle->name . '" sudah memiliki jadwal servis lain yang sedang berjalan pada rentang waktu yang dipilih.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $serviceLog->update([
                'vehicle_id' => $validated['vehicle_id'],
                'service_type' => $validated['service_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'service_date' => Carbon::parse($validated['start_date'])->toDateString(),
                'cost' => (float) $validated['cost'],
                'description' => $validated['description'] ?? null,
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'UPDATE_SERVICE_LOG',
                'entity_type' => 'ServiceLog',
                'entity_id' => $serviceLog->id,
                'description' => 'Memperbarui jadwal servis (ID ' . $serviceLog->id . ')',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data jadwal servis berhasil diperbarui.',
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
