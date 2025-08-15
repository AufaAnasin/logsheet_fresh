<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\LogdataController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LogConfigurationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified', 'non_operator'])->group(function () {
    Route::get('/dashboard', [DepartmentController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [LogdataController::class, 'analytics'])->name('analytics');
    Route::get('/analytics/components/{areaId}/area-report', [LogdataController::class, 'generateAreaReport'])->name('generateAreaReport');
    Route::get('/analytics/components/{area}', [LogdataController::class, 'componentsInsights'])->name('components.insights');
    Route::get('/userlist', [UserController::class, 'index'])->name('users.index');
    Route::post('/userlist', [UserController::class, 'store'])->name('users.store');
    Route::put('/userlist/{id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/userlist/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/visualizeandtable/{componentId}', [LogdataController::class, 'visualizeandtable'])->name('visualizeandtable');
    
    // Visualization routes for non-operator users (SuperUser, Admin)
    Route::get('/visualization', [LogdataController::class, 'logData'])->name('visualization');
    Route::post('/visualization/data', [LogdataController::class, 'visualizationData'])->middleware('api')->name('visualization.data');

    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

    Route::post('/areas', [AreaController::class, 'store'])->name('areas.store');
    Route::put('/areas/{area}', [AreaController::class, 'update'])->name('areas.update');
    Route::delete('/areas/{area}', [AreaController::class, 'destroy'])->name('areas.destroy');

    Route::get('/log-configurations', [LogConfigurationController::class, 'index'])->name('log-configurations.index');
    Route::post('/log-configurations', [LogConfigurationController::class, 'store'])->name('log-configurations.store');
    Route::put('/log-configurations/{id}', [LogConfigurationController::class, 'update'])->name('log-configurations.update');

    Route::get('/components', function () {
        return redirect()->route('dashboard');
    })->name('components.redirect');

    Route::post('/components', [ComponentController::class, 'store'])->name('components.store');
    Route::put('/components/{component}', [ComponentController::class, 'update'])->name('components.update');
    Route::delete('/components/{component}', [ComponentController::class, 'destroy'])->name('components.destroy');
});

Route::middleware(['auth', 'operator'])->group(function () {
    Route::get('/logdata', [ComponentController::class, 'logData'])->name('logdata');
    Route::post('/logdata/store', [ComponentController::class, 'storeLog'])->name('logdata.store');
    Route::get('/graphdata', [ComponentController::class, 'graphData'])->name('graphdata');
    Route::get('/component-logs/{componentId}', [ComponentController::class, 'getComponentLogs'])->name('component.logs');
    
    // Visualization routes for operator users
    Route::get('/visualization', [LogdataController::class, 'logData'])->name('visualization.operator');
    Route::post('/visualization/data', [LogdataController::class, 'visualizationData'])->middleware('api')->name('visualization.data.operator');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';