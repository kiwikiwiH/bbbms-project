<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'hospital_id',
    'job_title',
    'role',
    'status',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isHospital(): bool
    {
        return $this->role === 'hospital';
    }

    public function isLab(): bool
    {
        return $this->role === 'lab';
    }

    public function isDonor(): bool
    {
        return $this->role === 'donor';
    }

    public function donor(): HasOne
    {
        return $this->hasOne(Donor::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
