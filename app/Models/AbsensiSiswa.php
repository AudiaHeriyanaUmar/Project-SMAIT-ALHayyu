<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbsensiSiswa extends Model
{
    use HasFactory;

    protected $fillable = [
        'jurnal_guru_id',
        'siswa_id',
        'status',
        'keterangan',
    ];

    public function jurnal(): BelongsTo
    {
        return $this->belongsTo(JurnalGuru::class, 'jurnal_guru_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class)->withTrashed();
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
