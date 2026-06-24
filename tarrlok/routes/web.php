<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RegistrationReviewController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    if (auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
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
