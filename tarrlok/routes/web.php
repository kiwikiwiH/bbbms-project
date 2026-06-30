<?php

use App\Http\Controllers\BloodUnitTraceController;
use App\Http\Controllers\Admin\BlockchainController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\RegistrationReviewController;
use App\Http\Controllers\Hospital\BloodInventoryController;
use App\Http\Controllers\Hospital\BloodRequestController;
use App\Http\Controllers\Hospital\DashboardController as HospitalDashboardController;
use App\Http\Controllers\Hospital\LabStaffController;
use App\Http\Controllers\Hospital\PartnerExchangeController;
use App\Http\Controllers\Hospital\PlaceholderController as HospitalPlaceholderController;
use App\Http\Controllers\Lab\BloodScreeningController;
use App\Http\Controllers\Lab\BloodUnitController as LabBloodUnitController;
use App\Http\Controllers\Lab\DashboardController as LabDashboardController;
use App\Http\Controllers\Lab\DonorLookupController;
use App\Http\Controllers\DonationTrackController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();

    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->isLab()) {
        return redirect()->route('lab.dashboard');
    }

    return redirect()->route('hospital.dashboard');
});

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->isLab()) {
        return redirect()->route('lab.dashboard');
    }

    return redirect()->route('hospital.dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/track', [DonationTrackController::class, 'index'])->name('track.index');
Route::post('/track', [DonationTrackController::class, 'lookup'])->name('track.lookup');
Route::get('/track/{bloodUnit}', [DonationTrackController::class, 'show'])->name('track.show');

Route::middleware(['auth', 'hospital'])->prefix('hospital')->name('hospital.')->group(function () {
    Route::get('/', HospitalDashboardController::class)->name('dashboard');
    Route::get('/inventory', [BloodInventoryController::class, 'index'])->name('inventory');
    Route::get('/requests', [BloodRequestController::class, 'index'])->name('requests');
    Route::get('/requests/create', [BloodRequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [BloodRequestController::class, 'store'])->name('requests.store');
    Route::post('/requests/{bloodRequest}/approve', [BloodRequestController::class, 'approve'])->name('requests.approve');
    Route::post('/requests/{bloodRequest}/reject', [BloodRequestController::class, 'reject'])->name('requests.reject');
    Route::post('/requests/{bloodRequest}/issue', [BloodRequestController::class, 'issue'])->name('requests.issue');
    Route::get('/partners', [PartnerExchangeController::class, 'index'])->name('partners');
    Route::get('/trace', [BloodUnitTraceController::class, 'index'])->name('trace');
    Route::get('/trace/{bloodUnit}', [BloodUnitTraceController::class, 'show'])->name('trace.show');
    Route::get('/facility', [HospitalPlaceholderController::class, 'facility'])->name('facility');
    Route::get('/lab-staff', [LabStaffController::class, 'index'])->name('lab-staff.index');
    Route::get('/lab-staff/create', [LabStaffController::class, 'create'])->name('lab-staff.create');
    Route::post('/lab-staff', [LabStaffController::class, 'store'])->name('lab-staff.store');
    Route::post('/lab-staff/{user}/toggle-status', [LabStaffController::class, 'toggleStatus'])
        ->name('lab-staff.toggle')
        ->whereNumber('user');
    Route::get('/lab-staff/{user}/edit', [LabStaffController::class, 'edit'])->name('lab-staff.edit')->whereNumber('user');
    Route::patch('/lab-staff/{user}', [LabStaffController::class, 'update'])->name('lab-staff.update')->whereNumber('user');
});

Route::middleware(['auth', 'lab'])->prefix('lab')->name('lab.')->group(function () {
    Route::get('/', LabDashboardController::class)->name('dashboard');
    Route::get('/units', [LabBloodUnitController::class, 'index'])->name('units.index');
    Route::get('/units/create', [LabBloodUnitController::class, 'create'])->name('units.create');
    Route::post('/units', [LabBloodUnitController::class, 'store'])->name('units.store');
    Route::get('/units/{bloodUnit}/slip', [LabBloodUnitController::class, 'slip'])->name('units.slip');
    Route::get('/units/{bloodUnit}/screening', [BloodScreeningController::class, 'show'])->name('units.screening.show');
    Route::post('/units/{bloodUnit}/screening', [BloodScreeningController::class, 'update'])->name('units.screening.update');
    Route::get('/trace', [BloodUnitTraceController::class, 'index'])->name('trace');
    Route::get('/trace/{bloodUnit}', [BloodUnitTraceController::class, 'show'])->name('trace.show');
    Route::get('/donors/lookup', DonorLookupController::class)->name('donors.lookup');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/blockchain', BlockchainController::class)->name('blockchain');
    Route::get('/trace', [BloodUnitTraceController::class, 'index'])->name('trace');
    Route::get('/trace/{bloodUnit}', [BloodUnitTraceController::class, 'show'])->name('trace.show');
    Route::get('/registrations', [RegistrationReviewController::class, 'index'])->name('registrations.index');
    Route::get('/registrations/{hospital}', [RegistrationReviewController::class, 'show'])->name('registrations.show');
    Route::post('/registrations/{hospital}/approve', [RegistrationReviewController::class, 'approve'])->name('registrations.approve');
    Route::post('/registrations/{hospital}/reject', [RegistrationReviewController::class, 'reject'])->name('registrations.reject');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
