<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BloodRequest extends Model
{
    protected $fillable = [
        'request_code',
        'requesting_hospital_id',
        'fulfilling_hospital_id',
        'blood_group',
        'quantity',
        'urgency',
        'status',
        'rejection_reason',
        'fulfilled_at',
    ];

    protected function casts(): array
    {
        return [
            'fulfilled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BloodRequest $request) {
            if (! $request->request_code) {
                $request->request_code = 'REQ-'.str_pad((string) (static::max('id') + 1), 4, '0', STR_PAD_LEFT).'-'.strtoupper(substr(md5(uniqid('', true)), 0, 1));
            }
        });
    }

    public function requestingHospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'requesting_hospital_id');
    }

    public function fulfillingHospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'fulfilling_hospital_id');
    }

    public function bloodUnits(): BelongsToMany
    {
        return $this->belongsToMany(BloodUnit::class, 'blood_request_unit');
    }

    public function isActionable(): bool
    {
        return in_array($this->status, ['pending', 'approved'], true);
    }
}
