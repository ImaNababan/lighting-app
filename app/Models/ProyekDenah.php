<?php
// app/Models/ProyekDenah.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProyekDenah extends Model
{
    protected $table = 'proyek_denah';

    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_DIKIRIM = 'dikirim';
    public const STATUS_REVISI = 'revisi';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    public const STATUS_LABEL = [
        self::STATUS_UPLOADED => 'Diunggah',
        self::STATUS_DIKIRIM => 'Dikirim ke Admin',
        self::STATUS_REVISI => 'Perlu revisi',
        self::STATUS_PROCESSING => 'Menghitung titik',
        self::STATUS_PROCESSED => 'Titik terhitung',
        self::STATUS_FAILED => 'Gagal hitung titik',
    ];

    public const STATUS_BADGE = [
        self::STATUS_UPLOADED => 'secondary',
        self::STATUS_DIKIRIM => 'primary',
        self::STATUS_REVISI => 'warning',
        self::STATUS_PROCESSING => 'warning',
        self::STATUS_PROCESSED => 'success',
        self::STATUS_FAILED => 'danger',
    ];

    protected $fillable = [
        'user_id',
        'klien_id',
        'nama_proyek',
        'file_asli_path',
        'file_render_path',
        'status',
        'catatan',
        'diajukan_at',
        'reviewer_id',
        'direview_at',
        'catatan_review',
    ];

    protected $casts = [
        'diajukan_at' => 'datetime',
        'direview_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function klien()
    {
        return $this->belongsTo(Klien::class, 'klien_id');
    }

    public function titikLampu()
    {
        return $this->hasMany(TitikLampu::class, 'proyek_denah_id');
    }

    public function penawaran()
    {
        return $this->hasMany(Penawaran::class, 'proyek_denah_id');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABEL[$this->status] ?? $this->status;
    }

    public function statusBadge(): string
    {
        return self::STATUS_BADGE[$this->status] ?? 'secondary';
    }

    /** Drafter boleh mengirim ke Admin selama belum dikirim. */
    public function bisaDikirim(): bool
    {
        return $this->status === self::STATUS_UPLOADED;
    }

    /**
     * Drafter boleh mengganti file denah jika:
     * - denah belum dikirim ke Admin, atau
     * - Admin meminta upload ulang (status Perlu revisi), atau
     * - ada penawaran dari denah ini yang ditolak Manajer.
     * Tidak boleh bila ada penawaran yang sedang direview atau sudah disetujui.
     */
    public function bisaUploadUlang(): bool
    {
        if ($this->status === self::STATUS_UPLOADED) {
            return true;
        }

        if ($this->punyaPenawaranTerkunci()) {
            return false;
        }

        return $this->status === self::STATUS_REVISI
            || $this->penawaranDitolak()->isNotEmpty();
    }

    /**
     * Admin boleh meminta Drafter mengunggah ulang denah setelah denah dikirim
     * (termasuk saat hitung titik gagal), selama belum ada penawaran yang
     * sedang direview Manajer atau sudah disetujui.
     */
    public function bisaMintaRevisi(): bool
    {
        return in_array($this->status, [
            self::STATUS_DIKIRIM,
            self::STATUS_PROCESSED,
            self::STATUS_FAILED,
        ], true) && ! $this->punyaPenawaranTerkunci();
    }

    /** Penawaran yang sedang direview atau sudah disetujui mengunci denahnya. */
    public function punyaPenawaranTerkunci(): bool
    {
        return $this->penawaran
            ->whereIn('status', [Penawaran::STATUS_DIAJUKAN, Penawaran::STATUS_FINAL])
            ->isNotEmpty();
    }

    /** Drafter hanya boleh menghapus denah yang belum dikirim. */
    public function bisaDihapusDrafter(): bool
    {
        return $this->status === self::STATUS_UPLOADED;
    }

    /** Admin boleh menghitung titik setelah denah dikirim (boleh diulang bila gagal / katalog berubah). */
    public function bisaDihitung(): bool
    {
        return in_array($this->status, [
            self::STATUS_DIKIRIM,
            self::STATUS_PROCESSING,
            self::STATUS_PROCESSED,
            self::STATUS_FAILED,
        ], true);
    }

    /** Penawaran baru bisa dibuat setelah titik lampu selesai dihitung. */
    public function bolehDibuatPenawaran(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    public function penawaranDitolak()
    {
    return $this->penawaran
        ->where('status', Penawaran::STATUS_DITOLAK)
        ->sortByDesc('direview_at')
        ->values();
    }
}