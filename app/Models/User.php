<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAJER = 'manajer';
    public const ROLE_DRAFTER = 'drafter';

    public const ROLE_LABEL = [
        self::ROLE_DRAFTER => 'Drafter',
        self::ROLE_MANAJER => 'Manajer',
        self::ROLE_ADMIN => 'Admin',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // 'role' sengaja TIDAK masuk $fillable supaya tidak bisa diisi lewat
    // form register. Ubah role lewat seeder / tinker / halaman admin.

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isManajer(): bool
    {
        return $this->role === self::ROLE_MANAJER;
    }

    public function isDrafter(): bool
    {
        return $this->role === self::ROLE_DRAFTER;
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABEL[$this->role] ?? ucfirst((string) $this->role);
    }
        /** Inisial untuk avatar: "Budi Santoso" => "BS", "Drafter" => "D". */
    public function inisial(): string
    {
        $kata = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $huruf = mb_substr($kata[0] ?? '', 0, 1);

        if (count($kata) > 1) {
            $huruf .= mb_substr(end($kata), 0, 1);
        }

        return mb_strtoupper($huruf !== '' ? $huruf : '?');
    }

    /** Warna badge Kaiadmin sesuai level. */
    public function roleWarna(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'primary',
            self::ROLE_MANAJER => 'success',
            default => 'info',
        };
    }

    public function roleDeskripsi(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Mengelola katalog lampu dan klien, menghitung titik lampu, dan membuat penawaran.',
            self::ROLE_MANAJER => 'Mereview penawaran dari Admin: menyetujui atau menolak dengan alasan.',
            default => 'Mengunggah denah PDF, mengirimnya ke Admin, dan memantau status serta alasan penolakan penawaran.',
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
