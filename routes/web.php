<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\LogdataController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/dashboard', [DepartmentController::class, 'index'])
    ->middleware(['auth', 'verified', 'non_operator'])
    ->name('dashboard');

Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

Route::post('/areas', [AreaController::class, 'store'])->name('areas.store');
Route::put('/areas/{area}', [AreaController::class, 'update'])->name('areas.update');
Route::delete('/areas/{area}', [AreaController::class, 'destroy'])->name('areas.destroy');

Route::get('/components', function () {
    return redirect()->route('dashboard');
})->middleware('auth');

Route::post('/components', [ComponentController::class, 'store'])->middleware('auth');
Route::put('/components/{component}', [ComponentController::class, 'update'])->middleware('auth');
Route::delete('/components/{component}', [ComponentController::class, 'destroy'])->middleware('auth');

Route::get('/logdata', [ComponentController::class, 'logData'])->middleware('operator')->name('logdata');
Route::post('/logdata/store', [ComponentController::class, 'storeLog'])->middleware('operator')->name('logdata.store');

Route::get('/graphdata', [ComponentController::class, 'graphData'])->middleware('operator')->name('graphdata');
Route::get('/component-logs/{componentId}', [ComponentController::class, 'getComponentLogs'])->middleware('operator')->name('component.logs');


// Route::get('/user', [UserController::class, 'showUserList'])->middleware('auth')->name('user.list');
Route::get('/userlist', [UserController::class, 'index'])->name('users.index');
Route::post('/userlist', [UserController::class, 'store'])->name('users.store');
Route::put('/userlist/{id}', [UserController::class, 'update'])->name('users.update');
Route::delete('/userlist/{id}', [UserController::class, 'destroy'])->name('users.destroy');
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';