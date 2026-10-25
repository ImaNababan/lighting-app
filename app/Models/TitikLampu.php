<?php
// app/Models/TitikLampu.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TitikLampu extends Model
{
    protected $table = 'titik_lampu';

    protected $fillable = [
        'proyek_denah_id',
        'kode_terdeteksi',
        'kode_final',
        'x',
        'y',
        'confidence_score',
        'status',
        'crop_image_path',
    ];

    public function proyekDenah()
    {
        return $this->belongsTo(ProyekDenah::class, 'proyek_denah_id');
    }
}