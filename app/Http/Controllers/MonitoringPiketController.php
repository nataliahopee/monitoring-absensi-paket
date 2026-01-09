<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AbsensiPiket;
use App\Models\Pegawai;

class MonitoringPiketController extends Controller
{
    /**
     * Display monitoring table.
     *
     * Query params:
     * - q => search by nama pegawai or rfid_uid (partial)
     * - date => filter by tanggal (YYYY-MM-DD)
     * - per_page => number of rows per page (5,10,15,25,50) default 10
     */
    public function index(Request $request)
    {
        $q = $request->query('q');
        $date = $request->query('date');

        // validate per_page (whitelist)
        $allowed = [5, 10, 15, 25, 50];
        $perPage = (int) $request->query('per_page', 5);
        if (! in_array($perPage, $allowed)) {
            $perPage = 5;
        }

        $query = AbsensiPiket::with('pegawai')
            ->when($date, function ($qb) use ($date) {
                $qb->whereDate('tanggal', $date);
            })
            ->when($request->filled('q'), function ($qb) use ($q) {
                $qb->whereHas('pegawai', function ($q2) use ($q) {
                    $q2->where('nama_pegawai', 'like', "%{$q}%")
                       ->orWhere('rfid_uid', 'like', "%{$q}%");
                });
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('check_in', 'desc');

        $absensis = $query->paginate($perPage)->withQueryString();

        // pass perPage too for view default selection
        return view('monitoring-absensi', compact('absensis', 'q', 'date', 'perPage'));
    }
}
