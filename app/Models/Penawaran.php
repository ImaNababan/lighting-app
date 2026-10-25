<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penawaran extends Model
{
    protected $table = 'penawaran';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_DIAJUKAN = 'diajukan';
    public const STATUS_DITOLAK = 'ditolak';
    public const STATUS_FINAL = 'final';

    public const STATUS_LABEL = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_DIAJUKAN => 'Menunggu Manajer',
        self::STATUS_DITOLAK => 'Ditolak',
        self::STATUS_FINAL => 'Disetujui',
    ];

    public const STATUS_BADGE = [
        self::STATUS_DRAFT => 'warning',
        self::STATUS_DIAJUKAN => 'primary',
        self::STATUS_DITOLAK => 'danger',
        self::STATUS_FINAL => 'success',
    ];

    protected $fillable = [
        'proyek_denah_id',
        'nomor_penawaran',
        'tanggal_penawaran',
        'atas_nama',
        'nomor_telepon',
        'subtotal',
        'ppn_persen',
        'total',
        'status',
        'file_pdf_path',
        'diajukan_at',
        'reviewer_id',
        'direview_at',
        'catatan_review',
    ];

    protected $casts = [
        'tanggal_penawaran' => 'date',
        'subtotal' => 'decimal:2',
        'ppn_persen' => 'decimal:2',
        'total' => 'decimal:2',
        'diajukan_at' => 'datetime',
        'direview_at' => 'datetime',
    ];

    public function proyekDenah()
    {
        return $this->belongsTo(ProyekDenah::class, 'proyek_denah_id');
    }

    public function items()
    {
        return $this->hasMany(PenawaranItem::class, 'penawaran_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABEL[$this->status] ?? $this->status;
    }

    public function statusBadge(): string
    {
        return self::STATUS_BADGE[$this->status] ?? 'secondary';
    }

    /** Admin boleh mengedit (dan mengirim) selama masih draft atau setelah ditolak. */
    public function bisaDiedit(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_DITOLAK], true);
    }

    public function bisaDiajukan(): bool
    {
        return $this->bisaDiedit();
    }

    /** Manajer hanya bisa menyetujui/menolak penawaran yang sudah dikirim. */
    public function bisaDireview(): bool
    {
        return $this->status === self::STATUS_DIAJUKAN;
    }
}