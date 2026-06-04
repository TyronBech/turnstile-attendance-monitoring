<?php

use App\Http\Controllers\AttendanceDisplayController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome', [
    'canRegister' => false,
])->name('home');

Route::middleware(['auth'])->group(function (): void {
    Route::get('attendance-display', [AttendanceDisplayController::class, 'show'])
        ->name('attendance-display');
});

Route::middleware(['auth', 'verified', 'restrict-live-monitoring'])->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'show'])->name('dashboard');
});

require __DIR__.'/settings.php';
