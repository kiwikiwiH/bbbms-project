<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donor extends Model
{
    protected $fillable = [
        'user_id',
        'donor_code',
        'name',
        'phone',
        'email',
        'blood_group',
        'registered_at_hospital_id',
        'status',
        'last_donation_at',
        'tracking_consent',
    ];

    protected function casts(): array
    {
        return [
            'last_donation_at' => 'datetime',
            'tracking_consent' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registeredAtHospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'registered_at_hospital_id');
    }

    public function bloodUnits(): HasMany
    {
        return $this->hasMany(BloodUnit::class);
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '233')) {
            return '+'.substr($digits, 0, 3).substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            return '+233'.substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '+233'.$digits;
        }

        return '+'.$digits;
    }

    public static function generateDonorCode(): string
    {
        $sequence = static::count() + 1;

        return 'DONOR-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    public function isEligibleToDonate(): bool
    {
        $interval = (int) config('tarrlok.min_donation_interval_days', 56);

        if (! $this->last_donation_at) {
            return true;
        }

        return $this->last_donation_at->copy()->addDays($interval)->isPast();
    }

    public function nextEligibleDate(): ?\Illuminate\Support\Carbon
    {
        if (! $this->last_donation_at) {
            return null;
        }

        return $this->last_donation_at->copy()->addDays((int) config('tarrlok.min_donation_interval_days', 56));
    }
}
