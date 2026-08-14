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
            $isAdmin = Auth::user()->role?->name === 'admin';

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('location_name', function ($loc) {
                    $icon = 'bi-geo-alt';
                    if ($loc->type === 'head_office') {
                        $icon = 'bi-building-fill-gear';
                    } elseif ($loc->type === 'branch_office') {
                        $icon = 'bi-building-fill';
                    } elseif ($loc->type === 'mine_site') {
                        $icon = 'bi-funnel-fill';
                    }

                    return '
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-icon bg-warning bg-opacity-10 text-dark rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px;">
                                <i class="bi ' . $icon . ' fs-6"></i>
                            </div>
                            <div>
                                <strong class="text-dark d-block">' . e($loc->name) . '</strong>
                                <small class="text-muted"><i class="bi bi-truck me-1"></i>' . $loc->vehicles_count . ' unit di pool ini</small>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('region_name', function ($loc) {
                    if (!$loc->region) {
                        return '<span class="text-muted italic">-</span>';
                    }
                    return '<span class="badge text-bg-dark"><i class="bi bi-map me-1"></i>' . e($loc->region->name) . '</span>';
                })
                ->addColumn('type_badge', function ($loc) {
                    if ($loc->type === 'head_office') {
                        return '<span class="badge text-bg-primary"><i class="bi bi-building me-1"></i> Kantor Pusat</span>';
                    } elseif ($loc->type === 'branch_office') {
                        return '<span class="badge text-bg-info"><i class="bi bi-diagram-2 me-1"></i> Kantor Cabang</span>';
                    }
                    return '<span class="badge text-bg-warning text-dark"><i class="bi bi-geo-fill me-1"></i> Lokasi Tambang</span>';
                })
                ->addColumn('address_formatted', function ($loc) {
                    if (!$loc->address) {
                        return '<span class="text-muted italic">-</span>';
                    }
                    return '<small class="text-secondary">' . e($loc->address) . '</small>';
                })
                ->addColumn('action', function ($loc) use ($isAdmin) {
                    if (!$isAdmin) {
                        return '<span class="badge text-bg-light border text-secondary"><i class="bi bi-eye me-1"></i> Read Only</span>';
                    }
                    return '
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-edit" data-id="' . $loc->id . '" title="Edit Lokasi">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete" data-id="' . $loc->id . '" data-name="' . e($loc->name) . '" title="Hapus Lokasi">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    ';
                })
                ->filterColumn('region_name', function ($query, $keyword) {
                    $query->whereHas('region', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['location_name', 'region_name', 'type_badge', 'address_formatted', 'action'])
                ->make(true);
        }

        $regions = Region::orderBy('name')->get();

        return view('master.locations.index', compact('regions'));
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
