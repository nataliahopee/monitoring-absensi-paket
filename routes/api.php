<?php 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AbsensiTapController;
use App\Http\Controllers\RfidUnregisteredController;

Route::post('/absensi/tap', [AbsensiTapController::class, 'tap']);

Route::get('/rfid-unregistered/pending', [RfidUnregisteredController::class, 'pending']);
Route::post('/rfid-unregistered/register', [RfidUnregisteredController::class, 'register']);
Route::delete('/rfid-unregistered/{id}', [RfidUnregisteredController::class, 'destroy']);