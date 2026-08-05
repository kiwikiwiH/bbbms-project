<?php

namespace App\Http\Controllers;

use App\Models\BloodUnit;
use App\Models\Hospital;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->isLab()) {
                return redirect()->route('lab.dashboard');
            }

            return redirect()->route('hospital.dashboard');
        }

        $groups = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];

        $availableByGroup = BloodUnit::query()
            ->available()
            ->selectRaw('blood_group, COUNT(*) as aggregate')
            ->groupBy('blood_group')
            ->pluck('aggregate', 'blood_group');

        $maxStock = max(1, (int) $availableByGroup->max());

        $stock = collect($groups)->map(function (string $group) use ($availableByGroup, $maxStock) {
            $count = (int) ($availableByGroup[$group] ?? 0);

            return [
                'group' => $group,
                'count' => $count,
                'percent' => (int) round(($count / $maxStock) * 100),
                'low' => $count === 0,
            ];
        });

        return view('landing', [
            'stock' => $stock,
            'unitsThisYear' => BloodUnit::query()
                ->whereYear('collected_at', now()->year)
                ->count(),
            'hospitalsOnNetwork' => Hospital::query()->where('status', 'approved')->count(),
            'availableUnits' => BloodUnit::query()->available()->count(),
            'anchoredUnits' => BloodUnit::query()
                ->where(function ($query) {
                    $query->whereNotNull('blockchain_register_tx')
                        ->orWhereNotNull('blockchain_screening_tx')
                        ->orWhereNotNull('blockchain_issue_tx');
                })
                ->count(),
        ]);
    }
}
