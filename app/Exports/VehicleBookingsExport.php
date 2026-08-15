<?php

namespace App\Exports;

use App\Models\VehicleBooking;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VehicleBookingsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    private int $rowNumber = 0;

    /**
     * Get all vehicle bookings with relationships.
     */
    public function collection(): Collection
    {
        $this->rowNumber = 0;

        return VehicleBooking::withTrashed()->with([
            'user.role',
            'vehicle.location',
            'driver',
            'startLocation',
            'destinationLocation',
            'approvals.approver.role'
        ])->latest('id')->get();
    }

    /**
     * Table header definition.
     */
    public function headings(): array
    {
        return [
            'No',
            'Kode Pemesanan',
            'Tanggal Pengajuan',
            'Nama Pemohon',
            'Email Pemohon',
            'Role / Jabatan Pemohon',
            'Nama Kendaraan',
            'Nomor Plat',
            'Tipe Kendaraan',
            'Kepemilikan',
            'Jenis BBM',
            'Pool Kendaraan',
            'Nama Driver',
            'Telepon Driver',
            'Nomor SIM',
            'Lokasi Keberangkatan',
            'Lokasi Tujuan',
            'Jadwal Mulai',
            'Jadwal Selesai',
            'Keperluan / Tujuan Pemesanan',
            'Status Pemesanan',
            'Penyetuju Level 1 (Supervisor)',
            'Status Approval L1',
            'Tanggal Approval L1',
            'Catatan / Alasan L1',
            'Penyetuju Level 2 (Manager)',
            'Status Approval L2',
            'Tanggal Approval L2',
            'Catatan / Alasan L2',
        ];
    }

    /**
     * Map each booking row.
     *
     * @param VehicleBooking $booking
     */
    public function map($booking): array
    {
        $this->rowNumber++;

        $l1 = $booking->approvals->firstWhere('approval_level', 1);
        $l2 = $booking->approvals->firstWhere('approval_level', 2);

        $statusLabel = match ($booking->status) {
            'pending' => 'Menunggu Approval',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'in_progress' => 'Sedang Berjalan',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($booking->status ?? '-')
        };

        if ($booking->trashed() && $booking->status !== 'cancelled') {
            $statusLabel = 'Dibatalkan (Dihapus)';
        }

        $l1Status = $l1 ? match ($l1->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'pending' => 'Menunggu',
            default => ucfirst($l1->status)
        } : '-';

        $l2Status = $l2 ? match ($l2->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'pending' => 'Menunggu',
            default => ucfirst($l2->status)
        } : '-';

        $startLocationName = $booking->startLocation?->name ?? $booking->start_address ?? '-';
        $destinationLocationName = $booking->destinationLocation?->name ?? $booking->destination_address ?? '-';

        return [
            $this->rowNumber,
            $booking->booking_code,
            $booking->created_at ? $booking->created_at->format('d/m/Y H:i') : '-',
            $booking->user?->name ?? '-',
            $booking->user?->email ?? '-',
            $booking->user?->role?->label ?? $booking->user?->role?->name ?? '-',
            $booking->vehicle?->name ?? '-',
            $booking->vehicle?->license_plate ?? '-',
            $booking->vehicle?->type ? ucfirst(str_replace('_', ' ', $booking->vehicle->type)) : '-',
            $booking->vehicle?->ownership === 'rented' ? 'Sewa (Rental)' : 'Milik Perusahaan',
            $booking->vehicle?->fuel_type ?? '-',
            $booking->vehicle?->location?->name ?? '-',
            $booking->driver?->name ?? '-',
            $booking->driver?->phone ?? '-',
            $booking->driver?->license_number ?? '-',
            $startLocationName,
            $destinationLocationName,
            $booking->start_date ? $booking->start_date->format('d/m/Y H:i') : '-',
            $booking->end_date ? $booking->end_date->format('d/m/Y H:i') : '-',
            $booking->purpose ?? '-',
            $statusLabel,
            $l1 && $l1->approver ? $l1->approver->name . ' (' . ($l1->approver->role?->label ?? $l1->approver->role?->name ?? 'SPV') . ')' : '-',
            $l1Status,
            $l1 && $l1->responded_at ? $l1->responded_at->format('d/m/Y H:i') : '-',
            $l1?->note ?? '-',
            $l2 && $l2->approver ? $l2->approver->name . ' (' . ($l2->approver->role?->label ?? $l2->approver->role?->name ?? 'Manager') . ')' : '-',
            $l2Status,
            $l2 && $l2->responded_at ? $l2->responded_at->format('d/m/Y H:i') : '-',
            $l2?->note ?? '-',
        ];
    }

    /**
     * Excel styles.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Sheet title.
     */
    public function title(): string
    {
        return 'Data Pemesanan Kendaraan';
    }
}
