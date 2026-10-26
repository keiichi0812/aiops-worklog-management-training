<?php

use App\Http\Controllers\MasterController;
use App\Http\Controllers\WorkLogController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::resource('worklogs', WorkLogController::class)->except(['create', 'edit'])->parameters(['worklogs' => 'id']);
    Route::get('masters/{resource}', [MasterController::class, 'index'])->name('masters.index');
    Route::post('masters/{resource}', [MasterController::class, 'store'])->name('masters.store');
    Route::patch('masters/{resource}/{id}', [MasterController::class, 'update'])->whereNumber('id')->name('masters.update');
    Route::delete('masters/{resource}/{id}', [MasterController::class, 'destroy'])->whereNumber('id')->name('masters.destroy');
});

require __DIR__.'/settings.php';
