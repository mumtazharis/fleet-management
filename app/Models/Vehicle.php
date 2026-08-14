<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'license_plate',
        'type',
        'ownership',
        'rental_company_id',
        'location_id',
        'status',
        'fuel_type',
    ];

    public function rentalCompany(): BelongsTo
    {
        return $this->belongsTo(RentalCompany::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(VehicleBooking::class);
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class);
    }

    public function serviceLogs(): HasMany
    {
        return $this->hasMany(ServiceLog::class);
    }
}
