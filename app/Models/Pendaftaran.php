<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pendaftaran extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_pendaftaran',
        'nama_lengkap',
        'email',
        'no_whatsapp',
        'asal_sekolah',
        'jurusan_pilihan',
        'status_pendaftaran'
    ];

    public function broadcastLogs()
    {
        return $this->hasMany(BroadcastLog::class);
    }

    public function user() 
    {
        return $this->belongsTo(User::class);
    }
}
