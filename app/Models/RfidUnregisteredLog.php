<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfidUnregisteredLog extends Model
{
    use HasFactory;

    protected $table = 'rfid_unregistered_logs';

    protected $fillable = [
        'rfid_uid',
        'detected_at',
        'status',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
    ];
}
