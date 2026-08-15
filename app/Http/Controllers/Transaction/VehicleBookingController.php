<?php

namespace App\Http\Controllers\Transaction;

use App\Exports\VehicleBookingsExport;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BookingApproval;
use App\Models\Driver;
use App\Models\Location;
use App\Models\ServiceLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBooking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class VehicleBookingController extends Controller
{
    /**
     * Display a listing of vehicle bookings (Supports Yajra DataTables Server-Side Processing).
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = VehicleBooking::withTrashed()->with([
                'user',
                'vehicle',
                'driver',
                'startLocation',
                'destinationLocation',
                'approvals.approver'
            ])->select('vehicle_bookings.*');

            return DataTables::of($query)
                ->addIndexColumn()
                ->filterColumn('vehicle.name', function ($query, $keyword) {
                    $query->whereHas('vehicle', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                          ->orWhere('license_plate', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('driver.name', function ($query, $keyword) {
                    $query->whereHas('driver', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('startLocation.name', function ($query, $keyword) {
                    $query->whereHas('startLocation', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->make(true);
        }

        // Available Vehicles Query: Not in permanent maintenance
        $availableVehicles = Vehicle::where('status', '!=', 'maintenance')
            ->with('location')
            ->orderBy('name')
            ->get();

        // Available Drivers Query
        $availableDrivers = Driver::orderBy('name')->get();

        // Locations
        $locations = Location::orderBy('name')->get();

        // Approvers Level 1: Users whose role level == 1 (Supervisor / Atasan L1)
        $approversL1 = User::with('role')
            ->whereHas('role', function ($q) {
                $q->where('level', 1)->orWhere('name', 'supervisor');
            })->get();

        // Approvers Level 2: Users whose role level == 2 (Manager / Atasan L2)
        $approversL2 = User::with('role')
            ->whereHas('role', function ($q) {
                $q->where('level', 2)->orWhere('name', 'manager');
            })->get();

        return view('transactions.bookings.index', compact('availableVehicles', 'availableDrivers', 'locations', 'approversL1', 'approversL2'));
    }

    /**
     * Get dynamic options for booking forms with date-range conflict filter (AJAX).
     */
    public function options(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $vehiclesQuery = Vehicle::where('status', '!=', 'maintenance')->with('location')->orderBy('name');
        $driversQuery = Driver::orderBy('name');

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
            $servicedVehicleIds = ServiceLog::where('status', 'in_progress')
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

            // Find drivers with overlapping active bookings
            $bookedDriverIds = VehicleBooking::whereIn('status', ['pending', 'approved', 'in_progress'])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<', $endDate)
                      ->where('end_date', '>', $startDate);
                })
                ->pluck('driver_id')
                ->toArray();

            $driversQuery->whereNotIn('id', $bookedDriverIds);
        }

        return response()->json([
            'available_vehicles' => $vehiclesQuery->get(),
            'available_drivers' => $driversQuery->get(),
            'locations' => Location::orderBy('name')->get(),
            'approvers_l1' => User::with('role')->whereHas('role', function ($q) {
                $q->where('level', 1)->orWhere('name', 'supervisor');
            })->get(),
            'approvers_l2' => User::with('role')->whereHas('role', function ($q) {
                $q->where('level', 2)->orWhere('name', 'manager');
            })->get(),
        ]);
    }

    /**
     * Store a newly created vehicle booking (Admin Only) with advanced date-range conflict validation.
     */
    public function store(Request $request)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat menginput pemesanan kendaraan.',
            ], 403);
        }

        $validated = $request->validate([
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')->whereNull('deleted_at')],
            'driver_id' => ['required', Rule::exists('drivers', 'id')->whereNull('deleted_at')],
            'start_location_id' => ['required'],
            'start_address' => ['nullable', 'string', 'max:255', 'required_if:start_location_id,other'],
            'destination_location_id' => ['required'],
            'destination_address' => ['nullable', 'string', 'max:255', 'required_if:destination_location_id,other'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'approver_1_id' => ['required', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'approver_2_id' => ['required', Rule::exists('users', 'id')->whereNull('deleted_at'), 'different:approver_1_id'],
            'purpose' => ['required', 'string'],
        ], [
            'vehicle_id.required' => 'Pilih kendaraan operasional.',
            'driver_id.required' => 'Pilih driver operasional.',
            'start_location_id.required' => 'Pilih lokasi penjemputan/asal.',
            'start_address.required_if' => 'Nama / alamat lokasi penjemputan lainnya wajib diisi.',
            'destination_location_id.required' => 'Pilih lokasi tujuan.',
            'destination_address.required_if' => 'Nama / alamat lokasi tujuan lainnya wajib diisi.',
            'start_date.required' => 'Tanggal & waktu mulai wajib diisi.',
            'end_date.required' => 'Tanggal & waktu selesai wajib diisi.',
            'end_date.after' => 'Tanggal & waktu selesai harus setelah tanggal mulai.',
            'approver_1_id.required' => 'Pilih Penyetuju Level 1 (Atasan L1 dengan Role Level 1 / SPV).',
            'approver_2_id.required' => 'Pilih Penyetuju Level 2 (Atasan L2 dengan Role Level 2 / Manager).',
            'approver_2_id.different' => 'Penyetuju Level 2 harus berbeda dari Penyetuju Level 1.',
            'purpose.required' => 'Keperluan pemakaian kendaraan wajib diisi.',
        ]);

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
        $driver = Driver::findOrFail($validated['driver_id']);

        // Check if vehicle is in maintenance
        if ($vehicle->status === 'maintenance') {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan "' . $vehicle->name . '" saat ini berstatus MAINTENANCE (Perawatan Mesin) dan tidak dapat dipesan.',
            ], 422);
        }

        // 1. Check Date Range Conflict: Vehicle vs other active Bookings
        $vehicleBookingConflict = VehicleBooking::where('vehicle_id', $validated['vehicle_id'])
            ->whereIn('status', ['pending', 'approved', 'in_progress'])
            ->where(function ($q) use ($validated) {
                $q->where('start_date', '<', $validated['end_date'])
                  ->where('end_date', '>', $validated['start_date']);
            })->first();

        if ($vehicleBookingConflict) {
            $cStart = Carbon::parse($vehicleBookingConflict->start_date)->format('d/m/Y H:i');
            $cEnd = Carbon::parse($vehicleBookingConflict->end_date)->format('d/m/Y H:i');
            return response()->json([
                'success' => false,
                'message' => 'Jadwal Bentrok! Kendaraan "' . $vehicle->name . '" sudah memiliki jadwal pemesanan (' . $vehicleBookingConflict->booking_code . ') pada rentang waktu ' . $cStart . ' s/d ' . $cEnd . '.',
            ], 422);
        }

        // 2. Check Date Range Conflict: Vehicle vs active Service Logs
        $vehicleServiceConflict = ServiceLog::where('vehicle_id', $validated['vehicle_id'])
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

        if ($vehicleServiceConflict) {
            $sStart = $vehicleServiceConflict->start_date ? Carbon::parse($vehicleServiceConflict->start_date)->format('d/m/Y H:i') : Carbon::parse($vehicleServiceConflict->service_date)->format('d/m/Y');
            $sEnd = $vehicleServiceConflict->end_date ? Carbon::parse($vehicleServiceConflict->end_date)->format('d/m/Y H:i') : '-';
            return response()->json([
                'success' => false,
                'message' => 'Jadwal Bentrok! Kendaraan "' . $vehicle->name . '" sedang dijadwalkan dalam masa servis (' . $vehicleServiceConflict->service_type . ') pada rentang waktu ' . $sStart . ($sEnd !== '-' ? ' s/d ' . $sEnd : '') . '.',
            ], 422);
        }

        // 3. Check Date Range Conflict: Driver vs other active Bookings
        $driverConflict = VehicleBooking::where('driver_id', $validated['driver_id'])
            ->whereIn('status', ['pending', 'approved', 'in_progress'])
            ->where(function ($q) use ($validated) {
                $q->where('start_date', '<', $validated['end_date'])
                  ->where('end_date', '>', $validated['start_date']);
            })->first();

        if ($driverConflict) {
            $dStart = Carbon::parse($driverConflict->start_date)->format('d/m/Y H:i');
            $dEnd = Carbon::parse($driverConflict->end_date)->format('d/m/Y H:i');
            return response()->json([
                'success' => false,
                'message' => 'Jadwal Bentrok! Driver "' . $driver->name . '" sudah ditugaskan pada pemesanan lain (' . $driverConflict->booking_code . ') pada rentang waktu ' . $dStart . ' s/d ' . $dEnd . '.',
            ], 422);
        }

        // Validate Location Exists if not 'other'
        $startLocationId = null;
        $startAddress = null;
        if ($validated['start_location_id'] === 'other') {
            $startAddress = $validated['start_address'];
        } else {
            $loc = Location::find($validated['start_location_id']);
            if (!$loc) {
                return response()->json(['success' => false, 'message' => 'Lokasi penjemputan tidak valid.'], 422);
            }
            $startLocationId = $loc->id;
        }

        $destinationLocationId = null;
        $destinationAddress = null;
        if ($validated['destination_location_id'] === 'other') {
            $destinationAddress = $validated['destination_address'];
        } else {
            $loc = Location::find($validated['destination_location_id']);
            if (!$loc) {
                return response()->json(['success' => false, 'message' => 'Lokasi tujuan tidak valid.'], 422);
            }
            $destinationLocationId = $loc->id;
        }

        // Validate Approver 1 Role Level (Level 1)
        $approver1 = User::with('role')->find($validated['approver_1_id']);
        if (!$approver1 || ($approver1->role?->level != 1 && $approver1->role?->name !== 'supervisor')) {
            return response()->json([
                'success' => false,
                'message' => 'Penyetuju Level 1 harus merupakan user dengan Role Level 1 (Supervisor/SPV).',
            ], 422);
        }

        // Validate Approver 2 Role Level (Level 2)
        $approver2 = User::with('role')->find($validated['approver_2_id']);
        if (!$approver2 || ($approver2->role?->level != 2 && $approver2->role?->name !== 'manager')) {
            return response()->json([
                'success' => false,
                'message' => 'Penyetuju Level 2 harus merupakan user dengan Role Level 2 (Manager Ops/Tambang).',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Generate unique booking code e.g. BOOK-20260816-001
            $count = VehicleBooking::withTrashed()->whereDate('created_at', now())->count() + 1;
            do {
                $bookingCode = 'BOOK-' . now()->format('Ymd') . '-' . str_pad($count++, 3, '0', STR_PAD_LEFT);
            } while (VehicleBooking::withTrashed()->where('booking_code', $bookingCode)->exists());

            $booking = VehicleBooking::create([
                'booking_code' => $bookingCode,
                'user_id' => Auth::id(),
                'vehicle_id' => $validated['vehicle_id'],
                'driver_id' => $validated['driver_id'],
                'start_location_id' => $startLocationId,
                'destination_location_id' => $destinationLocationId,
                'start_address' => $startAddress,
                'destination_address' => $destinationAddress,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'purpose' => $validated['purpose'],
                'status' => 'pending',
            ]);

            // Update real-time status if the booking is active right now
            $now = Carbon::now();
            $startDt = Carbon::parse($validated['start_date']);
            $endDt = Carbon::parse($validated['end_date']);
            if ($startDt->lte($now) && $endDt->gte($now)) {
                $vehicle->update(['status' => 'reserved']);
                $driver->update(['status' => 'reserved']);
            }

            // Create Level 1 Approval Record
            BookingApproval::create([
                'vehicle_booking_id' => $booking->id,
                'approver_id' => $validated['approver_1_id'],
                'approval_level' => 1,
                'status' => 'pending',
            ]);

            // Create Level 2 Approval Record
            BookingApproval::create([
                'vehicle_booking_id' => $booking->id,
                'approver_id' => $validated['approver_2_id'],
                'approval_level' => 2,
                'status' => 'pending',
            ]);

            // Log Activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'CREATE_BOOKING',
                'entity_type' => 'VehicleBooking',
                'entity_id' => $booking->id,
                'description' => 'Menginput pemesanan kendaraan ' . $booking->booking_code . ' untuk jadwal ' . Carbon::parse($validated['start_date'])->format('d/m/Y H:i') . ' s/d ' . Carbon::parse($validated['end_date'])->format('d/m/Y H:i'),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pemesanan kendaraan ' . $bookingCode . ' berhasil dibuat.',
                    'data' => $booking,
                ]);
            }

            return redirect()->route('bookings.index')->with('success', 'Pemesanan kendaraan berhasil dibuat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pemesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified booking details.
     */
    public function show($id)
    {
        $booking = VehicleBooking::withTrashed()->with([
            'user',
            'vehicle.rentalCompany',
            'driver',
            'startLocation.region',
            'destinationLocation.region',
            'approvals.approver.role'
        ])->findOrFail($id);

        return response()->json($booking);
    }

    /**
     * Cancel the specified booking (Admin Only) via Soft Delete & Status Reset.
     */
    public function destroy(Request $request, $id)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat membatalkan pemesanan kendaraan.',
            ], 403);
        }

        $booking = VehicleBooking::findOrFail($id);

        if ($booking->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Pemesanan yang telah ditolak tidak dapat dibatalkan.',
            ], 422);
        }

        if (in_array($booking->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pemesanan ini sudah selesai atau telah dibatalkan sebelumnya.',
            ], 422);
        }

        $bookingCode = $booking->booking_code;

        DB::beginTransaction();
        try {
            // Restore Vehicle status back to available if it was reserved/in_use
            if ($booking->vehicle && in_array($booking->vehicle->status, ['reserved', 'in_use'])) {
                $booking->vehicle->update(['status' => 'available']);
            }

            // Restore Driver status back to available if it was reserved/on_trip
            if ($booking->driver && in_array($booking->driver->status, ['reserved', 'on_trip'])) {
                $booking->driver->update(['status' => 'available']);
            }

            // Change status to cancelled and apply soft delete
            $booking->update(['status' => 'cancelled']);
            
            // Also cancel any pending approvals for this booking
            BookingApproval::where('vehicle_booking_id', $booking->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'cancelled',
                    'note' => 'Pemesanan dibatalkan oleh Administrator.',
                    'responded_at' => now(),
                ]);

            $booking->delete();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'CANCEL_BOOKING',
                'entity_type' => 'VehicleBooking',
                'entity_id' => $id,
                'description' => 'Membatalkan pemesanan kendaraan: ' . $bookingCode . ' (Jadwal reservasi dibatalkan)',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pemesanan kendaraan ' . $bookingCode . ' berhasil dibatalkan.',
                ]);
            }

            return redirect()->route('bookings.index')->with('success', 'Pemesanan kendaraan berhasil dibatalkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan pemesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete the specified booking (Admin Only) via status update to completed & releasing vehicle & driver.
     */
    public function complete(Request $request, $id)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat menyelesaikan pemesanan kendaraan.',
            ], 403);
        }

        $booking = VehicleBooking::findOrFail($id);

        if (in_array($booking->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pemesanan ini sudah selesai atau dibatalkan.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update booking status to completed
            $booking->update(['status' => 'completed']);

            // Restore Vehicle status back to available if it was reserved/in_use
            if ($booking->vehicle && in_array($booking->vehicle->status, ['reserved', 'in_use'])) {
                $booking->vehicle->update(['status' => 'available']);
            }

            // Restore Driver status back to available if it was reserved/on_trip
            if ($booking->driver && in_array($booking->driver->status, ['reserved', 'on_trip'])) {
                $booking->driver->update(['status' => 'available']);
            }

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'COMPLETE_BOOKING',
                'entity_type' => 'VehicleBooking',
                'entity_id' => $booking->id,
                'description' => 'Selesaikan pemesanan kendaraan ' . $booking->booking_code . '. Status armada & driver dikembalikan ke TERSEDIA.',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pemesanan kendaraan ' . $booking->booking_code . ' berhasil DISELESAIKAN. Status armada & driver telah dikembalikan ke TERSEDIA.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyelesaikan pemesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export all vehicle bookings to Excel spreadsheet.
     */
    public function export()
    {
        $fileName = 'data-pemesanan-kendaraan-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new VehicleBookingsExport, $fileName);
    }
}
