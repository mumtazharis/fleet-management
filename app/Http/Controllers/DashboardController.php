<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BookingApproval;
use App\Models\FuelLog;
use App\Models\Location;
use App\Models\ServiceLog;
use App\Models\Vehicle;
use App\Models\VehicleBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the comprehensive dashboard view.
     */
    public function index()
    {
        $user = Auth::user();
        $roleName = strtolower($user->role?->name ?? '');
        $userLevel = $user->role?->level ?? null;

        // 1. Vehicle Fleet Metrics (Single Aggregate Query — replaces 9 individual queries)
        $vehicleStats = Vehicle::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN type = 'passenger' THEN 1 ELSE 0 END) as passenger,
            SUM(CASE WHEN type = 'cargo' THEN 1 ELSE 0 END) as cargo,
            SUM(CASE WHEN ownership = 'company' THEN 1 ELSE 0 END) as company_owned,
            SUM(CASE WHEN ownership = 'rented' THEN 1 ELSE 0 END) as rented,
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved,
            SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance
        ")->first();

        $totalVehicles = (int) $vehicleStats->total;
        $passengerVehicles = (int) $vehicleStats->passenger;
        $cargoVehicles = (int) $vehicleStats->cargo;
        $companyVehicles = (int) $vehicleStats->company_owned;
        $rentedVehicles = (int) $vehicleStats->rented;
        $availableVehiclesCount = (int) $vehicleStats->available;
        $reservedVehiclesCount = (int) $vehicleStats->reserved;
        $inUseVehiclesCount = (int) $vehicleStats->in_use;
        $maintenanceVehiclesCount = (int) $vehicleStats->maintenance;

        // 2. Booking Metrics (Single Aggregate Query — replaces 5 individual queries, excludes cancelled & soft-deleted)
        $bookingStats = VehicleBooking::where('status', '!=', 'cancelled')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
            ")->first();

        $totalBookings = (int) $bookingStats->total;
        $pendingBookings = (int) $bookingStats->pending;
        $approvedBookings = (int) $bookingStats->approved;
        $completedBookings = (int) $bookingStats->completed;
        $inProgressBookings = (int) $bookingStats->in_progress;

        // 3. Operational Costs & Consumption (2 queries instead of 3)
        $fuelStats = FuelLog::selectRaw('COALESCE(SUM(total_cost), 0) as total_cost, COALESCE(SUM(fuel_amount), 0) as total_liters')->first();
        $totalFuelCost = (float) $fuelStats->total_cost;
        $totalFuelLiters = (float) $fuelStats->total_liters;
        $totalServiceCost = (float) ServiceLog::sum('cost');

        // 4. Pending Approvals based on User Role & Sequential Approval Level (EXCLUDE CANCELLED / DELETED BOOKINGS)
        $pendingApprovalsQuery = BookingApproval::where('status', 'pending')
            ->whereHas('booking', function ($q) {
                $q->whereNull('deleted_at')
                  ->whereNotIn('status', ['cancelled', 'rejected']);
            })
            ->with([
                'booking.vehicle',
                'booking.driver',
                'booking.user',
                'booking.startLocation',
                'booking.destinationLocation',
                'approver.role'
            ])
            ->latest('id');

        if ($roleName === 'admin') {
            // Admin sees active pending approvals
            $pendingApprovalsQuery->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('approval_level', 2)
                        ->whereHas('booking.approvals', function ($l1) {
                            $l1->where('approval_level', 1)->where('status', 'approved');
                        });
                })->orWhere(function ($sub) {
                    $sub->where('approval_level', 1);
                });
            });
        } elseif ($roleName === 'supervisor' || $userLevel == 1) {
            // Supervisor (Level 1): Only sees Level 1 pending approvals assigned to them or supervisor role
            $pendingApprovalsQuery->where('approval_level', 1)
                ->where(function ($q) use ($user) {
                    $q->where('approver_id', $user->id)
                      ->orWhereHas('approver', function ($sub) {
                          $sub->whereHas('role', function ($r) {
                              $r->where('level', 1)->orWhere('name', 'supervisor');
                          });
                      });
                });
        } elseif ($roleName === 'manager' || $userLevel == 2) {
            // Manager (Level 2): Only sees Level 2 pending approvals if Level 1 is already approved
            $pendingApprovalsQuery->where('approval_level', 2)
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
            $pendingApprovalsQuery->where('approver_id', $user->id);
        }

        $myPendingApprovals = $pendingApprovalsQuery->take(5)->get();

        // 5. Recent Bookings (Exclude cancelled / soft deleted)
        $recentBookings = VehicleBooking::whereNull('deleted_at')
            ->where('status', '!=', 'cancelled')
            ->with(['vehicle', 'driver', 'user', 'startLocation', 'destinationLocation'])
            ->latest('id')
            ->take(5)
            ->get();

        // 6. Recent Activity Logs (Audit Trail)
        $recentActivities = ActivityLog::with('user.role')
            ->latest('id')
            ->take(6)
            ->get();

        // 7. Chart Data: Tren Pemakaian Kendaraan Bulanan (Past 6 Months)
        //    Single aggregate query with withTrashed() — includes soft-deleted rejected bookings for accurate trends
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
        $monthlyRawData = VehicleBooking::withTrashed()
            ->where('start_date', '>=', $sixMonthsAgo)
            ->selectRaw("
                YEAR(start_date) as y,
                MONTH(start_date) as m,
                SUM(CASE WHEN status IN ('approved', 'in_progress', 'completed') THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
            ")
            ->groupByRaw('YEAR(start_date), MONTH(start_date)')
            ->get()
            ->keyBy(fn($item) => $item->y . '-' . $item->m);

        $monthlyUsage = collect([]);
        for ($i = 5; $i >= 0; $i--) {
            $dt = now()->subMonths($i);
            $key = $dt->year . '-' . $dt->month;
            $label = $dt->translatedFormat('M Y');
            $data = $monthlyRawData->get($key);

            $monthlyUsage->push([
                'label' => $label,
                'approved' => (int) ($data?->approved_count ?? 0),
                'completed' => (int) ($data?->completed_count ?? 0),
                'rejected' => (int) ($data?->rejected_count ?? 0),
            ]);
        }
        $monthlyLabels = $monthlyUsage->pluck('label')->toArray();
        $monthlyApproved = $monthlyUsage->pluck('approved')->toArray();
        $monthlyCompleted = $monthlyUsage->pluck('completed')->toArray();
        $monthlyRejected = $monthlyUsage->pluck('rejected')->toArray();

        // 8. Chart Data: 5 Kendaraan Paling Sering Digunakan (Hanya status completed)
        $topVehicles = Vehicle::withCount(['bookings' => function ($q) {
                $q->where('status', 'completed');
            }])
            ->orderByDesc('bookings_count')
            ->take(5)
            ->get();
        $topVehicleLabels = $topVehicles->map(fn($v) => $v->name . ' (' . $v->license_plate . ')')->toArray();
        $topVehicleCounts = $topVehicles->pluck('bookings_count')->toArray();

        // 9. Chart Data: Distribusi Armada per Lokasi / Tambang
        $locationUsageData = Location::withCount('vehicles')->orderBy('name')->get();
        $locationLabels = $locationUsageData->pluck('name')->toArray();
        $locationCounts = $locationUsageData->pluck('vehicles_count')->toArray();

        // 10. Chart Data: Status Kesiapan Armada Kendaraan
        $vehicleStatusCounts = [
            'Tersedia (Available)' => $availableVehiclesCount,
            'Dipesan (Reserved)' => $reservedVehiclesCount,
            'Digunakan (On Trip)' => $inUseVehiclesCount,
            'Servis (Maintenance)' => $maintenanceVehiclesCount,
        ];

        return view('dashboard', compact(
            'totalVehicles',
            'passengerVehicles',
            'cargoVehicles',
            'companyVehicles',
            'rentedVehicles',
            'availableVehiclesCount',
            'reservedVehiclesCount',
            'inUseVehiclesCount',
            'maintenanceVehiclesCount',
            'totalBookings',
            'pendingBookings',
            'approvedBookings',
            'completedBookings',
            'inProgressBookings',
            'totalFuelCost',
            'totalFuelLiters',
            'totalServiceCost',
            'myPendingApprovals',
            'recentBookings',
            'recentActivities',
            'monthlyLabels',
            'monthlyApproved',
            'monthlyCompleted',
            'monthlyRejected',
            'topVehicleLabels',
            'topVehicleCounts',
            'locationLabels',
            'locationCounts',
            'vehicleStatusCounts'
        ));
    }
}
