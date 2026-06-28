<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BloodUnit extends Model
{
    protected $fillable = [
        'hospital_id',
        'donor_id',
        'unit_code',
        'blood_group',
        'status',
        'screening_status',
        'recorded_by',
        'collected_at',
        'expires_at',
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
            'expires_at' => 'datetime',
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

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
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
            ->where('screening_status', 'cleared')
            ->notExpired();
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
    }

    public function scopeExpiringSoon($query)
    {
        $warningDays = (int) config('tarrlok.expiry_warning_days', 7);

        return $query
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($warningDays));
    }

    public function scopePendingScreening($query)
    {
        return $query->where('screening_status', 'pending');
    }

    public function isIssuable(): bool
    {
        return $this->status === 'available'
            && $this->screening_status === 'cleared'
            && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExpiringSoon(): bool
    {
        if ($this->expires_at === null || $this->isExpired()) {
            return false;
        }

        $warningDays = (int) config('tarrlok.expiry_warning_days', 7);

        return $this->expires_at->lte(now()->addDays($warningDays));
    }

    public function daysUntilExpiry(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        return (int) now()->diffInDays($this->expires_at, false);
    }

    public static function calculateExpiresAt(\DateTimeInterface|string $collectedAt): \Illuminate\Support\Carbon
    {
        $days = (int) config('tarrlok.blood_shelf_life_days', 35);

        return \Illuminate\Support\Carbon::parse($collectedAt)->addDays($days);
    }

    public function donorStatusLabel(): string
    {
        if ($this->screening_status === 'failed' || $this->status === 'discarded') {
            return 'Did not pass screening';
        }

        if ($this->isExpired()) {
            return 'Expired';
        }

        if ($this->screening_status === 'pending') {
            return 'Awaiting lab screening';
        }

        if ($this->bloodRequests->isNotEmpty()) {
            $request = $this->bloodRequests->sortByDesc('fulfilled_at')->first();

            return 'Supplied to '.$request?->requestingHospital?->name;
        }

        return 'In stock at '.$this->hospital->name;
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
