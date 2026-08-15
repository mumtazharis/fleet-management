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

        // 1. Vehicle Fleet Metrics
        $totalVehicles = Vehicle::count();
        $passengerVehicles = Vehicle::where('type', 'passenger')->count();
        $cargoVehicles = Vehicle::where('type', 'cargo')->count();

        $companyVehicles = Vehicle::where('ownership', 'company')->count();
        $rentedVehicles = Vehicle::where('ownership', 'rented')->count();

        $availableVehiclesCount = Vehicle::where('status', 'available')->count();
        $reservedVehiclesCount = Vehicle::where('status', 'reserved')->count();
        $inUseVehiclesCount = Vehicle::where('status', 'in_use')->count();
        $maintenanceVehiclesCount = Vehicle::where('status', 'maintenance')->count();

        // 2. Booking Metrics (Exclude Cancelled & Soft-deleted)
        $totalBookings = VehicleBooking::whereNull('deleted_at')->where('status', '!=', 'cancelled')->count();
        $pendingBookings = VehicleBooking::whereNull('deleted_at')->where('status', 'pending')->count();
        $approvedBookings = VehicleBooking::whereNull('deleted_at')->where('status', 'approved')->count();
        $completedBookings = VehicleBooking::whereNull('deleted_at')->where('status', 'completed')->count();
        $inProgressBookings = VehicleBooking::whereNull('deleted_at')->where('status', 'in_progress')->count();

        // 3. Operational Costs & Consumption
        $totalFuelCost = FuelLog::sum('total_cost');
        $totalFuelLiters = FuelLog::sum('fuel_amount');
        $totalServiceCost = ServiceLog::sum('cost');

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
        $monthlyUsage = collect([]);
        for ($i = 5; $i >= 0; $i--) {
            $dt = now()->subMonths($i);
            $year = $dt->year;
            $month = $dt->month;
            $label = $dt->translatedFormat('M Y');

            $approved = VehicleBooking::whereYear('start_date', $year)
                ->whereMonth('start_date', $month)
                ->whereIn('status', ['approved', 'in_progress', 'completed'])
                ->count();

            $completed = VehicleBooking::whereYear('start_date', $year)
                ->whereMonth('start_date', $month)
                ->where('status', 'completed')
                ->count();

            $rejected = VehicleBooking::whereYear('start_date', $year)
                ->whereMonth('start_date', $month)
                ->where('status', 'rejected')
                ->count();

            $monthlyUsage->push([
                'label' => $label,
                'approved' => $approved,
                'completed' => $completed,
                'rejected' => $rejected,
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
