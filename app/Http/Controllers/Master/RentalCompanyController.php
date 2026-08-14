<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\RentalCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class RentalCompanyController extends Controller
{
    /**
     * Display a listing of rental companies (Supports Yajra DataTables Server-Side Processing).
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = RentalCompany::withCount('vehicles');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('company_name', function ($rc) {
                    return '
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-icon bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px;">
                                <i class="bi bi-building"></i>
                            </div>
                            <div>
                                <strong class="text-dark d-block">' . e($rc->name) . '</strong>
                                <small class="text-muted"><i class="bi bi-truck me-1"></i>' . $rc->vehicles_count . ' unit disewa</small>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('contact', function ($rc) {
                    if (!$rc->contact_person) {
                        return '<span class="text-muted italic">-</span>';
                    }
                    return '<span class="fw-semibold text-dark"><i class="bi bi-person me-1 text-primary"></i>' . e($rc->contact_person) . '</span>';
                })
                ->addColumn('phone_formatted', function ($rc) {
                    if (!$rc->phone) {
                        return '<span class="text-muted italic">-</span>';
                    }
                    return '<i class="bi bi-telephone text-primary me-1"></i> ' . e($rc->phone);
                })
                ->addColumn('address_formatted', function ($rc) {
                    if (!$rc->address) {
                        return '<span class="text-muted italic">-</span>';
                    }
                    return '<small class="text-secondary">' . e($rc->address) . '</small>';
                })
                ->addColumn('action', function ($rc) {
                    return '
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-edit" data-id="' . $rc->id . '" title="Edit Perusahaan Sewa">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete" data-id="' . $rc->id . '" data-name="' . e($rc->name) . '" title="Hapus Perusahaan Sewa">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['company_name', 'contact', 'phone_formatted', 'address_formatted', 'action'])
                ->make(true);
        }

        return view('master.rental_companies.index');
    }

    /**
     * Store a newly created rental company in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama perusahaan sewa wajib diisi.',
        ]);

        $rentalCompany = RentalCompany::create($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'CREATE_RENTAL_COMPANY',
            'entity_type' => 'RentalCompany',
            'entity_id' => $rentalCompany->id,
            'description' => 'Menambahkan perusahaan sewa kendaraan: ' . $rentalCompany->name,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data perusahaan sewa berhasil ditambahkan.',
                'data' => $rentalCompany,
            ]);
        }

        return redirect()->route('rental-companies.index')->with('success', 'Data perusahaan sewa berhasil ditambahkan.');
    }

    /**
     * Display the specified rental company data (JSON for edit modal).
     */
    public function show(RentalCompany $rentalCompany)
    {
        return response()->json($rentalCompany);
    }

    /**
     * Update the specified rental company in storage.
     */
    public function update(Request $request, RentalCompany $rentalCompany)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama perusahaan sewa wajib diisi.',
        ]);

        $rentalCompany->update($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'UPDATE_RENTAL_COMPANY',
            'entity_type' => 'RentalCompany',
            'entity_id' => $rentalCompany->id,
            'description' => 'Mengubah data perusahaan sewa: ' . $rentalCompany->name,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data perusahaan sewa berhasil diperbarui.',
                'data' => $rentalCompany,
            ]);
        }

        return redirect()->route('rental-companies.index')->with('success', 'Data perusahaan sewa berhasil diperbarui.');
    }

    /**
     * Remove the specified rental company from storage.
     */
    public function destroy(Request $request, RentalCompany $rentalCompany)
    {
        $companyName = $rentalCompany->name;
        $companyId = $rentalCompany->id;

        $rentalCompany->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'DELETE_RENTAL_COMPANY',
            'entity_type' => 'RentalCompany',
            'entity_id' => $companyId,
            'description' => 'Menghapus perusahaan sewa: ' . $companyName,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data perusahaan sewa berhasil dihapus.',
            ]);
        }

        return redirect()->route('rental-companies.index')->with('success', 'Data perusahaan sewa berhasil dihapus.');
    }
}
