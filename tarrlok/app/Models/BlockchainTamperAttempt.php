<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockchainTamperAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'actor_name',
        'role',
        'hospital_id',
        'action',
        'unit_code',
        'reason',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'registerUnit' => 'Register unit',
            'recordScreening' => 'Record screening',
            'recordIssue' => 'Issue unit',
            default => $this->action,
        };
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Admin',
            'hospital' => 'Hospital staff',
            'lab' => 'Lab staff',
            default => ucfirst((string) $this->role),
        };
    }
}
