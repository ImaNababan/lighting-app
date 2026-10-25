<?php

namespace App\Helpers;

/**
 * Konversi angka rupiah ke terbilang (teks) Bahasa Indonesia, dipakai di
 * dokumen penawaran. Contoh: 13688520 -> "Tiga Belas Juta Enam Ratus
 * Delapan Puluh Delapan Ribu Lima Ratus Dua Puluh Rupiah".
 */
class Terbilang
{
    private static array $angka = [
        '',
        'Satu',
        'Dua',
        'Tiga',
        'Empat',
        'Lima',
        'Enam',
        'Tujuh',
        'Delapan',
        'Sembilan',
        'Sepuluh',
        'Sebelas',
    ];

    public static function make(int $nominal): string
    {
        $nominal = abs($nominal);
        $huruf = trim(self::konversi($nominal));
        $huruf = preg_replace('/\s+/', ' ', $huruf);

        return $huruf . ' Rupiah';
    }

    private static function konversi(int $angka): string
    {
        if ($angka < 12) {
            return self::$angka[$angka];
        }

        if ($angka < 20) {
            return self::konversi($angka - 10) . ' Belas';
        }

        if ($angka < 100) {
            return trim(self::konversi(intdiv($angka, 10)) . ' Puluh ' . self::konversi($angka % 10));
        }

        if ($angka < 200) {
            return trim('Seratus ' . self::konversi($angka - 100));
        }

        if ($angka < 1000) {
            return trim(self::konversi(intdiv($angka, 100)) . ' Ratus ' . self::konversi($angka % 100));
        }

        if ($angka < 2000) {
            return trim('Seribu ' . self::konversi($angka - 1000));
        }

        if ($angka < 1000000) {
            return trim(self::konversi(intdiv($angka, 1000)) . ' Ribu ' . self::konversi($angka % 1000));
        }

        if ($angka < 1000000000) {
            return trim(self::konversi(intdiv($angka, 1000000)) . ' Juta ' . self::konversi($angka % 1000000));
        }

        if ($angka < 1000000000000) {
            return trim(self::konversi(intdiv($angka, 1000000000)) . ' Miliar ' . self::konversi($angka % 1000000000));
        }

        return trim(self::konversi(intdiv($angka, 1000000000000)) . ' Triliun ' . self::konversi($angka % 1000000000000));
    }
}