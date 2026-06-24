<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLab
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isLab() || ! $user->isActive()) {
            abort(403, 'Lab staff access required.');
        }

        if (! $user->hospital || $user->hospital->status !== 'approved') {
            abort(403, 'Your facility must be approved before accessing the lab portal.');
        }

        return $next($request);
    }
}
