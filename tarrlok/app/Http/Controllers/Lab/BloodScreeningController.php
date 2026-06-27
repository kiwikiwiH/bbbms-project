<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\BloodUnit;
use App\Services\BlockchainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BloodScreeningController extends Controller
{
    public function show(BloodUnit $bloodUnit): View
    {
        $this->ensureUnit($bloodUnit);

        $bloodUnit->load(['recorder', 'screener']);

        return view('lab.units.screening', [
            'unit' => $bloodUnit,
            'hospital' => auth()->user()->hospital,
            'screeningTests' => config('tarrlok.screening_tests'),
            'readOnly' => $bloodUnit->screening_status !== 'pending',
        ]);
    }

    public function update(Request $request, BloodUnit $bloodUnit): RedirectResponse
    {
        $this->ensureUnit($bloodUnit);

        if ($bloodUnit->screening_status !== 'pending') {
            return redirect()
                ->route('lab.units.screening.show', $bloodUnit)
                ->with('status', 'Screening has already been recorded for this unit.');
        }

        $validated = $request->validate([
            'action' => ['required', 'in:cleared,failed'],
            'screened_at' => ['required', 'date', 'before_or_equal:today'],
            'screening_hiv' => ['nullable', 'boolean'],
            'screening_hep_b' => ['nullable', 'boolean'],
            'screening_hep_c' => ['nullable', 'boolean'],
            'screening_syphilis' => ['nullable', 'boolean'],
            'screening_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = auth()->user();
        $tests = [
            'screening_hiv' => $request->boolean('screening_hiv'),
            'screening_hep_b' => $request->boolean('screening_hep_b'),
            'screening_hep_c' => $request->boolean('screening_hep_c'),
            'screening_syphilis' => $request->boolean('screening_syphilis'),
        ];

        if ($validated['action'] === 'cleared') {
            if (! ($tests['screening_hiv'] && $tests['screening_hep_b'] && $tests['screening_hep_c'] && $tests['screening_syphilis'])) {
                return back()
                    ->withInput()
                    ->withErrors(['screening' => 'All four screening tests must be marked non-reactive to clear a unit for inventory.']);
            }

            $bloodUnit->update([
                ...$tests,
                'screening_status' => 'cleared',
                'status' => 'available',
                'screened_at' => $validated['screened_at'],
                'screened_by' => $user->id,
                'screening_notes' => $validated['screening_notes'] ?? null,
            ]);

            $txHash = app(BlockchainService::class)->recordScreening($bloodUnit->unit_code, 'cleared');
            if ($txHash) {
                $bloodUnit->update(['blockchain_screening_tx' => $txHash]);
            }

            return redirect()
                ->route('lab.units.index')
                ->with('status', $bloodUnit->unit_code.' cleared for inventory — all screening non-reactive.');
        }

        $bloodUnit->update([
            ...$tests,
            'screening_status' => 'failed',
            'status' => 'discarded',
            'screened_at' => $validated['screened_at'],
            'screened_by' => $user->id,
            'screening_notes' => $validated['screening_notes'] ?? 'Unit failed screening and was discarded.',
        ]);

        $txHash = app(BlockchainService::class)->recordScreening($bloodUnit->unit_code, 'failed');
        if ($txHash) {
            $bloodUnit->update(['blockchain_screening_tx' => $txHash]);
        }

        return redirect()
            ->route('lab.units.index')
            ->with('status', $bloodUnit->unit_code.' marked as failed screening and discarded.');
    }

    private function ensureUnit(BloodUnit $bloodUnit): void
    {
        abort_unless(
            $bloodUnit->hospital_id === auth()->user()->hospital_id,
            403
        );
    }
}
