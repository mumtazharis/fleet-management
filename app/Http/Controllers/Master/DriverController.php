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

            return DataTables::of($query)
                ->addIndexColumn()
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
