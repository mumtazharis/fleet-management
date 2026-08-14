<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Location;
use App\Models\RentalCompany;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class VehicleController extends Controller
{
    /**
     * Display a listing of vehicles (Supports Yajra DataTables Server-Side Processing).
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Vehicle::with(['location', 'rentalCompany'])->select('vehicles.*');
            $isAdmin = Auth::user()->role?->name === 'admin';

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('name_plate', function ($vehicle) {
                    return '
                        <strong class="d-block text-dark fs-6">' . e($vehicle->name) . '</strong>
                        <span class="badge text-bg-dark font-monospace"><i class="bi bi-card-text me-1"></i>' . e($vehicle->license_plate) . '</span>
                    ';
                })
                ->addColumn('type_badge', function ($vehicle) {
                    if ($vehicle->type === 'passenger') {
                        return '<span class="badge text-bg-primary"><i class="bi bi-people-fill me-1"></i> Angkutan Orang</span>';
                    }
                    return '<span class="badge text-bg-info"><i class="bi bi-box-seam-fill me-1"></i> Angkutan Barang</span>';
                })
                ->addColumn('ownership_badge', function ($vehicle) {
                    if ($vehicle->ownership === 'company') {
                        return '<span class="badge text-bg-success"><i class="bi bi-building-check me-1"></i> Milik Perusahaan</span>';
                    }
                    $rentalName = $vehicle->rentalCompany ? e($vehicle->rentalCompany->name) : 'Perusahaan Sewa';
                    return '
                        <span class="badge text-bg-warning text-dark"><i class="bi bi-journal-bookmark-fill me-1"></i> Sewa</span>
                        <small class="d-block text-muted mt-1">' . $rentalName . '</small>
                    ';
                })
                ->addColumn('location_name', function ($vehicle) {
                    $locationName = $vehicle->location ? e($vehicle->location->name) : '-';
                    return '<i class="bi bi-geo-alt-fill text-danger me-1"></i> ' . $locationName;
                })
                ->addColumn('fuel_type', function ($vehicle) {
                    return '<span class="badge text-bg-light border"><i class="bi bi-fuel-pump me-1"></i> ' . e($vehicle->fuel_type) . '</span>';
                })
                ->addColumn('status_badge', function ($vehicle) {
                    if ($vehicle->status === 'available') {
                        return '<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i> Tersedia</span>';
                    } elseif ($vehicle->status === 'reserved') {
                        return '<span class="badge text-bg-warning text-dark"><i class="bi bi-clock-history me-1"></i> Dipesan (Reserved)</span>';
                    } elseif ($vehicle->status === 'in_use') {
                        return '<span class="badge text-bg-primary"><i class="bi bi-arrow-repeat me-1"></i> Sedang Digunakan</span>';
                    }
                    return '<span class="badge text-bg-danger"><i class="bi bi-tools me-1"></i> Dalam Service</span>';
                })
                ->addColumn('action', function ($vehicle) use ($isAdmin) {
                    if (!$isAdmin) {
                        return '<span class="badge text-bg-light border text-secondary"><i class="bi bi-eye me-1"></i> Read Only</span>';
                    }
                    return '
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-edit" data-id="' . $vehicle->id . '" title="Edit Kendaraan">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete" data-id="' . $vehicle->id . '" data-name="' . e($vehicle->name) . ' (' . e($vehicle->license_plate) . ')" title="Hapus Kendaraan">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    ';
                })
                ->filterColumn('location_name', function ($query, $keyword) {
                    $query->whereHas('location', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('ownership_badge', function ($query, $keyword) {
                    $query->where('ownership', 'like', "%{$keyword}%")
                          ->orWhereHas('rentalCompany', function ($q) use ($keyword) {
                              $q->where('name', 'like', "%{$keyword}%");
                          });
                })
                ->rawColumns(['name_plate', 'type_badge', 'ownership_badge', 'location_name', 'fuel_type', 'status_badge', 'action'])
                ->make(true);
        }

        $locations = Location::orderBy('name')->get();
        $rentalCompanies = RentalCompany::orderBy('name')->get();

        return view('master.vehicles.index', compact('locations', 'rentalCompanies'));
    }

    /**
     * Store a newly created vehicle in storage (Admin Only).
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
            'license_plate' => ['required', 'string', 'max:50', 'unique:vehicles,license_plate'],
            'type' => ['required', Rule::in(['passenger', 'cargo'])],
            'ownership' => ['required', Rule::in(['company', 'rented'])],
            'rental_company_id' => ['nullable', 'required_if:ownership,rented', 'exists:rental_companies,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'fuel_type' => ['required', 'string', 'max:50'],
        ], [
            'name.required' => 'Nama kendaraan wajib diisi.',
            'license_plate.required' => 'Plat nomor wajib diisi.',
            'license_plate.unique' => 'Plat nomor sudah terdaftar pada sistem.',
            'type.required' => 'Jenis kendaraan wajib dipilih.',
            'ownership.required' => 'Kepemilikan kendaraan wajib dipilih.',
            'rental_company_id.required_if' => 'Perusahaan sewa wajib dipilih jika kendaraan berstatus sewa.',
            'location_id.required' => 'Lokasi pool kendaraan wajib dipilih.',
            'fuel_type.required' => 'Jenis BBM wajib diisi.',
        ]);

        $validated['status'] = 'available';

        if ($validated['ownership'] === 'company') {
            $validated['rental_company_id'] = null;
        }

        $vehicle = Vehicle::create($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'CREATE_VEHICLE',
            'entity_type' => 'Vehicle',
            'entity_id' => $vehicle->id,
            'description' => 'Menambahkan data kendaraan baru: ' . $vehicle->name . ' (' . $vehicle->license_plate . ')',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data kendaraan berhasil ditambahkan.',
                'data' => $vehicle,
            ]);
        }

        return redirect()->route('vehicles.index')->with('success', 'Data kendaraan berhasil ditambahkan.');
    }

    /**
     * Display the specified vehicle data (JSON for edit modal).
     */
    public function show(Vehicle $vehicle)
    {
        return response()->json($vehicle->load(['location', 'rentalCompany']));
    }

    /**
     * Update the specified vehicle in storage (Admin Only).
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat mengubah data master.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'license_plate' => ['required', 'string', 'max:50', Rule::unique('vehicles', 'license_plate')->ignore($vehicle->id)],
            'type' => ['required', Rule::in(['passenger', 'cargo'])],
            'ownership' => ['required', Rule::in(['company', 'rented'])],
            'rental_company_id' => ['nullable', 'required_if:ownership,rented', 'exists:rental_companies,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'fuel_type' => ['required', 'string', 'max:50'],
        ], [
            'name.required' => 'Nama kendaraan wajib diisi.',
            'license_plate.required' => 'Plat nomor wajib diisi.',
            'license_plate.unique' => 'Plat nomor sudah terdaftar pada sistem.',
            'type.required' => 'Jenis kendaraan wajib dipilih.',
            'ownership.required' => 'Kepemilikan kendaraan wajib dipilih.',
            'rental_company_id.required_if' => 'Perusahaan sewa wajib dipilih jika kendaraan berstatus sewa.',
            'location_id.required' => 'Lokasi pool kendaraan wajib dipilih.',
            'fuel_type.required' => 'Jenis BBM wajib diisi.',
        ]);

        if ($validated['ownership'] === 'company') {
            $validated['rental_company_id'] = null;
        }

        $vehicle->update($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'UPDATE_VEHICLE',
            'entity_type' => 'Vehicle',
            'entity_id' => $vehicle->id,
            'description' => 'Mengubah data kendaraan: ' . $vehicle->name . ' (' . $vehicle->license_plate . ')',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data kendaraan berhasil diperbarui.',
                'data' => $vehicle,
            ]);
        }

        return redirect()->route('vehicles.index')->with('success', 'Data kendaraan berhasil diperbarui.');
    }

    /**
     * Remove the specified vehicle from storage (Admin Only).
     */
    public function destroy(Request $request, Vehicle $vehicle)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat menghapus data master.',
            ], 403);
        }

        if ($vehicle->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus! Kendaraan "' . $vehicle->name . '" (' . $vehicle->license_plate . ') tidak dapat dihapus karena berstatus ' . strtoupper($vehicle->status) . ' (hanya armada berstatus Tersedia yang dapat dihapus).',
            ], 422);
        }

        $vehicleName = $vehicle->name;
        $vehiclePlate = $vehicle->license_plate;
        $vehicleId = $vehicle->id;

        $vehicle->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'DELETE_VEHICLE',
            'entity_type' => 'Vehicle',
            'entity_id' => $vehicleId,
            'description' => 'Menghapus data kendaraan: ' . $vehicleName . ' (' . $vehiclePlate . ')',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data kendaraan berhasil dihapus.',
            ]);
        }

        return redirect()->route('vehicles.index')->with('success', 'Data kendaraan berhasil dihapus.');
    }
}
