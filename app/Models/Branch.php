<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'email',
        'opening_time',
        'closing_time',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_time' => 'datetime:H:i',
            'closing_time' => 'datetime:H:i',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the users assigned to this branch.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_branches')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get the parking capacities for this branch.
     */
    public function parkingCapacities()
    {
        return $this->hasMany(BranchParkingCapacity::class);
    }

    /**
     * Get the rates for this branch.
     */
    public function rates()
    {
        return $this->hasMany(BranchRate::class);
    }

    /**
     * Get the discounts for this branch.
     */
    public function discounts()
    {
        return $this->hasMany(BranchDiscount::class);
    }

    /**
     * Get the flat rates for this branch.
     */
    public function flatRates()
    {
        return $this->hasMany(BranchFlatRate::class);
    }

    /**
     * Get the taxes configured for this branch.
     */
    public function taxes()
    {
        return $this->belongsToMany(Tax::class, 'branch_taxes')
            ->withPivot('is_active')
            ->withTimestamps();
    }
}
