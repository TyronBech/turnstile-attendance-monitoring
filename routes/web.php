<?php

use App\Enums\Permission;
use App\Http\Controllers\AttendanceDisplayController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Import\FacultyStaffImportController;
use App\Http\Controllers\Import\StudentImportController;
use App\Http\Controllers\Maintenance\UserMaintenanceController;
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

Route::middleware(['auth', 'verified', 'permission:'.Permission::ImportUsers->value])->group(function (): void {
    Route::get('imports/students', [StudentImportController::class, 'index'])->name('imports.students.index');
    Route::post('imports/students', [StudentImportController::class, 'store'])->name('imports.students.store');
    Route::get('imports/students/template', [StudentImportController::class, 'downloadTemplate'])->name('imports.students.template');
    Route::get('imports/students/progress/{importLog}', [StudentImportController::class, 'progress'])->name('imports.students.progress');

    Route::get('imports/faculties-staffs', [FacultyStaffImportController::class, 'index'])->name('imports.faculties-staffs.index');
    Route::post('imports/faculties-staffs', [FacultyStaffImportController::class, 'store'])->name('imports.faculties-staffs.store');
    Route::get('imports/faculties-staffs/template', [FacultyStaffImportController::class, 'downloadTemplate'])->name('imports.faculties-staffs.template');
    Route::get('imports/faculties-staffs/progress/{importLog}', [FacultyStaffImportController::class, 'progress'])->name('imports.faculties-staffs.progress');
});

Route::middleware(['auth', 'verified', 'permission:'.Permission::ViewUsers->value])->group(function (): void {
    Route::get('user-maintenance/users', [UserMaintenanceController::class, 'index'])->name('user-maintenance.users.index');
    Route::get('user-maintenance/users/{user}', [UserMaintenanceController::class, 'show'])->name('user-maintenance.users.show');
    Route::post('user-maintenance/users', [UserMaintenanceController::class, 'store'])
        ->middleware('permission:'.Permission::CreateUser->value)
        ->name('user-maintenance.users.store');
    Route::put('user-maintenance/users/{user}', [UserMaintenanceController::class, 'update'])
        ->middleware('permission:'.Permission::EditUser->value)
        ->name('user-maintenance.users.update');
    Route::delete('user-maintenance/users/{user}', [UserMaintenanceController::class, 'destroy'])
        ->middleware('permission:'.Permission::DeleteUser->value)
        ->name('user-maintenance.users.destroy');
});

require __DIR__.'/settings.php';
