<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartnerExchangeController extends Controller
{
    public function index(Request $request): View
    {
        $hospital = auth()->user()->hospital;
        $search = trim((string) $request->query('q', ''));

        $partners = Hospital::query()
            ->where('status', 'approved')
            ->where('id', '!=', $hospital->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        return view('hospital.partners.index', [
            'hospital' => $hospital,
            'partners' => $partners,
            'search' => $search,
        ]);
    }
}
