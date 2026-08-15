<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BookingApproval;
use App\Models\VehicleBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BookingApprovalController extends Controller
{
    /**
     * Display a listing of booking approvals (Supports Yajra DataTables Server-Side Processing).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $roleName = strtolower($user->role?->name ?? '');

        if ($request->ajax()) {
            $query = BookingApproval::with([
                'booking.user',
                'booking.vehicle',
                'booking.driver',
                'booking.startLocation',
                'booking.destinationLocation',
                'booking.approvals.approver',
                'approver.role'
            ])->select('booking_approvals.*');

            // Filtering based on role and sequential workflow:
            if ($roleName === 'admin') {
                // Admin sees 1 active approval row per booking code (current / latest level)
                $query->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('approval_level', 2)
                            ->whereHas('booking.approvals', function ($l1) {
                                $l1->where('approval_level', 1)->where('status', 'approved');
                            });
                    })->orWhere(function ($sub) {
                        $sub->where('approval_level', 1)
                            ->whereHas('booking.approvals', function ($l1) {
                                $l1->where('approval_level', 1)->whereIn('status', ['pending', 'rejected']);
                            });
                    });
                });
            } elseif ($roleName === 'supervisor' || $user->role?->level == 1) {
                // Supervisor (Level 1): Only sees Level 1 approval tasks assigned to them or Level 1
                $query->where('approval_level', 1)
                    ->where(function ($q) use ($user) {
                        $q->where('approver_id', $user->id)
                          ->orWhereHas('approver', function ($sub) {
                              $sub->whereHas('role', function ($r) {
                                  $r->where('level', 1)->orWhere('name', 'supervisor');
                              });
                          });
                    });
            } elseif ($roleName === 'manager' || $user->role?->level == 2) {
                // Manager (Level 2): Only sees Level 2 approval tasks
                // CRITICAL SEQUENTIAL RULE: ONLY IF LEVEL 1 IS ALREADY APPROVED!
                $query->where('approval_level', 2)
                    ->where(function ($q) use ($user) {
                        $q->where('approver_id', $user->id)
                          ->orWhereHas('approver', function ($sub) {
                              $sub->whereHas('role', function ($r) {
                                  $r->where('level', 2)->orWhere('name', 'manager');
                              });
                          });
                    })
                    ->whereHas('booking.approvals', function ($q) {
                        $q->where('approval_level', 1)->where('status', 'approved');
                    });
            } else {
                $query->where('approver_id', $user->id);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->filterColumn('booking_code', function ($q, $keyword) {
                    $q->whereHas('booking', function ($b) use ($keyword) {
                        $b->where('booking_code', 'like', "%{$keyword}%")
                          ->orWhereHas('user', function ($u) use ($keyword) {
                              $u->where('name', 'like', "%{$keyword}%");
                          });
                    });
                })
                ->filterColumn('fleet_driver', function ($q, $keyword) {
                    $q->whereHas('booking', function ($b) use ($keyword) {
                        $b->whereHas('vehicle', function ($v) use ($keyword) {
                            $v->where('name', 'like', "%{$keyword}%")
                              ->orWhere('license_plate', 'like', "%{$keyword}%");
                        })->orWhereHas('driver', function ($d) use ($keyword) {
                            $d->where('name', 'like', "%{$keyword}%");
                        });
                    });
                })
                ->filterColumn('schedule_route', function ($q, $keyword) {
                    $q->whereHas('booking', function ($b) use ($keyword) {
                        $b->whereHas('startLocation', function ($l) use ($keyword) {
                            $l->where('name', 'like', "%{$keyword}%");
                        })->orWhereHas('destinationLocation', function ($l) use ($keyword) {
                            $l->where('name', 'like', "%{$keyword}%");
                        });
                    });
                })
                ->addColumn('booking_code', function ($app) {
                    $b = $app->booking;
                    if (!$b) return '-';
                    return '
                        <strong class="text-primary font-monospace d-block">' . e($b->booking_code) . '</strong>
                        <small class="text-muted"><i class="bi bi-person me-1"></i>Pemohon: ' . e($b->user?->name ?? 'System') . '</small>
                    ';
                })
                ->addColumn('schedule_route', function ($app) {
                    $b = $app->booking;
                    if (!$b) return '-';
                    $startDate = $b->start_date ? $b->start_date->format('d/m/Y H:i') : '-';
                    $endDate = $b->end_date ? $b->end_date->format('d/m/Y H:i') : '-';
                    $startLoc = $b->startLocation ? e($b->startLocation->name) : '-';
                    $destLoc = $b->destinationLocation ? e($b->destinationLocation->name) : '-';

                    return '
                        <div class="lh-sm">
                            <small class="d-block text-dark fw-semibold"><i class="bi bi-calendar-range me-1 text-primary"></i>' . $startDate . ' - ' . $endDate . '</small>
                            <small class="d-block text-secondary mt-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i>' . $startLoc . ' &rarr; ' . $destLoc . '</small>
                        </div>
                    ';
                })
                ->addColumn('fleet_driver', function ($app) {
                    $b = $app->booking;
                    if (!$b) return '-';
                    $vName = $b->vehicle ? e($b->vehicle->name) . ' (' . e($b->vehicle->license_plate) . ')' : '-';
                    $dName = $b->driver ? e($b->driver->name) : '-';
                    return '
                        <div class="lh-sm">
                            <strong class="d-block text-dark"><i class="bi bi-truck text-warning me-1"></i>' . $vName . '</strong>
                            <small class="text-secondary"><i class="bi bi-person-badge me-1"></i>Driver: ' . $dName . '</small>
                        </div>
                    ';
                })
                ->addColumn('approval_level_badge', function ($app) {
                    $level = $app->approval_level;
                    $approverName = $app->approver ? e($app->approver->name) : 'User';

                    if ($level == 1) {
                        return '<span class="badge text-bg-info"><i class="bi bi-shield-check me-1"></i>Level 1 (SPV: ' . $approverName . ')</span>';
                    }
                    return '<span class="badge text-bg-primary"><i class="bi bi-award me-1"></i>Level 2 (Manager: ' . $approverName . ')</span>';
                })
                ->addColumn('status_badge', function ($app) {
                    $b = $app->booking;
                    if ($b && ($b->status === 'cancelled' || $b->trashed())) {
                        return '<span class="badge text-bg-secondary"><i class="bi bi-x-octagon me-1"></i> Dibatalkan</span>';
                    }
                    if ($app->status === 'approved') {
                        return '<span class="badge text-bg-success"><i class="bi bi-check-circle-fill me-1"></i> Disetujui</span>';
                    } elseif ($app->status === 'rejected') {
                        $noteStr = $app->note ? 'Catatan: ' . e($app->note) : '';
                        return '<span class="badge text-bg-danger" title="' . $noteStr . '"><i class="bi bi-x-circle-fill me-1"></i> Ditolak</span>';
                    }
                    return '<span class="badge text-bg-warning text-dark"><i class="bi bi-clock me-1"></i> Menunggu Approval</span>';
                })
                ->addColumn('action', function ($app) use ($user, $roleName) {
                    $b = $app->booking;
                    $bookingId = $b ? $b->id : 0;

                    $btnDetail = '
                        <button type="button" class="btn btn-outline-info btn-sm btn-detail" data-booking-id="' . $bookingId . '" title="Lihat Detail Pemesanan">
                            <i class="bi bi-eye me-1"></i> Detail
                        </button>
                    ';

                    if ($app->status !== 'pending' || ($b && ($b->status === 'cancelled' || $b->trashed()))) {
                        return $btnDetail;
                    }

                    // Check strict authorization:
                    // Admin cannot process approvals for SPV or Manager unless explicitly set as approver_id
                    $isAssignedApprover = ($app->approver_id == $user->id);
                    $hasMatchingLevel = ($user->role?->level == $app->approval_level && $roleName !== 'admin');

                    $canProcess = ($isAssignedApprover || $hasMatchingLevel);

                    // For Level 2, also check Level 1 is approved
                    if ($app->approval_level == 2) {
                        $l1Approved = BookingApproval::where('vehicle_booking_id', $app->vehicle_booking_id)
                            ->where('approval_level', 1)
                            ->where('status', 'approved')
                            ->exists();
                        if (!$l1Approved) {
                            $canProcess = false;
                        }
                    }

                    if (!$canProcess) {
                        if ($roleName === 'admin') {
                            return $btnDetail;
                        }
                        return $btnDetail;
                    }

                    return '
                        <div class="btn-group btn-group-sm">
                            ' . $btnDetail . '
                            <button type="button" class="btn btn-success btn-sm btn-approve" data-id="' . $app->id . '" data-code="' . e($b?->booking_code) . '" title="Setujui Pemesanan">
                                <i class="bi bi-check-lg me-1"></i> Setujui
                            </button>
                            <button type="button" class="btn btn-danger btn-sm btn-reject" data-id="' . $app->id . '" data-code="' . e($b?->booking_code) . '" title="Tolak Pemesanan">
                                <i class="bi bi-x-lg me-1"></i> Tolak
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['booking_code', 'schedule_route', 'fleet_driver', 'approval_level_badge', 'status_badge', 'action'])
                ->make(true);
        }

        return view('transactions.approvals.index');
    }

    /**
     * Approve the specified booking approval.
     */
    public function approve(Request $request, BookingApproval $approval)
    {
        $user = Auth::user();
        $roleName = strtolower($user->role?->name ?? '');

        $isAssignedApprover = ($approval->approver_id == $user->id);
        $hasMatchingLevel = ($user->role?->level == $approval->approval_level && $roleName !== 'admin');

        if (!$isAssignedApprover && !$hasMatchingLevel) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki kewenangan untuk menyetujui pemesanan Level ' . $approval->approval_level . ' ini.',
            ], 403);
        }

        if ($approval->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Persetujuan ini sudah diproses sebelumnya.',
            ], 422);
        }

        // If Level 2 approval, verify Level 1 is approved
        if ($approval->approval_level == 2) {
            $l1Approved = BookingApproval::where('vehicle_booking_id', $approval->vehicle_booking_id)
                ->where('approval_level', 1)
                ->where('status', 'approved')
                ->exists();

            if (!$l1Approved) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pemesanan ini belum disetujui oleh Penyetuju Level 1 (Supervisor/SPV).',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $approval->update([
                'status' => 'approved',
                'responded_at' => now(),
                'note' => $request->input('note', 'Disetujui oleh ' . $user->name),
            ]);

            $booking = $approval->booking;

            // Check if all approvals for this booking are now approved
            $unapprovedCount = BookingApproval::where('vehicle_booking_id', $booking->id)
                ->where('status', '!=', 'approved')
                ->count();

            if ($unapprovedCount === 0) {
                // Final Approval Completed!
                $booking->update(['status' => 'approved']);

                // Change Vehicle status to 'in_use'
                if ($booking->vehicle) {
                    $booking->vehicle->update(['status' => 'in_use']);
                }

                // Change Driver status to 'on_trip'
                if ($booking->driver) {
                    $booking->driver->update(['status' => 'on_trip']);
                }

                $msg = 'Pemesanan ' . $booking->booking_code . ' telah sepenuhnya DISETUJUI!';
            } else {
                $msg = 'Persetujuan Level 1 untuk ' . $booking->booking_code . ' berhasil disimpan. Menunggu approval Level 2 (Manager).';
            }

            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'APPROVE_BOOKING',
                'entity_type' => 'BookingApproval',
                'entity_id' => $approval->id,
                'description' => 'Menyetujui pemesanan kendaraan ' . $booking->booking_code . ' (Level ' . $approval->approval_level . ')',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses persetujuan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject the specified booking approval with reason note.
     */
    public function reject(Request $request, BookingApproval $approval)
    {
        $user = Auth::user();
        $roleName = strtolower($user->role?->name ?? '');

        $isAssignedApprover = ($approval->approver_id == $user->id);
        $hasMatchingLevel = ($user->role?->level == $approval->approval_level && $roleName !== 'admin');

        if (!$isAssignedApprover && !$hasMatchingLevel) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki kewenangan untuk menolak pemesanan Level ' . $approval->approval_level . ' ini.',
            ], 403);
        }

        if ($approval->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Persetujuan ini sudah diproses sebelumnya.',
            ], 422);
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ], [
            'note.required' => 'Alasan penolakan wajib diisi.',
            'note.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        DB::beginTransaction();
        try {
            $approval->update([
                'status' => 'rejected',
                'note' => $validated['note'],
                'responded_at' => now(),
            ]);

            $booking = $approval->booking;

            // Set booking status to rejected
            $booking->update(['status' => 'rejected']);

            // Restore Vehicle & Driver status back to 'available'
            if ($booking->vehicle && in_array($booking->vehicle->status, ['reserved', 'in_use'])) {
                $booking->vehicle->update(['status' => 'available']);
            }

            if ($booking->driver && in_array($booking->driver->status, ['reserved', 'on_trip'])) {
                $booking->driver->update(['status' => 'available']);
            }

            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'REJECT_BOOKING',
                'entity_type' => 'BookingApproval',
                'entity_id' => $approval->id,
                'description' => 'Menolak pemesanan kendaraan ' . $booking->booking_code . ' (Level ' . $approval->approval_level . '). Alasan: ' . $validated['note'],
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pemesanan kendaraan ' . $booking->booking_code . ' berhasil DITOLAK. Status armada & driver telah dikembalikan ke TERSEDIA.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses penolakan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
