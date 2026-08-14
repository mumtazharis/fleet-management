<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class DriverController extends Controller
{
    /**
     * Display a listing of drivers (Supports Yajra DataTables Server-Side Processing).
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Driver::query();
            $isAdmin = Auth::user()->role?->name === 'admin';

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('driver_name', function ($driver) {
                    return '
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-icon bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <strong class="text-dark">' . e($driver->name) . '</strong>
                        </div>
                    ';
                })
                ->addColumn('phone_formatted', function ($driver) {
                    if (!$driver->phone) {
                        return '<span class="text-muted italic">-</span>';
                    }
                    return '<i class="bi bi-telephone text-primary me-1"></i> ' . e($driver->phone);
                })
                ->addColumn('license_badge', function ($driver) {
                    if (!$driver->license_number) {
                        return '<span class="text-muted italic">-</span>';
                    }
                    return '<span class="badge text-bg-dark font-monospace"><i class="bi bi-card-heading me-1"></i>' . e($driver->license_number) . '</span>';
                })
                ->addColumn('status_badge', function ($driver) {
                    if ($driver->status === 'available') {
                        return '<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i> Tersedia</span>';
                    } elseif ($driver->status === 'reserved') {
                        return '<span class="badge text-bg-warning text-dark"><i class="bi bi-clock-history me-1"></i> Dipesan (Reserved)</span>';
                    } elseif ($driver->status === 'on_trip') {
                        return '<span class="badge text-bg-primary"><i class="bi bi-truck me-1"></i> Sedang Bertugas</span>';
                    }
                    return '<span class="badge text-bg-secondary"><i class="bi bi-person-x me-1"></i> Libur / Nonaktif</span>';
                })
                ->addColumn('action', function ($driver) use ($isAdmin) {
                    if (!$isAdmin) {
                        return '<span class="badge text-bg-light border text-secondary"><i class="bi bi-eye me-1"></i> Read Only</span>';
                    }
                    return '
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-edit" data-id="' . $driver->id . '" title="Edit Driver">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete" data-id="' . $driver->id . '" data-name="' . e($driver->name) . '" title="Hapus Driver">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['driver_name', 'phone_formatted', 'license_badge', 'status_badge', 'action'])
                ->make(true);
        }

        return view('master.drivers.index');
    }

    /**
     * Store a newly created driver in storage (Admin Only).
     */
    public function store(Request $request)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat menambah data master.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'license_number' => ['nullable', 'string', 'max:100'],
        ], [
            'name.required' => 'Nama driver wajib diisi.',
        ]);

        $validated['status'] = 'available';

        $driver = Driver::create($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'CREATE_DRIVER',
            'entity_type' => 'Driver',
            'entity_id' => $driver->id,
            'description' => 'Menambahkan data driver baru: ' . $driver->name,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data driver berhasil ditambahkan.',
                'data' => $driver,
            ]);
        }

        return redirect()->route('drivers.index')->with('success', 'Data driver berhasil ditambahkan.');
    }

    /**
     * Display the specified driver data (JSON for edit modal).
     */
    public function show(Driver $driver)
    {
        return response()->json($driver);
    }

    /**
     * Update the specified driver in storage (Admin Only).
     */
    public function update(Request $request, Driver $driver)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat mengubah data master.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'license_number' => ['nullable', 'string', 'max:100'],
        ], [
            'name.required' => 'Nama driver wajib diisi.',
        ]);

        $driver->update($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'UPDATE_DRIVER',
            'entity_type' => 'Driver',
            'entity_id' => $driver->id,
            'description' => 'Mengubah data driver: ' . $driver->name,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data driver berhasil diperbarui.',
                'data' => $driver,
            ]);
        }

        return redirect()->route('drivers.index')->with('success', 'Data driver berhasil diperbarui.');
    }

    /**
     * Remove the specified driver from storage (Admin Only).
     */
    public function destroy(Request $request, Driver $driver)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat menghapus data master.',
            ], 403);
        }

        if ($driver->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus! Driver "' . $driver->name . '" tidak dapat dihapus karena berstatus ' . strtoupper($driver->status) . ' (hanya driver berstatus Tersedia yang dapat dihapus).',
            ], 422);
        }

        $driverName = $driver->name;
        $driverId = $driver->id;

        $driver->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'DELETE_DRIVER',
            'entity_type' => 'Driver',
            'entity_id' => $driverId,
            'description' => 'Menghapus data driver: ' . $driverName,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data driver berhasil dihapus.',
            ]);
        }

        return redirect()->route('drivers.index')->with('success', 'Data driver berhasil dihapus.');
    }
}
