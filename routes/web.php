<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MonitoringPiketController;
use App\Http\Controllers\RfidUnregisteredController;


// Route::get('/', function () {
//     return view('monitoring-absensi');
// });

Route::get('/', [MonitoringPiketController::class, 'index'])->name('monitoring.index');
Route::get('/rfid-unregistered', [RfidUnregisteredController::class, 'index'])->name('rfid.unregistered.index');
