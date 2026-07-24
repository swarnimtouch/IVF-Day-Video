<?php

use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VideoController::class, 'create'])->name('video.form');
Route::post('/generate-video', [VideoController::class, 'store'])->name('video.generate');
