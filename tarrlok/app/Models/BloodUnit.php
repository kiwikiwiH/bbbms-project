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
        'screening_status',
        'recorded_by',
        'collected_at',
        'screened_at',
        'screened_by',
        'screening_hiv',
        'screening_hep_b',
        'screening_hep_c',
        'screening_syphilis',
        'screening_notes',
        'blockchain_register_tx',
        'blockchain_screening_tx',
        'blockchain_issue_tx',
    ];

    protected function casts(): array
    {
        return [
            'collected_at' => 'datetime',
            'screened_at' => 'datetime',
            'screening_hiv' => 'boolean',
            'screening_hep_b' => 'boolean',
            'screening_hep_c' => 'boolean',
            'screening_syphilis' => 'boolean',
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

    public function screener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'screened_by');
    }

    public function bloodRequests(): BelongsToMany
    {
        return $this->belongsToMany(BloodRequest::class, 'blood_request_unit');
    }

    public function scopeAvailable($query)
    {
        return $query
            ->where('status', 'available')
            ->where('screening_status', 'cleared');
    }

    public function scopePendingScreening($query)
    {
        return $query->where('screening_status', 'pending');
    }

    public function isIssuable(): bool
    {
        return $this->status === 'available' && $this->screening_status === 'cleared';
    }

    public function screeningStatusLabel(): string
    {
        return match ($this->screening_status) {
            'cleared' => 'Cleared',
            'failed' => 'Failed',
            default => 'Pending screening',
        };
    }

    public function passedAllScreeningTests(): bool
    {
        return $this->screening_hiv
            && $this->screening_hep_b
            && $this->screening_hep_c
            && $this->screening_syphilis;
    }

    public static function generateUnitCode(int $hospitalId): string
    {
        $sequence = static::where('hospital_id', $hospitalId)->count() + 1;

        return 'UNIT-'.str_pad((string) $hospitalId, 3, '0', STR_PAD_LEFT).'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function getRouteKeyName(): string
    {
        return 'unit_code';
    }
}
