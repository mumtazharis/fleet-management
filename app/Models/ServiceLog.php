<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'service_type',
        'description',
        'cost',
        'service_date',
        'next_service_date',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'next_service_date' => 'date',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }
}
