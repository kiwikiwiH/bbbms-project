<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BloodUnit extends Model
{
    protected $fillable = [
        'hospital_id',
        'unit_code',
        'blood_group',
        'status',
        'recorded_by',
        'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'collected_at' => 'datetime',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function bloodRequests(): BelongsToMany
    {
        return $this->belongsToMany(BloodRequest::class, 'blood_request_unit');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public static function generateUnitCode(int $hospitalId): string
    {
        $sequence = static::where('hospital_id', $hospitalId)->count() + 1;

        return 'UNIT-'.str_pad((string) $hospitalId, 3, '0', STR_PAD_LEFT).'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
