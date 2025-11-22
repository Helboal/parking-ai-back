<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'rate_per_minute',
        'discount_percentage',
        'flat_rate_applied',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'entry_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate_per_minute' => 'decimal:2',
            'discount_percentage' => 'integer',
            'flat_rate_applied' => 'boolean',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the entry that owns the invoice.
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    /**
     * Get the invoice taxes for the invoice.
     */
    public function invoiceTaxes(): HasMany
    {
        return $this->hasMany(InvoiceTax::class);
    }
}
