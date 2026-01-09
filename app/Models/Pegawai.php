<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pegawai extends Model
{
    use HasFactory;

    protected $table = 'pegawai';

    protected $fillable = [
        'rfid_uid',
        'nama_pegawai',
    ];


    public function absensiPiket()
    {
        return $this->hasMany(AbsensiPiket::class, 'pegawai_id');
    }
}
