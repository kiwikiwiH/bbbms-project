<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Donor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DonorLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $phone = Donor::normalizePhone((string) $request->query('phone', ''));

        if (strlen(preg_replace('/\D+/', '', $phone) ?? '') < 9) {
            return response()->json(['found' => false]);
        }

        $donor = Donor::query()->where('phone', $phone)->first();

        if (! $donor) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'donor' => [
                'id' => $donor->id,
                'donor_code' => $donor->donor_code,
                'name' => $donor->name,
                'phone' => $donor->phone,
                'email' => $donor->email,
                'blood_group' => $donor->blood_group,
                'eligible' => $donor->isEligibleToDonate(),
                'next_eligible' => $donor->nextEligibleDate()?->toDateString(),
            ],
        ]);
    }
}
