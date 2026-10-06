<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastLog extends Model
{
    use HasFactory;

    // Field yang diizinkan untuk diisi secara massal
    protected $fillable = [
        'pendaftaran_id',
        'tipe_pesan',
        'isi_pesan',
        'status',
        'sent_at'
    ];

    // Relasi kembali ke model Pendaftaran
    public function pendaftaran()
    {
        return $this->belongsTo(Pendaftaran::class);
    }
}