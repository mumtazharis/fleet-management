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
                ->make(true);
        }

        return view('master.rental_companies.index');
    }

    /**
     * Store a newly created rental company in storage (Admin Only).
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
     * Update the specified rental company in storage (Admin Only).
     */
    public function update(Request $request, RentalCompany $rentalCompany)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat mengubah data master.',
            ], 403);
        }

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
     * Remove the specified rental company from storage (Admin Only).
     */
    public function destroy(Request $request, RentalCompany $rentalCompany)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat menghapus data master.',
            ], 403);
        }

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
