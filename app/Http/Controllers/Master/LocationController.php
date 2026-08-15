<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Location;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class LocationController extends Controller
{
    /**
     * Display a listing of locations (Supports Yajra DataTables Server-Side Processing).
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Location::with('region')->withCount('vehicles');

            return DataTables::of($query)
                ->addIndexColumn()
                ->filterColumn('region.name', function ($query, $keyword) {
                    $query->whereHas('region', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->make(true);
        }

        $regions = Region::orderBy('name')->get();

        return view('master.locations.index', compact('regions'));
    }

    /**
     * Get dynamic options for location forms (AJAX).
     */
    public function options()
    {
        return response()->json([
            'regions' => Region::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created location in storage (Admin Only).
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
            'region_id' => ['nullable', 'exists:regions,id'],
            'type' => ['required', Rule::in(['head_office', 'branch_office', 'mine_site'])],
            'address' => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama lokasi wajib diisi.',
            'type.required' => 'Tipe lokasi wajib dipilih.',
        ]);

        $location = Location::create($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'CREATE_LOCATION',
            'entity_type' => 'Location',
            'entity_id' => $location->id,
            'description' => 'Menambahkan data lokasi baru: ' . $location->name,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data lokasi berhasil ditambahkan.',
                'data' => $location,
            ]);
        }

        return redirect()->route('locations.index')->with('success', 'Data lokasi berhasil ditambahkan.');
    }

    /**
     * Display the specified location data (JSON for edit modal).
     */
    public function show(Location $location)
    {
        return response()->json($location->load('region'));
    }

    /**
     * Update the specified location in storage (Admin Only).
     */
    public function update(Request $request, Location $location)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat mengubah data master.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'type' => ['required', Rule::in(['head_office', 'branch_office', 'mine_site'])],
            'address' => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama lokasi wajib diisi.',
            'type.required' => 'Tipe lokasi wajib dipilih.',
        ]);

        $location->update($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'UPDATE_LOCATION',
            'entity_type' => 'Location',
            'entity_id' => $location->id,
            'description' => 'Mengubah data lokasi: ' . $location->name,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data lokasi berhasil diperbarui.',
                'data' => $location,
            ]);
        }

        return redirect()->route('locations.index')->with('success', 'Data lokasi berhasil diperbarui.');
    }

    /**
     * Remove the specified location from storage (Admin Only).
     */
    public function destroy(Request $request, Location $location)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat menghapus data master.',
            ], 403);
        }

        $locationName = $location->name;
        $locationId = $location->id;

        $location->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'DELETE_LOCATION',
            'entity_type' => 'Location',
            'entity_id' => $locationId,
            'description' => 'Menghapus data lokasi: ' . $locationName,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data lokasi berhasil dihapus.',
            ]);
        }

        return redirect()->route('locations.index')->with('success', 'Data lokasi berhasil dihapus.');
    }
}
