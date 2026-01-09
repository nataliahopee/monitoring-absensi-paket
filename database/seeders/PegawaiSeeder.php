<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Pegawai;

class PegawaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['rfid_uid' => 'RFID001', 'nama_pegawai' => 'Rizki Santoso'],
            ['rfid_uid' => 'RFID002', 'nama_pegawai' => 'Siti Aminah'],
            ['rfid_uid' => 'RFID003', 'nama_pegawai' => 'Andi Putra'],
        ];


        foreach ($data as $row) {
            Pegawai::updateOrCreate(
                ['rfid_uid' => $row['rfid_uid']],
                ['nama_pegawai' => $row['nama_pegawai']]
            );
        }
    }
}
