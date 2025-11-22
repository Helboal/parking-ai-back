<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Entry extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'entry_datetime',
        'exit_datetime',
        'total_minutes',
        'status',
        'entry_user_id',
        'exit_user_id',
        'branch_id',
        'vehicle_id',
        'license_plate',
        'entry_type_id',
        'subscription_id',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_datetime' => 'datetime',
            'exit_datetime' => 'datetime',
            'total_minutes' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user who registered the entry.
     */
    public function entryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entry_user_id');
    }

    /**
     * Get the user who registered the exit.
     */
    public function exitUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exit_user_id');
    }

    /**
     * Get the branch that owns the entry.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the vehicle that owns the entry.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the entry type that owns the entry.
     */
    public function entryType(): BelongsTo
    {
        return $this->belongsTo(EntryType::class);
    }

    /**
     * Get the subscription that owns the entry.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the invoice generated for this entry.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}
