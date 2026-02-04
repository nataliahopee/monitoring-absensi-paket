<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Pegawai;
use App\Models\AbsensiPiket;
use App\Models\RfidUnregisteredLog;

class AbsensiTapController extends Controller
{
    public function tap(Request $request)
    {
        $data = $request->validate([
            'rfid_uid' => 'required|string',
        ]);

        $rfid = $data['rfid_uid'];
        
        $pegawai = Pegawai::where('rfid_uid', $rfid)->first();
        if (! $pegawai) {
            // upsert pending log: jika sudah ada pending, update detected_at; jika belum, insert
            $log = RfidUnregisteredLog::where('rfid_uid', $rfid)
                ->where('status', 'pending')
                ->first();

            if ($log) {
                $log->update(['detected_at' => Carbon::now()]);
            } else {
                RfidUnregisteredLog::create([
                    'rfid_uid' => $rfid,
                    'detected_at' => Carbon::now(),
                    'status' => 'pending',
                ]);
            }

            return response()->json([
                'status' => 'unregistered',
                'message' => 'Kartu belum terdaftar',
                'rfid_uid' => $rfid,
            ], 200);
        }

        $today = Carbon::now()->toDateString();

        // Use transaction to reduce race condition risk
        return DB::transaction(function () use ($pegawai, $today) {
            $absen = AbsensiPiket::where('pegawai_id', $pegawai->id)
                ->where('tanggal', $today)
                ->first();

            if (! $absen) {
                $absen = AbsensiPiket::create([
                    'pegawai_id' => $pegawai->id,
                    'tanggal' => $today,
                    'check_in' => Carbon::now(),
                ]);

                return response()->json([
                    'status' => 'check_in',
                    'message' => 'Check-in tercatat',
                    'data' => $absen->load('pegawai'),
                ], 201);
            }

            if ($absen->check_in && ! $absen->check_out) {
                $absen->check_out = Carbon::now();
                $absen->save();

                return response()->json([
                    'status' => 'check_out',
                    'message' => 'Check-out tercatat',
                    'data' => $absen->load('pegawai'),
                ], 200);
            }

            // already has check_in and check_out
            return response()->json([
            'status' => 'rejected',
            'message' => 'Sudah tercatat check-in & check-out hari ini',
            ], 409);
        });
    }
}
