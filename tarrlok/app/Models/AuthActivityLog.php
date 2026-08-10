<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthActivityLog extends Model
{
    public const AUTH_EVENTS = ['login', 'logout', 'login_failed'];

    protected $fillable = [
        'user_id',
        'actor_name',
        'email',
        'role',
        'hospital_id',
        'event',
        'summary',
        'ip_address',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function eventLabel(): string
    {
        return match ($this->event) {
            'login' => 'Login',
            'logout' => 'Logout',
            'login_failed' => 'Login failed',
            'action' => 'Action',
            default => ucfirst(str_replace('_', ' ', (string) $this->event)),
        };
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Platform admin',
            'hospital' => 'Hospital',
            'lab' => 'Lab',
            default => $this->role ? ucfirst($this->role) : '—',
        };
    }

    public function isAuthEvent(): bool
    {
        return in_array($this->event, self::AUTH_EVENTS, true);
    }

    public static function record(string $event, Request $request, ?User $user = null, ?string $email = null, ?string $summary = null): self
    {
        $user?->loadMissing('hospital');

        $agent = $request->userAgent();

        return static::create([
            'user_id' => $user?->id,
            'actor_name' => $user?->name,
            'email' => $user?->email ?? ($email ? strtolower(trim($email)) : null),
            'role' => $user?->role,
            'hospital_id' => $user?->hospital_id,
            'event' => $event,
            'summary' => $summary ? Str::limit($summary, 500, '') : null,
            'ip_address' => $request->ip(),
            'user_agent' => $agent ? Str::limit($agent, 500, '') : null,
        ]);
    }

    public static function recordAction(Request $request, ?User $user, string $summary): self
    {
        return static::record('action', $request, $user, null, $summary);
    }
}
