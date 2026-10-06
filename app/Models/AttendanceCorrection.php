<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    protected $fillable = [
        'absensi_siswa_id',
        'admin_id',
        'admin_name',
        'status_sebelumnya',
        'status_baru',
        'keterangan_sebelumnya',
        'keterangan_baru',
        'alasan',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(AbsensiSiswa::class, 'absensi_siswa_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id')->withTrashed();
    }
}
