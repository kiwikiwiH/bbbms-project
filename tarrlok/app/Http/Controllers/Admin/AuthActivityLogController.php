<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthActivityLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $event = trim((string) $request->query('event', ''));
        $allowed = ['login', 'logout', 'login_failed', 'action'];

        if ($event !== '' && ! in_array($event, $allowed, true)) {
            $event = '';
        }

        $logs = AuthActivityLog::query()
            ->with(['user', 'hospital'])
            ->when($event !== '', fn ($q) => $q->where('event', $event))
            ->latest()
            ->paginate(40)
            ->withQueryString();

        return view('admin.auth-log.index', [
            'logs' => $logs,
            'event' => $event,
        ]);
    }
}
