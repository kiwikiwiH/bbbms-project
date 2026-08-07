<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isActive()) {
            if ($user->isLab()) {
                return redirect()->route('lab.dashboard');
            }

            if ($user->isHospital()) {
                return redirect()->route('hospital.dashboard');
            }
        }

        if (! $user || ! $user->isAdmin() || ! $user->isActive()) {
            abort(403, 'Platform administrator access required.');
        }

        return $next($request);
    }
}
