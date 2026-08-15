<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\FuelLog;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class FuelLogController extends Controller
{
    /**
     * Display a listing of fuel logs (Supports Yajra DataTables Server-Side Processing).
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = FuelLog::with(['vehicle', 'creator'])->select('fuel_logs.*');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('formatted_date', function ($log) {
                    return $log->date ? $log->date->format('d/m/Y') : '-';
                })
                ->addColumn('vehicle_info', function ($log) {
                    $vName = $log->vehicle ? e($log->vehicle->name) : 'Kendaraan Terhapus';
                    $plate = $log->vehicle ? e($log->vehicle->license_plate) : '-';
                    $fuelType = $log->vehicle ? e($log->vehicle->fuel_type) : '-';
                    return '
                        <div class="lh-sm">
                            <strong class="d-block text-dark"><i class="bi bi-truck text-warning me-1"></i>' . $vName . ' (' . $plate . ')</strong>
                            <small class="text-muted"><i class="bi bi-fuel-pump me-1"></i>BBM: ' . $fuelType . '</small>
                        </div>
                    ';
                })
                ->addColumn('fuel_amount_formatted', function ($log) {
                    return '<span class="fw-bold text-dark">' . number_format($log->fuel_amount, 2, ',', '.') . ' Liter</span>';
                })
                ->addColumn('cost_info', function ($log) {
                    $priceStr = 'Rp ' . number_format($log->fuel_price, 0, ',', '.');
                    $totalStr = 'Rp ' . number_format($log->total_cost, 0, ',', '.');
                    return '
                        <div class="lh-sm">
                            <strong class="text-success d-block">' . $totalStr . '</strong>
                            <small class="text-muted">(' . $priceStr . ' / Liter)</small>
                        </div>
                    ';
                })
                ->addColumn('odometer_formatted', function ($log) {
                    return $log->odometer_reading ? number_format($log->odometer_reading, 0, ',', '.') . ' KM' : '-';
                })
                ->addColumn('creator_name', function ($log) {
                    return $log->creator ? e($log->creator->name) : 'Sistem';
                })
                ->addColumn('action', function ($log) {
                    $isAdmin = Auth::user()->role?->name === 'admin';
                    
                    $btnDetail = '
                        <button type="button" class="btn btn-outline-info btn-sm btn-detail me-1" data-id="' . $log->id . '" title="Rincian Pengisian">
                            <i class="bi bi-eye"></i> Detail
                        </button>
                    ';

                    if (!$isAdmin) {
                        return $btnDetail;
                    }

                    return '
                        <div class="btn-group btn-group-sm">
                            ' . $btnDetail . '
                            <button type="button" class="btn btn-outline-warning btn-sm btn-edit me-1" data-id="' . $log->id . '" title="Edit Pencatatan">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm btn-delete" data-id="' . $log->id . '" data-vehicle="' . e($log->vehicle?->name ?? 'Kendaraan') . '" title="Hapus Pencatatan">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['vehicle_info', 'fuel_amount_formatted', 'cost_info', 'action'])
                ->make(true);
        }

        // Summary Stats for Top Panel
        $totalCost = FuelLog::sum('total_cost');
        $totalLiters = FuelLog::sum('fuel_amount');
        $totalTransactions = FuelLog::count();

        // Vehicles for modal select dropdown (active vehicles)
        $vehicles = Vehicle::orderBy('name')->get();

        return view('monitoring.fuel_logs.index', compact('vehicles', 'totalCost', 'totalLiters', 'totalTransactions'));
    }

    /**
     * Store a newly created fuel log.
     */
    public function store(Request $request)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat mencatat pengisian BBM.',
            ], 403);
        }

        $validated = $request->validate([
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')->whereNull('deleted_at')],
            'date' => ['required', 'date'],
            'fuel_amount' => ['required', 'numeric', 'min:0.01'],
            'fuel_price' => ['required', 'numeric', 'min:0'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'odometer_reading' => ['required', 'integer', 'min:0'],
        ], [
            'vehicle_id.required' => 'Pilih kendaraan operasional.',
            'date.required' => 'Tanggal pengisian wajib diisi.',
            'fuel_amount.required' => 'Jumlah liter BBM wajib diisi.',
            'fuel_price.required' => 'Harga per liter BBM wajib diisi.',
            'odometer_reading.required' => 'Angka odometer (KM) saat pengisian BBM wajib diisi.',
            'odometer_reading.integer' => 'Angka odometer (KM) harus berupa angka bulat positif.',
        ]);

        $fuelAmount = (float) $validated['fuel_amount'];
        $fuelPrice = (float) $validated['fuel_price'];
        $totalCost = isset($validated['total_cost']) && $validated['total_cost'] > 0
            ? (float) $validated['total_cost']
            : ($fuelAmount * $fuelPrice);

        DB::beginTransaction();
        try {
            $fuelLog = FuelLog::create([
                'vehicle_id' => $validated['vehicle_id'],
                'date' => $validated['date'],
                'fuel_amount' => $fuelAmount,
                'fuel_price' => $fuelPrice,
                'total_cost' => $totalCost,
                'odometer_reading' => $validated['odometer_reading'] ?? null,
                'created_by' => Auth::id(),
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'INPUT_FUEL_LOG',
                'entity_type' => 'FuelLog',
                'entity_id' => $fuelLog->id,
                'description' => 'Mencatat pengisian BBM ' . number_format($fuelAmount, 2) . ' Liter untuk kendaraan ID ' . $validated['vehicle_id'],
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pencatatan konsumsi BBM berhasil disimpan.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan pencatatan BBM: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified fuel log.
     */
    public function show($id)
    {
        $fuelLog = FuelLog::with(['vehicle.location', 'creator'])->findOrFail($id);
        return response()->json($fuelLog);
    }

    /**
     * Update the specified fuel log.
     */
    public function update(Request $request, FuelLog $fuelLog)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $validated = $request->validate([
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')->whereNull('deleted_at')],
            'date' => ['required', 'date'],
            'fuel_amount' => ['required', 'numeric', 'min:0.01'],
            'fuel_price' => ['required', 'numeric', 'min:0'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'odometer_reading' => ['required', 'integer', 'min:0'],
        ], [
            'vehicle_id.required' => 'Pilih kendaraan operasional.',
            'date.required' => 'Tanggal pengisian wajib diisi.',
            'fuel_amount.required' => 'Jumlah liter BBM wajib diisi.',
            'fuel_price.required' => 'Harga per liter BBM wajib diisi.',
            'odometer_reading.required' => 'Angka odometer (KM) saat pengisian BBM wajib diisi.',
            'odometer_reading.integer' => 'Angka odometer (KM) harus berupa angka bulat positif.',
        ]);

        $fuelAmount = (float) $validated['fuel_amount'];
        $fuelPrice = (float) $validated['fuel_price'];
        $totalCost = isset($validated['total_cost']) && $validated['total_cost'] > 0
            ? (float) $validated['total_cost']
            : ($fuelAmount * $fuelPrice);

        DB::beginTransaction();
        try {
            $fuelLog->update([
                'vehicle_id' => $validated['vehicle_id'],
                'date' => $validated['date'],
                'fuel_amount' => $fuelAmount,
                'fuel_price' => $fuelPrice,
                'total_cost' => $totalCost,
                'odometer_reading' => $validated['odometer_reading'] ?? null,
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'UPDATE_FUEL_LOG',
                'entity_type' => 'FuelLog',
                'entity_id' => $fuelLog->id,
                'description' => 'Memperbarui data pengisian BBM (ID ' . $fuelLog->id . ')',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pencatatan konsumsi BBM berhasil diperbarui.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data BBM: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified fuel log.
     */
    public function destroy(Request $request, $id)
    {
        if (Auth::user()->role?->name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Administrator yang dapat menghapus pencatatan BBM.',
            ], 403);
        }

        $fuelLog = FuelLog::findOrFail($id);

        DB::beginTransaction();
        try {
            $fuelLog->delete();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'DELETE_FUEL_LOG',
                'entity_type' => 'FuelLog',
                'entity_id' => $id,
                'description' => 'Menghapus data pencatatan pengisian BBM (ID ' . $id . ')',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pencatatan konsumsi BBM berhasil dihapus.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pencatatan BBM.',
            ], 500);
        }
    }
}
