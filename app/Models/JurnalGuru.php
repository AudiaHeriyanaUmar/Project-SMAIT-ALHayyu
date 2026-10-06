<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalGuru extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tanggal',
        'jam_ke',
        'kelas_id',
        'mata_pelajaran_id',
        'materi_pembelajaran',
        'catatan_kegiatan',
        'status_monitoring'
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiSiswa::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class);
    }
}