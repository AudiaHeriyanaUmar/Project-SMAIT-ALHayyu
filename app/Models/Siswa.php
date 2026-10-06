<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Siswa extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nisn',
        'nama_lengkap',
        'kelas_id',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiSiswa::class);
    }
}
