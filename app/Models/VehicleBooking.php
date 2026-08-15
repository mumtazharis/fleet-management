<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_code',
        'user_id',
        'vehicle_id',
        'driver_id',
        'start_location_id',
        'destination_location_id',
        'start_address',
        'destination_address',
        'start_date',
        'end_date',
        'purpose',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id')->withTrashed();
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id')->withTrashed();
    }

    public function startLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'start_location_id')->withTrashed();
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id')->withTrashed();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(BookingApproval::class)->orderBy('approval_level', 'asc');
    }
}
