<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AbsensiPiket extends Model
{
    use HasFactory;

    protected $table = 'absensi_piket';

    protected $fillable = [
        'pegawai_id',
        'tanggal',
        'check_in',
        'check_out',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }
}
