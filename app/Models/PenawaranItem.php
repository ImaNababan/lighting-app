<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranItem extends Model
{
    protected $table = 'penawaran_item';

    protected $fillable = [
        'penawaran_id',
        'katalog_lampu_id',
        'kode_lampu',
        'deskripsi',
        'qty',
        'satuan',
        'harga_satuan',
        'subtotal',
    ];

    protected $casts = [
        'qty' => 'integer',
        'harga_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id');
    }

    public function katalogLampu()
    {
        return $this->belongsTo(KatalogLampu::class, 'katalog_lampu_id');
    }
}