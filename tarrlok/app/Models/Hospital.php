<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hospital extends Model
{
    protected $fillable = [
        'name',
        'type',
        'region',
        'city',
        'license_id',
        'phone',
        'email',
        'status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function primaryContact(): ?User
    {
        return $this->users()->where('role', 'hospital')->first();
    }

    public function labStaff()
    {
        return $this->users()->where('role', 'lab');
    }

    public function bloodUnits()
    {
        return $this->hasMany(BloodUnit::class);
    }

    public function incomingBloodRequests()
    {
        return $this->hasMany(BloodRequest::class, 'fulfilling_hospital_id');
    }

    public function outgoingBloodRequests()
    {
        return $this->hasMany(BloodRequest::class, 'requesting_hospital_id');
    }

    public function availableUnitsCount(?string $bloodGroup = null): int
    {
        $query = $this->bloodUnits()->available();

        if ($bloodGroup) {
            $query->where('blood_group', $bloodGroup);
        }

        return $query->count();
    }

    public function typeLabel(): string
    {
        return config('tarrlok.institution_types.'.$this->type, $this->type);
    }

    public function regionLabel(): string
    {
        return config('tarrlok.ghana_regions.'.$this->region, $this->region);
    }
}
