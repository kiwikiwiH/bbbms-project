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
        return redirect()->intended($this->homeRouteFor($user));
    }
}
