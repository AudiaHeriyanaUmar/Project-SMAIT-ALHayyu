<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }
}
