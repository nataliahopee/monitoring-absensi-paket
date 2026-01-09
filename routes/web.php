<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MonitoringPiketController;

// Route::get('/', function () {
//     return view('monitoring-absensi');
// });

Route::get('/', [MonitoringPiketController::class, 'index'])->name('monitoring.index');