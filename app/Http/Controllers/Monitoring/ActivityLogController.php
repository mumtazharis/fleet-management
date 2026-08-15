<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs (Supports Yajra DataTables Server-Side Processing).
     */
    public function index(Request $request)
    {
        if (Auth::user()->role?->name !== 'admin') {
            abort(403, 'Akses ditolak. Log aktivitas sistem hanya dapat diakses oleh Administrator.');
        }
        if ($request->ajax()) {
            $query = ActivityLog::with(['user.role'])->select('activity_logs.*');

            return DataTables::of($query)
                ->addIndexColumn()
                ->filterColumn('user.name', function ($query, $keyword) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                          ->orWhere('email', 'like', "%{$keyword}%");
                    });
                })
                ->make(true);
        }

        $totalLogs = ActivityLog::count();
        $todayLogs = ActivityLog::whereDate('created_at', today())->count();
        $uniqueUsers = ActivityLog::distinct('user_id')->count('user_id');

        return view('monitoring.activity_logs.index', compact('totalLogs', 'todayLogs', 'uniqueUsers'));
    }

    /**
     * Display the specified activity log details (JSON for modal).
     */
    public function show(ActivityLog $activityLog)
    {
        if (Auth::user()->role?->name !== 'admin') {
            abort(403, 'Akses ditolak. Log aktivitas sistem hanya dapat diakses oleh Administrator.');
        }

        $activityLog->load(['user.role']);

        return response()->json($activityLog);
    }
}
