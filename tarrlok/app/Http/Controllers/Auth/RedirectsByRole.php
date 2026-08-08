<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

trait RedirectsByRole
{
    protected function homeRouteFor(User $user): string
    {
        if ($user->isAdmin()) {
            return route('admin.dashboard', absolute: false);
        }

        if ($user->isLab()) {
            return route('lab.dashboard', absolute: false);
        }

        return route('hospital.dashboard', absolute: false);
    }

    protected function redirectHome(User $user): RedirectResponse
    {
        $home = $this->homeRouteFor($user);
        $intended = session()->pull('url.intended');

        if (is_string($intended) && $intended !== '' && $this->intendedAllowedFor($user, $intended)) {
            return redirect()->to($intended);
        }

        return redirect()->to($home);
    }

    protected function intendedAllowedFor(User $user, string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        if (in_array($path, ['/dashboard', '/profile'], true) || str_starts_with($path, '/track')) {
            return true;
        }

        return match (true) {
            $user->isAdmin() => str_starts_with($path, '/admin'),
            $user->isLab() => str_starts_with($path, '/lab'),
            $user->isHospital() => str_starts_with($path, '/hospital'),
            default => false,
        };
    }
}
