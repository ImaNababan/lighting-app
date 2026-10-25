<?php
// app/Models/Klien.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Klien extends Model
{
    protected $table = 'klien';

    protected $fillable = [
        'nama',
        'nomor_telepon',
        'email',
        'alamat',
    ];

    public function proyekDenah()
    {
        return $this->hasMany(ProyekDenah::class, 'klien_id');
    }
}