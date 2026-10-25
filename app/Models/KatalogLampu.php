<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KatalogLampu extends Model
{
    protected $table = 'katalog_lampu';

    protected $fillable = [
        'kode',
        'deskripsi',
        'foto',
        'harga_satuan',
        'satuan',
    ];
}