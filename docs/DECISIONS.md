# ARSITEKTURAL DECISIONS (ADR) — BESTCO

Dokumen ini mencatat keputusan arsitektural dan teknis utama aplikasi BESTCO berdasarkan dokumen PRD & Arsitektur Teknis.

---

### ADR-001: Presisi Desimal Tinta Fotocopy & Satuan Dasar (UoM)
- **Konteks:** Mesin fotocopy mengonsumsi 1 Kg tinta untuk 20.000 lembar (rasio 0,00005 Kg/lembar). Kolom basis data `DECIMAL(19,4)` hanya mendukung 4 digit di belakang koma.
- **Keputusan:** Satuan dasar (*Base Unit of Measure*) untuk bahan baku toner disimpan dalam **Gram (`GRM`)**. Konsumsi BoM per lembar tercatat tepat `0.0500` Gram per lembar, dan 1 Kg tinta dicatat sebagai `1000.0000` Gram.
- **Alasan:** Menjamin integritas matematis 100% tanpa risiko *rounding truncation* pada skala 4 desimal.

---

### ADR-002: Dual-Inventory & Konversi Otomatis ("Ambil dari Etalase")
- **Konteks:** Kebutuhan operasional jam sibuk ketika bahan baku kertas fotocopy habis dan operator perlu mengambil stok dari etalase ATK retail.
- **Keputusan:** Pemisahan ketat antara `RETAIL` (akun persediaan `1-1301`) dan `RAW_MATERIAL` (akun persediaan `1-1302`). Konversi 1 Rim retail menghasilkan 500 lembar bahan baku jasa, dicatat dengan 2 entri buku pembantu stok dan jurnal berimbang otomatis (Dr. `1-1302` / Cr. `1-1301`).
- **Alasan:** Mencegah percampuran stok ritel dan bahan produksi, serta menjaga akurasi HPP moving average.

---

### ADR-003: Jurnal Akuntansi Double-Entry Atomik & Immutable
- **Konteks:** Kepatuhan pembukuan dan transparansi audit pembelajaran siswa MPLB.
- **Keputusan:** Setiap posting jurnal berjalan dalam database transaction bersamaan dengan dokumen sumbernya. Jurnal yang telah diposting bersifat *immutable* (larangan hard delete/update; koreksi harus via jurnal pembalik / penyesuaian). Setiap akun kas/bank otomatis mencatat mutasi ke Buku Kas Umum (BKU).
- **Alasan:** Menjamin keseimbangan Debit = Kredit selalu terpenuhi dan rekam jejak audit tidak dapat dimanipulasi.

---

### ADR-004: Penanganan Presisi Decimal Model Eloquent di SQLite Testing & MySQL Production
- **Konteks:** SQLite memformat nilai angka floating/integer tanpa trailing zero (misal `60000` bukan `60000.0000`), menyebabkan kegagalan assertion `assertSame` pada pengujian bcmath.
- **Keputusan:** Semua custom getter/accessor numerik pada model diatur memanggil `number_format((float) $value, 4, '.', '')` secara konsisten.
- **Alasan:** Konsistensi format string dengan 4 angka di belakang koma di seluruh environment (testing memory SQLite maupun production MySQL).

---

### ADR-005: Proteksi PIN Otorisasi Guru, Pencegahan Double Hashing, & Mekanisme VOID Atomik
- **Konteks:** Setter `pinCode` di model `User` memicu *double-hashing* jika string bcrypt diinputkan ulang dari seeder. Fitur pembatalan transaksi kasir (VOID) juga membutuhkan verifikasi PIN 6-digit guru supervisor pendamping.
- **Keputusan:**
  1. Setter `pinCode` menggunakan `Hash::needsRehash($value) ? Hash::make($value) : $value`.
  2. Aksi `VoidPosTransactionAction` memverifikasi PIN guru supervisor secara ketat, mengembalikan stok fisik produk etalase dan bahan baku BoM jasa secara atomik, membalik saldo ekspektasi kas shift, serta menghasilkan Jurnal Pembalik (*reversing journal*) `REV-YYYYMMDD-XXXX`.
- **Alasan:** Menjamin auditabilitas tinggi, mencegah ketidakseimbangan stok fisik dan buku, serta memastikan keamanan otorisasi tindakan kritis di Teaching Factory.


