<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RfidUnregisteredLog;
use App\Models\Pegawai;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RfidUnregisteredController extends Controller
{
    // API: pending summary (count + latest 5)
    public function pending()
    {
        $count = RfidUnregisteredLog::where('status', 'pending')->count();
        $latest = RfidUnregisteredLog::where('status', 'pending')
            ->orderBy('detected_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'count' => $count,
            'latest' => $latest,
        ]);
    }

public function index(Request $request)
{
    $q = $request->query('q');
    $status = $request->query('status');
    $date = $request->query('date');

    // per_page param: allowed 10,30,50,all (default 10)
    $allowed = ['10','30','50','all'];
    $perPageParam = $request->query('per_page', '10');
    if (! in_array($perPageParam, $allowed)) {
        $perPageParam = '10';
    }

    // if 'all' then set perPage to count (fallback 1)
    if ($perPageParam === 'all') {
        $totalCount = RfidUnregisteredLog::count();
        $perPage = $totalCount > 0 ? $totalCount : 1;
    } else {
        $perPage = (int) $perPageParam;
    }

    $query = RfidUnregisteredLog::query();

    if ($q) {
        $query->where('rfid_uid', 'like', "%{$q}%");
    }

    if ($status && in_array($status, ['pending','registered','ignored'])) {
        $query->where('status', $status);
    }

    if ($date) {
        // filter by detected_at date
        $query->whereDate('detected_at', $date);
    }

    // ensure pending appear first, then by detected_at desc
    // FIELD(status,'pending') returns 1 for pending, 0 otherwise; order desc -> pending first
    $query->orderByRaw("FIELD(status, 'pending') DESC")
          ->orderBy('detected_at', 'desc');

    $items = $query->paginate($perPage)->withQueryString();

    return view('rfid-unregistered', [
        'items' => $items,
        'q' => $q,
        'status' => $status,
        'date' => $date,
        'perPageParam' => $perPageParam,
    ]);
}

    // Register a pending rfid to Pegawai (POST)
    public function register(Request $request)
    {
        $data = $request->validate([
            'rfid_uid' => 'required|string',
            'nama_pegawai' => 'required|string|max:255',
        ]);

        return DB::transaction(function () use ($data) {
            $pegawai = Pegawai::create([
                'rfid_uid' => $data['rfid_uid'],
                'nama_pegawai' => $data['nama_pegawai'],
            ]);

            // mark log as registered (all pending records for that uid)
            RfidUnregisteredLog::where('rfid_uid', $data['rfid_uid'])
                ->where('status', 'pending')
                ->update([
                    'status' => 'registered',
                    'updated_at' => Carbon::now(),
                ]);

            return response()->json([
                'status' => 'ok',
                'message' => 'Pegawai berhasil didaftarkan',
                'pegawai' => $pegawai,
            ]);
        });
    }

    // Mark ignored or delete (DELETE)
    public function destroy($id)
    {
        $log = RfidUnregisteredLog::findOrFail($id);
        $log->update(['status' => 'ignored']);
        return response()->json(['status'=>'ok','message'=>'Log diabaikan']);
    }
}
