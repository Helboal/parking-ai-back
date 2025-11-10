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
        'address',
        'phone',
        'total_spaces',
        'available_spaces',
        'is_active',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_spaces' => 'integer',
            'available_spaces' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user that manages the branch.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
