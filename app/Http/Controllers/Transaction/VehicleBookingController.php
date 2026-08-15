<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BookingApproval;
use App\Models\Driver;
use App\Models\Location;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

            $isAdmin = Auth::user()->role?->name === 'admin';

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('code_date', function ($b) {
                    $startDate = $b->start_date ? $b->start_date->format('d/m/Y H:i') : '-';
                    $endDate = $b->end_date ? $b->end_date->format('d/m/Y H:i') : '-';
                    return '
                        <strong class="text-primary font-monospace d-block">' . e($b->booking_code) . '</strong>
                        <small class="text-muted"><i class="bi bi-calendar-range me-1"></i>' . $startDate . ' - ' . $endDate . '</small>
                    ';
                })
                ->addColumn('vehicle_driver', function ($b) {
                    $vehicleName = $b->vehicle ? e($b->vehicle->name) . ' (' . e($b->vehicle->license_plate) . ')' : '-';
                    $driverName = $b->driver ? e($b->driver->name) : '-';
                    return '
                        <div class="lh-sm">
                            <strong class="d-block text-dark"><i class="bi bi-truck text-warning me-1"></i>' . $vehicleName . '</strong>
                            <small class="text-secondary"><i class="bi bi-person me-1"></i>Driver: ' . $driverName . '</small>
                        </div>
                    ';
                })
                ->addColumn('route', function ($b) {
                    $startLoc = $b->startLocation ? e($b->startLocation->name) : e($b->start_address ?? '-');
                    $destLoc = $b->destinationLocation ? e($b->destinationLocation->name) : e($b->destination_address ?? '-');
                    return '
                        <small class="d-block text-dark fw-semibold"><i class="bi bi-geo-alt-fill text-danger me-1"></i>' . $startLoc . '</small>
                        <small class="d-block text-secondary"><i class="bi bi-arrow-down me-1"></i>' . $destLoc . '</small>
                    ';
                })
                ->addColumn('status_badge', function ($b) {
                    if ($b->status === 'cancelled' || $b->trashed()) {
                        return '<span class="badge text-bg-secondary"><i class="bi bi-x-octagon me-1"></i> Dibatalkan</span>';
                    } elseif ($b->status === 'approved') {
                        return '<span class="badge text-bg-success"><i class="bi bi-check-all me-1"></i> Disetujui</span>';
                    } elseif ($b->status === 'rejected') {
                        return '<span class="badge text-bg-danger"><i class="bi bi-x-lg me-1"></i> Ditolak</span>';
                    } elseif ($b->status === 'completed') {
                        return '<span class="badge text-bg-info"><i class="bi bi-flag-fill me-1"></i> Selesai</span>';
                    } elseif ($b->status === 'in_progress') {
                        return '<span class="badge text-bg-primary"><i class="bi bi-truck me-1"></i> Berjalan</span>';
                    }
                    return '<span class="badge text-bg-warning text-dark"><i class="bi bi-clock me-1"></i> Menunggu Approval</span>';
                })
                ->addColumn('action', function ($b) use ($isAdmin) {
                    $btnDetail = '
                        <button type="button" class="btn btn-sm btn-outline-info btn-detail" data-id="' . $b->id . '" title="Detail Pemesanan">
                            <i class="bi bi-eye me-1"></i> Detail
                        </button>
                    ';

                    if (!$isAdmin || $b->status === 'cancelled' || $b->trashed()) {
                        return $btnDetail;
                    }

                    $btnComplete = '';
                    if (in_array($b->status, ['approved', 'in_progress'])) {
                        $btnComplete = '
                            <button type="button" class="btn btn-sm btn-success btn-complete ms-1" data-id="' . $b->id . '" data-code="' . e($b->booking_code) . '" title="Selesaikan Pemesanan">
                                <i class="bi bi-check2-circle me-1"></i> Selesai
                            </button>
                        ';
                    }

                    $btnCancel = '';
                    if ($b->status !== 'completed') {
                        $btnCancel = '
                            <button type="button" class="btn btn-sm btn-outline-warning btn-cancel ms-1" data-id="' . $b->id . '" data-code="' . e($b->booking_code) . '" title="Batalkan Pemesanan">
                                Batalkan
                            </button>
                        ';
                    }

                    return '<div class="btn-group btn-group-sm">' . $btnDetail . $btnComplete . $btnCancel . '</div>';
                })
                ->rawColumns(['code_date', 'vehicle_driver', 'route', 'approvals_status', 'status_badge', 'action'])
                ->make(true);
        }

        // Available Vehicles Query: Status must be 'available'
        $availableVehicles = Vehicle::where('status', 'available')
            ->with('location')
            ->get();

        // Available Drivers Query: Status must be 'available'
        $availableDrivers = Driver::where('status', 'available')
            ->get();

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
     * Store a newly created vehicle booking (Admin Only).
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
            'start_location_id' => ['required', Rule::exists('locations', 'id')->whereNull('deleted_at')],
            'destination_location_id' => ['required', Rule::exists('locations', 'id')->whereNull('deleted_at')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'approver_1_id' => ['required', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'approver_2_id' => ['required', Rule::exists('users', 'id')->whereNull('deleted_at'), 'different:approver_1_id'],
            'purpose' => ['required', 'string'],
        ], [
            'vehicle_id.required' => 'Pilih kendaraan yang tersedia.',
            'driver_id.required' => 'Pilih driver yang tersedia.',
            'start_location_id.required' => 'Pilih lokasi penjemputan/asal.',
            'destination_location_id.required' => 'Pilih lokasi tujuan.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'approver_1_id.required' => 'Pilih Penyetuju Level 1 (Atasan L1 dengan Role Level 1 / SPV).',
            'approver_2_id.required' => 'Pilih Penyetuju Level 2 (Atasan L2 dengan Role Level 2 / Manager).',
            'approver_2_id.different' => 'Penyetuju Level 2 harus berbeda dari Penyetuju Level 1.',
            'purpose.required' => 'Keperluan pemakaian kendaraan wajib diisi.',
        ]);

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

        // Double-check availability of vehicle
        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
        if ($vehicle->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan "' . $vehicle->name . '" saat ini tidak tersedia (status: ' . strtoupper($vehicle->status) . ').',
            ], 422);
        }

        // Double-check availability of driver
        $driver = Driver::findOrFail($validated['driver_id']);
        if ($driver->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Driver "' . $driver->name . '" saat ini tidak tersedia (status: ' . strtoupper($driver->status) . ').',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Generate unique booking code e.g. BOOK-20260814-001
            $count = VehicleBooking::withTrashed()->whereDate('created_at', now())->count() + 1;
            do {
                $bookingCode = 'BOOK-' . now()->format('Ymd') . '-' . str_pad($count++, 3, '0', STR_PAD_LEFT);
            } while (VehicleBooking::withTrashed()->where('booking_code', $bookingCode)->exists());

            $booking = VehicleBooking::create([
                'booking_code' => $bookingCode,
                'user_id' => Auth::id(),
                'vehicle_id' => $validated['vehicle_id'],
                'driver_id' => $validated['driver_id'],
                'start_location_id' => $validated['start_location_id'],
                'destination_location_id' => $validated['destination_location_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'purpose' => $validated['purpose'],
                'status' => 'pending',
            ]);

            // Automatically update Vehicle and Driver status to 'reserved'
            $vehicle->update(['status' => 'reserved']);
            $driver->update(['status' => 'reserved']);

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
                'description' => 'Menginput pemesanan kendaraan ' . $booking->booking_code . ' (Status Armada & Driver diubah ke RESERVED)',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pemesanan kendaraan ' . $bookingCode . ' berhasil dibuat. Status kendaraan & driver otomatis diubah menjadi RESERVED.',
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
            $booking->delete();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'CANCEL_BOOKING',
                'entity_type' => 'VehicleBooking',
                'entity_id' => $id,
                'description' => 'Membatalkan pemesanan kendaraan: ' . $bookingCode . ' (Status Armada & Driver dikembalikan ke AVAILABLE)',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pemesanan kendaraan ' . $bookingCode . ' berhasil dibatalkan. Status armada & driver telah dikembalikan ke TERSEDIA.',
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
}
