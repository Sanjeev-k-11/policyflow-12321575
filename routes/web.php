<?php

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\PolicyRequestViewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::prefix('requests')->group(function () {
    Route::get('/', [PolicyRequestViewController::class, 'index'])->name('requests.index');
    Route::get('/create', [PolicyRequestViewController::class, 'create'])->name('requests.create');
    Route::get('/{id}', [PolicyRequestViewController::class, 'show'])->name('requests.show');
});

Route::get('/mock-lab', [PolicyRequestViewController::class, 'mockLab'])->name('mock-lab');
