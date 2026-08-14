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
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display dashboard.
     */
    public function index()
    {
        $user = Auth::user();

        // Metrics
        $totalVehicles = Vehicle::count();
        $passengerVehicles = Vehicle::where('type', 'passenger')->count();
        $cargoVehicles = Vehicle::where('type', 'cargo')->count();

        $companyVehicles = Vehicle::where('ownership', 'company')->count();
        $rentedVehicles = Vehicle::where('ownership', 'rented')->count();

        $totalBookings = VehicleBooking::count();
        $pendingBookings = VehicleBooking::where('status', 'pending')->count();
        $approvedBookings = VehicleBooking::where('status', 'approved')->count();
        $completedBookings = VehicleBooking::where('status', 'completed')->count();

        $totalFuelCost = FuelLog::sum('total_cost');
        $totalFuelLiters = FuelLog::sum('fuel_amount');

        $totalServiceCost = ServiceLog::sum('cost');

        // Pending approvals for logged-in user if approver
        $myPendingApprovals = BookingApproval::where('approver_id', $user->id)
            ->where('status', 'pending')
            ->with(['booking.vehicle', 'booking.driver', 'booking.user'])
            ->latest()
            ->take(5)
            ->get();

        // Recent Activity Logs
        $recentActivities = ActivityLog::with('user')
            ->latest('id')
            ->take(8)
            ->get();

        // Chart 1: Vehicle usage per Location
        $locationUsageData = Location::withCount('vehicles')->get();
        $locationLabels = $locationUsageData->pluck('name')->toArray();
        $locationCounts = $locationUsageData->pluck('vehicles_count')->toArray();

        // Chart 2: Booking status distribution
        $bookingStatusCounts = [
            'Pending' => $pendingBookings,
            'Disetujui' => $approvedBookings,
            'Selesai' => $completedBookings,
            'Ditolak' => VehicleBooking::where('status', 'rejected')->count(),
        ];

        return view('dashboard', compact(
            'totalVehicles',
            'passengerVehicles',
            'cargoVehicles',
            'companyVehicles',
            'rentedVehicles',
            'totalBookings',
            'pendingBookings',
            'approvedBookings',
            'completedBookings',
            'totalFuelCost',
            'totalFuelLiters',
            'totalServiceCost',
            'myPendingApprovals',
            'recentActivities',
            'locationLabels',
            'locationCounts',
            'bookingStatusCounts'
        ));
    }
}
