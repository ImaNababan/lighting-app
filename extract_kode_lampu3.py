import sys
import json
import re
import fitz  # PyMuPDF

# ---------------------------------------------------------------------------
# VALIDASI KODE LAMPU: berbasis POLA, bukan lagi daftar kode spesifik.
#
# Kenapa diubah:
# - Sebelumnya, kode harus PERSIS cocok dengan daftar KODE_VALID_DEFAULT /
#   JSON dari database. Kalau ada kode baru yang belum sempat didaftarkan
#   (misal "R78" sebelum ditambahkan), dia gagal dan HILANG TOTAL dari hasil
#   deteksi, walau bentuknya jelas kode lampu.
# - Sekarang, Python hanya mengecek apakah suatu teks BERBENTUK seperti kode
#   lampu (mis. huruf diikuti angka, boleh ada desimal): R05, R78, RA7.1,
#   RL81, RM14, MN48, dst. Selama bentuknya cocok, dia dianggap kode lampu
#   dan diteruskan ke Laravel.
# - Laravel tetap yang menentukan status akhir: apakah kode itu ada di
#   katalog_lampu / pricelist ("Tersedia di pricelist" vs "belum ada di
#   pricelist"). Python tidak lagi jadi gerbang penyaring kedua yang bisa
#   membuat kode valid hilang senyap tanpa keterangan.
#
# Format yang tertangkap oleh KODE_PATTERN (1-3 huruf + angka, opsional
# desimal): R05, R30, R63, R92, R95, R78, RM13, RM14, RA03, RA7.1, RAT.1,
# BA7.2, RL81, MA01, MN48, dst.
KODE_PATTERN = re.compile(r"^[A-Z]{1,3}\d+(\.\d+)?$")

# Pengecualian: kode lampu yang formatnya TIDAK mengikuti pola huruf+angka
# (murni huruf, tanpa angka sama sekali). Ini bukan whitelist lengkap kode
# lampu seperti sebelumnya -- ini cuma daftar kecil untuk kasus khusus yang
# memang tidak bisa ditangkap regex di atas. Contoh: LG = lampu gantung.
# Tambahkan di sini HANYA kalau memang ada kode baru berformat serupa
# (huruf saja, tanpa angka).
KODE_NON_NUMERIK = {
    "LG",
}


def is_kode_lampu(text):
    """
    True kalau `text` berbentuk seperti kode lampu, berdasarkan pola umum
    (huruf+angka) ATAU termasuk pengecualian non-numerik (mis. LG).
    Tidak lagi bergantung pada daftar kode spesifik dari database.
    """
    return bool(KODE_PATTERN.match(text)) or text in KODE_NON_NUMERIK


def extract_code_and_qty(raw_text):
    text = raw_text.strip().upper()

    # Cek pola multiplier seperti "2X RA03" atau "2 X RA03"
    match = re.search(r"(\d+)\s*X\s*([A-Z0-9\.]+)", text)
    if match:
        qty = int(match.group(1))
        kode = match.group(2).rstrip(".,;:!?-")
        if is_kode_lampu(kode):
            return kode, qty

    # Cek kode biasa
    clean_text = text.rstrip(".,;:!?-")
    if is_kode_lampu(clean_text):
        return clean_text, 1

    return None, 0


def process_lighting_plan(pdf_path):
    """
    Mengembalikan list of dict: [{"kode_terdeteksi": ..., "x": ..., "y": ...}, ...]
    Melempar exception kalau file tidak bisa dibuka.
    """
    doc = fitz.open(pdf_path)
    page = doc[0]

    detail = []

    # Pakai get_text("dict") supaya dapat koordinat (bbox) tiap potongan teks,
    # bukan cuma string-nya saja seperti versi get_text("text") sebelumnya.
    text_dict = page.get_text("dict")

    for block in text_dict.get("blocks", []):
        if block.get("type") != 0:  # 0 = blok teks, selain itu (gambar dll) dilewati
            continue
        for line in block.get("lines", []):
            for span in line.get("spans", []):
                raw_text = span.get("text", "")
                if not raw_text.strip():
                    continue

                kode, qty = extract_code_and_qty(raw_text)
                if not kode:
                    continue

                x0, y0, x1, y1 = span["bbox"]
                cx = int((x0 + x1) / 2)
                cy = int((y0 + y1) / 2)

                # Kalau ada multiplier ("2X RA03"), catat sebagai beberapa titik
                # di koordinat yang sama karena teksnya cuma satu label.
                for _ in range(qty):
                    detail.append(
                        {
                            "kode_terdeteksi": kode,
                            "x": cx,
                            "y": cy,
                        }
                    )

    doc.close()
    return detail


def main():
    if len(sys.argv) < 3:
        print(
            json.dumps(
                {
                    "error": "Usage: python extract_kode_lampu.py <path_pdf> <path_output_json>"
                }
            )
        )
        sys.exit(1)

    pdf_path = sys.argv[1]
    output_json_path = sys.argv[2]
    # Catatan: argumen ke-3 (path JSON kode_valid dari database) sudah TIDAK
    # dipakai lagi, karena validasi sekarang berbasis pola, bukan whitelist.
    # Tetap diterima (diabaikan) supaya pemanggilan lama dari Laravel tidak
    # langsung error kalau belum sempat diupdate.

    try:
        detail = process_lighting_plan(pdf_path)

        if not detail:
            result = {
                "error": (
                    "Tidak ditemukan kode lampu berupa teks vektor di PDF ini. "
                    "Pastikan file adalah PDF asli hasil export AutoCAD/Revit "
                    "(bukan hasil scan/foto), atau cek apakah teksnya sudah "
                    "berubah jadi kurva/vektor gambar."
                )
            }
        else:
            result = {"detail": detail}

    except Exception as e:
        result = {"error": f"Gagal memproses PDF: {e}"}

    with open(output_json_path, "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)


if __name__ == "__main__":
    main()
