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

        // 1. Vehicle Fleet Metrics
        $totalVehicles = Vehicle::count();
        $passengerVehicles = Vehicle::where('type', 'passenger')->count();
        $cargoVehicles = Vehicle::where('type', 'cargo')->count();

        $companyVehicles = Vehicle::where('ownership', 'company')->count();
        $rentedVehicles = Vehicle::where('ownership', 'rented')->count();

        $availableVehiclesCount = Vehicle::where('status', 'available')->count();
        $inUseVehiclesCount = Vehicle::whereIn('status', ['in_use', 'reserved'])->count();
        $maintenanceVehiclesCount = Vehicle::where('status', 'maintenance')->count();

        // 2. Booking Metrics
        $totalBookings = VehicleBooking::count();
        $pendingBookings = VehicleBooking::where('status', 'pending')->count();
        $approvedBookings = VehicleBooking::where('status', 'approved')->count();
        $completedBookings = VehicleBooking::where('status', 'completed')->count();
        $inProgressBookings = VehicleBooking::where('status', 'in_progress')->count();

        // 3. Operational Costs & Consumption
        $totalFuelCost = FuelLog::sum('total_cost');
        $totalFuelLiters = FuelLog::sum('fuel_amount');
        $totalServiceCost = ServiceLog::sum('cost');

        // 4. Pending Approvals for logged-in user
        $myPendingApprovals = BookingApproval::where('approver_id', $user->id)
            ->where('status', 'pending')
            ->with(['booking.vehicle', 'booking.driver', 'booking.user', 'booking.startLocation', 'booking.destinationLocation'])
            ->latest()
            ->take(5)
            ->get();

        // 5. Recent Bookings (Latest 5)
        $recentBookings = VehicleBooking::with(['vehicle', 'driver', 'user', 'startLocation', 'destinationLocation'])
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

            $total = VehicleBooking::whereYear('start_date', $year)
                ->whereMonth('start_date', $month)
                ->count();

            $completed = VehicleBooking::whereYear('start_date', $year)
                ->whereMonth('start_date', $month)
                ->where('status', 'completed')
                ->count();

            $monthlyUsage->push([
                'label' => $label,
                'total' => $total,
                'completed' => $completed,
            ]);
        }
        $monthlyLabels = $monthlyUsage->pluck('label')->toArray();
        $monthlyTotals = $monthlyUsage->pluck('total')->toArray();
        $monthlyCompleted = $monthlyUsage->pluck('completed')->toArray();

        // 8. Chart Data: 5 Kendaraan Paling Sering Digunakan (Top Used Vehicles)
        $topVehicles = Vehicle::withCount('bookings')
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
            'Tersedia' => $availableVehiclesCount,
            'Digunakan / On Trip' => $inUseVehiclesCount,
            'Maintenance / Servis' => $maintenanceVehiclesCount,
        ];

        return view('dashboard', compact(
            'totalVehicles',
            'passengerVehicles',
            'cargoVehicles',
            'companyVehicles',
            'rentedVehicles',
            'availableVehiclesCount',
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
            'monthlyTotals',
            'monthlyCompleted',
            'topVehicleLabels',
            'topVehicleCounts',
            'locationLabels',
            'locationCounts',
            'vehicleStatusCounts'
        ));
    }
}
