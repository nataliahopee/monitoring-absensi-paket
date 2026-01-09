<?php 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AbsensiTapController;

Route::post('/absensi/tap', [AbsensiTapController::class, 'tap']);

// Route::post('/ping', function () {
//     return response()->json(['pong' => true]);
// });
