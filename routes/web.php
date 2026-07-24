<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VideoController::class, 'create'])->name('video.form');
Route::post('/generate-video', [VideoController::class, 'store'])->name('video.generate');
Route::get('/video/{user}/{token}', [VideoController::class, 'download'])->name('video.download');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('login');
    Route::post('/login', [AdminController::class, 'authenticate'])->name('authenticate');
    Route::middleware('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/submissions', [AdminController::class, 'submissions'])->name('submissions');
        Route::get('/export', [AdminController::class, 'export'])->name('export');
        Route::get('/submissions/{user}/download', [AdminController::class, 'download'])->name('download');
        Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
    });
});
