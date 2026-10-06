<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiSiswa extends Model
{
    use HasFactory;

    protected $fillable = [
        'jurnal_guru_id',
        'nama_siswa',
        'status',
        'keterangan'
    ];

    public function jurnal()
    {
        return $this->belongsTo(JurnalGuru::class);
    }
}
