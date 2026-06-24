<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHospital
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isHospital() || ! $user->isActive()) {
            abort(403, 'Hospital administrator access required.');
        }

        if (! $user->hospital || $user->hospital->status !== 'approved') {
            abort(403, 'Your facility must be approved before accessing the hospital portal.');
        }

        return $next($request);
    }
}
