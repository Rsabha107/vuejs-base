<?php

use App\Http\Controllers\GlobalStatusController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolesPermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserExportController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('MyAuth/Login', 'MyAuth/Login')->name('mylogin');
Route::inertia('MyAuth/Register', 'MyAuth/Register')->name('myregister');
Route::inertia('MyAuth/ForgotPassword', 'MyAuth/ForgotPassword')->name('myforgotpassword');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/', function () {
        return Inertia::render('Mypage', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    })->name('home');

    Route::get('/users/export', [UserExportController::class, 'export'])->name('users.export');

    Route::get('/statuses', [GlobalStatusController::class, 'getStatuses'])->name('global.statuses.get');

    Route::controller(PermissionController::class)->group(function () {
        Route::get('/permissions', 'index')->name('permissions.index');
        Route::get('/api/permissions', 'data')->name('permissions.data');
        Route::post('/permissions', 'store')->name('permissions.store');
        Route::get('/permissions/{permission}', 'show')->name('permissions.show');
        Route::put('/permissions/{permission}', 'update')->name('permissions.update');
        Route::delete('/permissions/{permission}', 'destroy')->name('permissions.destroy');
    });

    Route::controller(RolesPermissionController::class)->group(function () {
        Route::get('/roles-permissions', 'index')->name('roles-permissions.index'); // Inertia page
        Route::get('/api/roles-permissions', 'data')->name('roles-permissions.data'); // AJAX data endpoint
    });

    Route::controller(UserController::class)->group(function () {
        Route::get('/users', 'index')->name('users.index'); // Inertia page
        Route::post('/users/table', 'table')->name('users.table');
        // Route::match(['get','post'], '/users', 'index')->name('users.index');
        // Route::get('/api/users', 'data')->name('users.data'); // AJAX data endpoint

        // Route::get('/users', 'index')->name('users.index');
        Route::post('/users', 'store')->name('users.store');
        Route::put('/users/{user}', 'update')->name('users.update');
        Route::delete('/users/{user}', 'destroy')->name('users.destroy');

    });

    Route::controller(RoleController::class)->group(function () {
        Route::get('/roles', 'index')->name('roles.index'); // Inertia page
        Route::get('/api/roles', 'data')->name('roles.data'); // AJAX data endpoint
    });

    Route::inertia('mypage', 'Mypage')->name('mypage');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
