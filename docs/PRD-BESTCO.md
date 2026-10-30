# PRD — BESTCO (Point of Sale & Akuntansi Teaching Factory MPLB)

| | |
|---|---|
| Versi | 1.0 (draft untuk dibangun) |
| Produk | **BESTCO** — POS + Persediaan + Akuntansi untuk Teaching Factory jurusan MPLB |
| Platform | Web app (Laravel 13, MySQL 8, Tailwind CSS) |
| Bahasa UI | Indonesia (id-ID), zona waktu Asia/Jakarta, mata uang Rupiah |
| Pengguna | Superadmin, Admin, Piket Guru, Piket Siswa |

---

## 1. Ringkasan & Tujuan

Teaching Factory (Tefa) MPLB menjalankan dua lini usaha dalam satu toko:

1. **Toko etalase** — menjual alat tulis dan kebutuhan kantor.
2. **Jasa** — fotocopy, print, laminating, jilid, dan jasa sejenis toko fotocopy.

Keduanya memiliki persediaan yang **terpisah**: *Barang Dagang* (dijual langsung) dan *Bahan Jasa* (dipakai untuk mengerjakan jasa). Tetapi di lapangan, bahan jasa sering habis di tengah pekerjaan sehingga harus mengambil dari barang etalase. Sistem harus menangani ini secara resmi, tercatat, dan terjurnal.

Karena Tefa adalah sarana belajar, BESTCO juga harus menghasilkan **pembukuan akuntansi yang lengkap, transparan, dan bisa ditelusuri** (jurnal, buku besar, neraca saldo, laba rugi, neraca, BKU, dst.), sehingga siswa dapat melihat alur dari transaksi kasir sampai laporan keuangan.

### Tujuan terukur
- Satu transaksi kasir campuran (barang + jasa) selesai ≤ 30 detik oleh siswa terlatih.
- Setiap transaksi yang mengubah uang atau stok otomatis menghasilkan jurnal seimbang (debit = kredit) tanpa input manual.
- Stok bahan jasa selalu bisa direkonsiliasi: pemakaian teoretis (dari resep/BOM) vs stok fisik (opname).
- Setiap rupiah dapat ditelusuri: laporan → buku besar → jurnal → transaksi asal → siapa/shift apa.

### Di luar ruang lingkup v1
- Integrasi payment gateway/QRIS dinamis (v1 hanya mencatat metode pembayaran).
- Aplikasi mobile native, e-commerce online, multi-cabang.
- Perpajakan (PPN/PPh) — disediakan hook akun, tidak ada perhitungan otomatis.
- Penggajian. Honor/insentif siswa dicatat sebagai biaya manual.

---

## 2. Peran & Hak Akses

| Peran | Deskripsi |
|---|---|
| **Superadmin** | Pemilik sistem. Semua akses + pengaturan sistem, tema, pengguna, backup, penguncian periode. |
| **Admin** | Pengelola Tefa (guru pembimbing/bendahara). Master data, pembelian, stok, laporan, akuntansi, void/retur, verifikasi. |
| **Piket Guru** | Guru yang bertugas hari itu (fleksibel, tanpa jadwal tetap). Membuka/menutup shift, mengawasi, memverifikasi, approve retur/diskon khusus, transfer bahan. |
| **Piket Siswa** | Siswa bertugas (±3 siswa/hari, wakil per kelas). Kasir, catat barang gagal/rusak, pinjam bahan dengan persetujuan guru piket. |

### Matriks izin (R=lihat, C=buat, U=ubah, D=hapus/void, A=approve)

| Fitur | Superadmin | Admin | Piket Guru | Piket Siswa |
|---|---|---|---|---|
| Pengaturan sistem & tema | CRUD | – | – | – |
| Manajemen pengguna | CRUD | CRU (kecuali Superadmin) | – | – |
| Jadwal piket siswa | CRUD | CRUD | R | R (jadwal sendiri) |
| Buka/tutup shift | ✔ | ✔ | ✔ | – |
| Kasir POS | ✔ | ✔ | ✔ | ✔ (hanya jika terdaftar di shift aktif) |
| Diskon manual / ubah harga di kasir | ✔ | ✔ | ✔ (A) | Terbatas, butuh PIN guru piket |
| Void / retur transaksi | ✔ | ✔ | A (hari yang sama) | Ajukan saja |
| Master barang, layanan, harga, BOM | CRUD | CRUD | R | R |
| Pembelian & supplier | CRUD | CRUD | R | – |
| Transfer bahan etalase → jasa | ✔ | ✔ | ✔ | Ajukan, guru piket A (PIN) |
| Catat barang gagal/rusak | ✔ | ✔ | ✔ | C |
| Verifikasi barang rusak | ✔ | ✔ | ✔ | – |
| Stok opname | ✔ | ✔ | C | – |
| Biaya / kas keluar | ✔ | ✔ | C (draft) | – |
| Jurnal manual, periode, penutupan | ✔ | ✔ (periode terbuka) | – | – |
| Laporan operasional | R | R | R (shift/hari ini) | R (shift sendiri) |
| Laporan akuntansi | R | R | R | – |
| Audit log | R | R | – | – |

Izin diimplementasikan lewat **permission granular** (spatie/laravel-permission) dan Policy; matriks di atas adalah *default seed* dan dapat diubah Superadmin.

---

## 3. Alur Piket & Shift (Inti Operasional)

### 3.1 Data
- **Kelas** (mis. XI MPLB 1) dan **Siswa** (NIS, nama, kelas, akun login).
- **Jadwal piket siswa**: hari (Senin–Jumat/Sabtu) → daftar wakil per kelas (±3). Berlaku dengan tanggal efektif.
- **Guru piket**: tidak terjadwal. Guru mana pun dengan peran Piket Guru dapat membuka shift hari itu.

### 3.2 Alur harian
1. Guru piket login → **Buka Shift**: tanggal otomatis, input **kas awal** (laci), tampil daftar siswa terjadwal hari itu.
2. Untuk tiap siswa terjadwal: tandai **Hadir** atau **Tidak hadir**. Jika tidak hadir → pilih **siswa pengganti** (dicari dari seluruh siswa MPLB; kolom alasan; tercatat `is_substitute`, `replaced_student_id`).
3. Hanya siswa berstatus hadir/pengganti pada shift aktif yang bisa memakai kasir. Siswa lain yang login hanya melihat halaman "Anda tidak sedang piket".
4. Selama shift: semua transaksi, barang rusak, transfer bahan tertaut `shift_id` dan `user_id`.
5. **Tutup Shift**: sistem menghitung kas seharusnya (kas awal + penerimaan tunai − pengeluaran tunai). Guru memasukkan **kas fisik** (boleh per pecahan). Selisih dicatat ke akun *Selisih Kas* (jurnal otomatis). Cetak **Laporan Serah Terima Shift** (ringkasan penjualan per metode, jasa vs barang, barang rusak, transfer bahan, daftar petugas).
5. Shift yang sudah ditutup tidak bisa diubah; koreksi hanya lewat transaksi koreksi oleh Admin.

Aturan: hanya satu shift terbuka pada satu waktu. Lupa menutup shift → peringatan di hari berikutnya; Admin dapat menutup paksa dengan catatan.

---

## 4. Aturan Bisnis Inti

**BR-01 Dua domain persediaan terpisah.** *Barang Dagang* (tabel `products`) dan *Bahan Jasa* (tabel `materials`) punya stok, harga pokok, dan akun persediaan sendiri. Barang etalase tidak pernah berkurang karena jasa, kecuali lewat Transfer (BR-05).

**BR-02 Resep bahan (BOM) per layanan.** Tiap layanan punya daftar bahan beserta jumlah per satuan layanan. Contoh: Fotocopy A4 hitam-putih → 1 lembar Kertas A4 + 0,05 gram Tinta Fotocopy per lembar. Saat layanan terjual, bahan otomatis dipotong dan dibebankan.

**BR-03 Konsumsi tinta fotocopy.** 1 kg tinta = 20.000 lembar fotocopy **termasuk yang gagal**. Maka `qty_per_unit` tinta = 1000 g / 20.000 = **0,05 gram per lembar**. Nilai ini **dapat dikonfigurasi** (field "yield per satuan bahan"). Setiap lembar gagal (BR-04) juga memotong tinta 0,05 g. Sistem menampilkan estimasi *sisa lembar* dari stok tinta dan laporan *yield aktual* (lembar tercetak total ÷ kg terpakai berdasarkan opname).

**BR-04 Fotocopy/print gagal.** Dicatat sebagai **Barang Rusak** dengan sumber `cetak_gagal`, terkait layanan (dan transaksi bila ada). Efek: potong bahan sesuai BOM × jumlah gagal, tidak ditagihkan ke pelanggan, jurnal ke *Beban Bahan Rusak*. Alasan baku: kertas macet, tinta kabur/belang, salah ukuran, salah file/halaman, salah pelanggan, lainnya (dengan catatan).

**BR-05 Pinjam/ambil bahan dari etalase (Transfer Etalase → Jasa).**
- Pemicu: (a) tombol **Ambil dari Etalase** di POS/halaman bahan, dan (b) otomatis muncul dialog saat kasir menambah layanan yang bahannya **kurang** dari stok jasa.
- Setiap `materials` dapat dipetakan ke satu `products` dengan **faktor konversi** (mis. Kertas A4 produk satuan *rim* = 500 *lembar* bahan).
- Input jumlah dalam satuan bahan. Jika produk tidak boleh pecahan (`allow_fraction = false`), sistem membulatkan ke atas ke satuan produk utuh; kelebihannya masuk stok bahan jasa (bukan hilang).
- Dinilai pada **harga pokok rata-rata barang** (bukan harga jual). Tidak ada laba/rugi.
- Jurnal: Dr Persediaan Bahan Jasa / Cr Persediaan Barang Dagang.
- Siswa mengajukan → guru piket menyetujui dengan PIN. Tercatat alasan, pengaju, penyetuju, shift.
- Jika stok etalase juga tidak cukup, ditolak dengan pesan jelas.

**BR-06 Tidak ada stok negatif.** Pengecualian hanya jika Admin mengaktifkan "izinkan stok negatif" per item (default mati).

**BR-07 Harga bertingkat layanan (tiered pricing).** Satu layanan dapat punya beberapa aturan berdasarkan rentang jumlah. Setiap aturan memilih salah satu tipe:

| Tipe | Arti | Contoh |
|---|---|---|
| `unit_price` | Harga satuan tetap menggantikan harga dasar | 1–2 lbr: Rp 500/lbr |
| `fixed_total` | Harga total tetap untuk seluruh jumlah | 1–2 lbr: Rp 1.000 total |
| `percent_discount` | Diskon % dari harga dasar | ≥100 lbr: 5% |
| `discount_per_unit` | Potongan Rp per satuan | ≥300 lbr: −Rp 25/lbr |
| `discount_total` | Potongan Rp total | ≥1000 lbr: −Rp 20.000 |

Aturan evaluasi (default): pilih **satu** aturan dengan rentang yang mencakup jumlah (tidak menumpuk); jumlah tier dihitung dari **total jumlah baris layanan itu**; tier berlaku untuk **seluruh** lembar (bukan progresif). Sistem menyimpan *snapshot* aturan yang dipakai di baris transaksi. Layanan tanpa aturan memakai harga dasar × jumlah.

*Contoh seed Fotocopy A4 (harga dasar Rp 300; semua bisa diubah di UI):*

| Rentang | Tipe | Nilai | Hasil per lembar |
|---|---|---|---|
| 1–2 | unit_price | 500 | Rp 500 |
| 3–99 | (harga dasar) | – | Rp 300 |
| 100–499 | percent_discount | 5% | Rp 285 |
| 500–999 | percent_discount | 10% | Rp 270 |
| ≥1000 | unit_price | 250 | Rp 250 |

**BR-08 Peringatan margin.** Saat Admin menyimpan harga/aturan, sistem menghitung HPP per satuan dari BOM (harga rata-rata bahan) dan memperingatkan jika harga efektif terendah di bawah HPP.

**BR-09 Diskon dibukukan transparan.** Pendapatan dicatat **bruto**, diskon dicatat di akun kontra *Diskon Penjualan* (bukan mengurangi pendapatan langsung).

**BR-10 Metode persediaan.** Rata-rata bergerak (*moving average*) untuk produk dan bahan. Kartu stok mencatat saldo qty, harga rata-rata, dan nilai.

**BR-11 Jurnal tidak boleh diubah/dihapus.** Koreksi = jurnal pembalik + jurnal baru. Periode akuntansi yang dikunci menolak posting baru.

**BR-12 Void/retur.** Void transaksi (hari yang sama, belum ditutup periodenya) dan retur sebagian membuat transaksi pembalik: stok kembali (barang) / bahan **tidak** kembali untuk layanan yang sudah dikerjakan kecuali ditandai "belum dikerjakan"; jurnal pembalik otomatis. Wajib alasan + approval.

**BR-13 Penomoran dokumen.** Format `INV/YYMMDD/0001`, `PBL/…`, `JU/YYYY/MM/0001`, dst., dibuat atomik (lock) agar tidak ganda.

---

## 5. Kebutuhan Fungsional per Modul

### 5.1 Autentikasi & Pengguna
- Login dengan username/NIS/email + password; throttle; logout otomatis saat idle (konfigurasi).
- Superadmin/Admin kelola pengguna, impor siswa via Excel, reset password.
- **PIN persetujuan** 4–6 digit untuk guru (disimpan hash) dipakai pada dialog approval di kasir.
- Opsional: 2FA TOTP untuk Superadmin.

### 5.2 Kasir (POS)
Layar utama untuk tablet landscape/desktop, dioptimalkan untuk kecepatan.

- Pencarian satu kolom: scan barcode, ketik SKU/nama, hasil gabungan **Barang** dan **Layanan** (dibedakan label).
- Tab/pintasan cepat: layanan favorit (Fotocopy, Print HP, Print Warna, Laminating, Jilid) sebagai tombol besar.
- Keranjang campuran barang + layanan. Baris layanan menampilkan: jumlah, tier aktif, harga efektif, hemat diskon.
- Input jumlah dengan numpad/keyboard; layanan print memiliki pilihan **mode warna**: Hitam-putih (Rp 500), Sedikit warna/logo (Rp 1.000), Full warna (Rp 2.000) — sebagai varian layanan.
- Tombol **Catat Gagal** per baris layanan atau global: jumlah lembar gagal + alasan (BR-04), langsung terhubung ke transaksi yang sedang berjalan.
- Cek stok bahan real-time; bila kurang → dialog **Ambil dari Etalase** (BR-05) dengan ringkasan: kekurangan, stok etalase, jumlah yang disarankan, hitungan konversi, PIN guru piket.
- Diskon: per baris & per transaksi (persen/nominal), terbatas peran; di atas batas butuh PIN guru.
- Pembayaran: Tunai (hitung kembalian + tombol uang pas/pecahan cepat), Transfer, QRIS (catat referensi), **Piutang** (pilih pelanggan, jatuh tempo), pembayaran campuran (split).
- **Tahan transaksi (hold)** dan panggil lagi; banyak antrean sekaligus.
- Cetak struk 58/80 mm (CSS print) + opsi struk PDF; cetak ulang tercatat.
- Pintasan keyboard (F2 cari, F4 bayar, F8 hold, dll.), dapat dipakai tanpa mouse.
- Cegah double-submit (idempotency key).
- Offline-tolerant ringan: jika koneksi LAN putus sesaat, keranjang tidak hilang (state lokal) dan pesan jelas muncul.

Checkout (satu DB transaction, lock baris stok):
1. Validasi stok & periode & shift aktif.
2. Simpan `sales`, `sale_items` (snapshot harga, tier, HPP), `sale_payments`.
3. Potong stok barang; potong bahan sesuai BOM; tulis `inventory_movements`.
4. Posting jurnal otomatis (bagian 7).

### 5.3 Master Barang Dagang (Etalase)
Kategori, barang (SKU, barcode, nama, satuan, harga jual, harga pokok rata-rata, stok min, foto opsional, status aktif, `allow_fraction`), satuan & konversi, impor/ekspor Excel, cetak label barcode. Riwayat harga jual.

### 5.4 Master Layanan Jasa
Kategori layanan (Fotocopy, Print, Laminating, Jilid, Scan, Lainnya), layanan/varian (kode, nama, satuan, harga dasar, mode warna/ukuran), **resep bahan (BOM)**, **aturan harga bertingkat**, status aktif, tampil di favorit.

Seed layanan awal:
- Fotocopy A4/F4/A3 (hitam-putih), Fotocopy warna.
- Print: Hitam-putih Rp 500; Sedikit warna Rp 1.000; Full warna Rp 2.000 (A4; ukuran lain konfigurasi).
- Laminating Besar Rp 5.000; Laminating Kecil Rp 3.000 (BOM: 1 pouch sesuai ukuran).
- Jilid: spiral kawat/plastik (per ketebalan), jilid lakban, jilid hard cover.
- Scan, ketik/edit dokumen, cetak foto, dll. — dapat ditambah Admin tanpa coding.

### 5.5 Bahan Jasa
Master bahan (kode, nama, satuan dasar, stok min, harga pokok rata-rata, pemetaan ke produk etalase + faktor konversi, **yield per satuan** untuk tinta). Contoh bahan: Kertas A4/F4/A3, Tinta fotocopy (gram), Tinta/cartridge printer, Pouch laminating besar/kecil, Spiral (aneka ukuran), Cover jilid, Lakban jilid. Kartu stok, peringatan stok menipis, estimasi sisa kapasitas (mis. "tinta ±4.200 lembar lagi").

### 5.6 Transfer Etalase → Jasa
Daftar, pengajuan, persetujuan, riwayat; filter per shift/siswa/bahan; laporan frekuensi (indikator bahan jasa sering kurang → saran restock).

### 5.7 Barang Rusak / Gagal
Satu modul untuk semua kerusakan:
- Sumber: `cetak_gagal`, `rusak` (pecah/sobek), `kadaluarsa`, `hilang`, `lainnya`.
- Domain: barang etalase atau bahan jasa.
- Otomatis potong stok, hitung nilai kerugian pada HPP, jurnal Beban Bahan Rusak / Beban Barang Rusak.
- Status verifikasi oleh guru/Admin (flag, tidak menahan stok).
- Laporan: tingkat gagal % per layanan/siswa/shift/mesin (opsional), tren, nilai kerugian.

### 5.8 Pembelian & Supplier
Supplier, pembelian (tunai/kredit), item masuk ke **Barang Dagang atau Bahan Jasa** sesuai pilihan, update harga pokok rata-rata, utang usaha, pembayaran utang, retur pembelian, lampiran nota (foto/PDF).

### 5.9 Stok Opname
Sesi opname per domain, input fisik, selisih → penyesuaian stok + jurnal (Beban Selisih Persediaan / Pendapatan Selisih), approval Admin. Khusus tinta: input berat timbangan. Menghasilkan analisis variansi pemakaian teoretis vs aktual.

### 5.10 Biaya & Kas Keluar
Kategori biaya (listrik, servis mesin, ATK operasional, kebersihan, honor, dll.), pencatatan dengan bukti, metode bayar, jurnal otomatis. Kas masuk lain (setoran modal, hibah) juga tersedia.

### 5.11 Piutang
Penjualan kredit, daftar piutang, pembayaran sebagian/penuh, umur piutang, jurnal otomatis.

### 5.12 Modul Akuntansi

**a. Bagan Akun (Perkiraan)** — hierarki, kode, nama, tipe (aset/kewajiban/ekuitas/pendapatan/beban), saldo normal, akun kas/bank, akun sistem terkunci (dipakai posting otomatis), aktif/nonaktif. CRUD untuk Admin.

**b. Jurnal** — Jurnal Umum (semua), filter per jenis sumber (penjualan, pembelian, transfer, rusak, biaya, opname, manual, penyesuaian, penutup). Setiap baris jurnal memiliki **tautan ke dokumen sumber**. Jurnal manual dengan validasi seimbang. Jurnal penyesuaian (penyusutan, perlengkapan, dll.). Jurnal pembalik.

**c. Buku Besar** — per akun, saldo awal, mutasi, saldo berjalan, drill-down ke jurnal dan transaksi.

**d. Buku Pembantu** — Kas, Piutang (per pelanggan), Utang (per supplier), Persediaan (kartu stok per item).

**e. BKU (Buku Kas Umum)** — kronologis seluruh penerimaan dan pengeluaran kas (dan bank): Tanggal, No. Bukti, Uraian, Penerimaan, Pengeluaran, Saldo; ringkasan harian dan bulanan; saldo awal/akhir; kolom tanda tangan (Guru Pembimbing/Bendahara/Kepala Tefa) pada cetakan; pengelompokan per kode akun lawan.

**f. Laporan keuangan:**
- Neraca Saldo (sebelum & sesudah penyesuaian)
- Neraca Lajur (worksheet)
- Laporan Laba Rugi (bruto → diskon → bersih → HPP → laba kotor → beban → laba bersih), **dengan pemisahan segmen** Toko Etalase vs Jasa dan margin per layanan
- Laporan Perubahan Ekuitas
- Neraca/Laporan Posisi Keuangan
- Laporan Arus Kas (metode langsung)
- Catatan: nilai persediaan terhadap kartu stok (rekonsiliasi otomatis: saldo akun persediaan = total nilai kartu stok)

**g. Periode & penutupan** — periode bulanan/tahun ajaran; buka/kunci periode; jurnal penutup otomatis (pendapatan & beban → Ikhtisar Laba Rugi → Laba Ditahan/Modal); saldo awal tahun baru otomatis; nonaktifkan posting ke periode terkunci.

**h. Saldo awal** — wizard input saldo awal akun dan stok awal (menghasilkan jurnal saldo awal seimbang terhadap Modal).

Semua laporan: filter periode, ekspor **PDF dan Excel**, header sekolah/Tefa, drill-down ke sumber, perbandingan periode.

### 5.13 Laporan Operasional
Penjualan harian/periode/per shift/per petugas, per layanan, produk terlaris, stok menipis, kartu stok, pemakaian bahan, yield tinta, rekap barang rusak, transfer bahan, kinerja piket (jumlah transaksi, nilai, selisih kas), piutang.

### 5.14 Dashboard
- **Piket/Siswa:** status shift, penjualan hari ini, pintasan kasir, peringatan stok bahan menipis.
- **Admin/Superadmin:** penjualan & laba kotor (hari/minggu/bulan), komposisi etalase vs jasa, bahan menipis, barang rusak, selisih kas, utang/piutang jatuh tempo, status periode. Grafik sederhana, informasional, bukan dekoratif.

### 5.15 Pengaturan (Superadmin)
- Identitas: nama Tefa, logo, alamat, footer struk.
- **Kustomisasi tema warna**: pilih warna utama (hex/picker) → sistem otomatis membuat palet 50–950 yang lolos kontras (lihat bagian 9), pratinjau langsung, preset (Biru default), reset. Disimpan di DB, di-inject sebagai CSS variable.
- Aturan: batas diskon per peran, izinkan stok negatif, timeout sesi, format nomor dokumen, bahan/akun default.
- Backup & restore terjadwal, lihat riwayat backup.
- Penguncian periode.

### 5.16 Audit Log
Semua perubahan data penting (siapa, kapan, sebelum/sesudah, IP). Tidak dapat dihapus melalui UI.

---

## 6. Model Data (Skema Tingkat Tinggi)

Konvensi: PK `id` bigint; uang `DECIMAL(18,2)`; kuantitas `DECIMAL(18,4)`; biaya/harga rata-rata `DECIMAL(18,4)`; soft delete untuk master; `created_by/updated_by`; semua tabel transaksi punya `shift_id` bila relevan; indeks pada tanggal dan foreign key; transaksi dengan `lockForUpdate` pada baris stok.

**Pengguna & piket:** `users`, `roles/permissions` (spatie), `classes`, `students`, `piket_schedules`, `shifts`, `shift_students` (status hadir/absen, `replaced_student_id`, `is_substitute`, alasan).

**Katalog:** `units`, `unit_conversions`, `product_categories`, `products`, `service_categories`, `services`, `service_materials` (BOM: `service_id, material_id, qty_per_unit`), `service_price_tiers` (`service_id, min_qty, max_qty, rule_type, value, active, valid_from, valid_to`), `materials` (`linked_product_id`, `conversion_factor`, `yield_note`).

**Persediaan:** `inventory_movements` (`domain` etalase|jasa, `item_type`, `item_id`, `movement_type` [purchase_in, sale_out, service_use, waste, transfer_out, transfer_in, adjustment, opname, return_in, return_out], `qty_in`, `qty_out`, `unit_cost`, `balance_qty`, `balance_avg_cost`, `ref_type`, `ref_id`, `shift_id`, `user_id`), `material_transfers`, `waste_records`, `stock_opnames`, `stock_opname_items`.

**Penjualan:** `customers`, `sales`, `sale_items` (`sellable_type/id`, `qty`, `base_price`, `applied_tier_snapshot`, `unit_price`, `line_discount`, `line_total`, `cost_snapshot`), `sale_payments`, `sale_returns`, `receivable_payments`, `held_carts`.

**Pembelian:** `suppliers`, `purchases`, `purchase_items`, `purchase_payments`.

**Keuangan:** `expenses`, `expense_categories`, `cash_transactions` (kas masuk lain), `shift_cash_counts`.

**Akuntansi:** `accounts` (COA), `fiscal_periods` (status open/locked/closed), `journal_entries` (`no`, `date`, `source_type`, `source_id`, `description`, `status`, `reversal_of_id`, `posted_by`), `journal_lines` (`entry_id`, `account_id`, `debit`, `credit`, `memo`), `opening_balances`.

**Sistem:** `settings`, `activity_log`, `document_sequences`, `backups_log`.

Invarian (wajib ditegakkan di kode dan diuji): jumlah debit = kredit per jurnal; saldo `Persediaan Barang Dagang` = Σ nilai kartu stok produk; saldo `Persediaan Bahan Jasa` = Σ nilai kartu stok bahan; kas akun = BKU; tidak ada UPDATE/DELETE pada `journal_lines` posted.

---

## 7. Aturan Jurnal Otomatis

Metode persediaan perpetual. Nominal HPP berasal dari harga rata-rata saat transaksi.

| Peristiwa | Debit | Kredit |
|---|---|---|
| Penjualan barang tunai | Kas (net) ; Diskon Penjualan | Penjualan Barang Dagang |
| …HPP barang | HPP Barang Dagang | Persediaan Barang Dagang |
| Penjualan jasa tunai | Kas (net) ; Diskon Penjualan | Pendapatan Jasa [Fotocopy/Print/Laminating/Jilid/Lainnya] |
| …bahan terpakai (BOM) | Beban Pemakaian Bahan Jasa | Persediaan Bahan Jasa |
| Penjualan non-tunai (transfer/QRIS) | Bank/Kas Digital | (sama seperti di atas) |
| Penjualan kredit | Piutang Usaha | (sama seperti di atas) |
| Pelunasan piutang | Kas/Bank | Piutang Usaha |
| Cetak gagal / bahan rusak | Beban Bahan Rusak | Persediaan Bahan Jasa |
| Barang etalase rusak/kadaluarsa | Beban Barang Rusak | Persediaan Barang Dagang |
| Transfer etalase → jasa | Persediaan Bahan Jasa | Persediaan Barang Dagang |
| Pembelian barang (tunai/kredit) | Persediaan Barang Dagang | Kas / Utang Usaha |
| Pembelian bahan jasa | Persediaan Bahan Jasa | Kas / Utang Usaha |
| Bayar utang | Utang Usaha | Kas/Bank |
| Biaya operasional | Beban [kategori] | Kas/Bank |
| Opname: selisih kurang | Beban Selisih Persediaan | Persediaan terkait |
| Opname: selisih lebih | Persediaan terkait | Pendapatan Selisih Persediaan |
| Selisih kas shift (kurang) | Selisih Kas (beban) | Kas |
| Selisih kas shift (lebih) | Kas | Selisih Kas (pendapatan lain) |
| Void/retur | Jurnal pembalik dari jurnal asal | |
| Setoran modal | Kas | Modal |
| Penyusutan (penyesuaian) | Beban Penyusutan | Akumulasi Penyusutan |

---

## 8. Usulan Bagan Akun Awal (SAK-EMKM, dapat diedit)

| Kode | Akun | Tipe |
|---|---|---|
| 1-1100 | Kas | Aset |
| 1-1200 | Bank / Kas Digital | Aset |
| 1-1300 | Piutang Usaha | Aset |
| 1-1400 | Persediaan Barang Dagang | Aset |
| 1-1500 | Persediaan Bahan Jasa | Aset |
| 1-1600 | Perlengkapan | Aset |
| 1-2100 | Peralatan & Mesin (mesin fotocopy, printer, dll.) | Aset |
| 1-2190 | Akumulasi Penyusutan Peralatan | Aset (kontra) |
| 2-1100 | Utang Usaha | Kewajiban |
| 2-1200 | Utang Lain-lain | Kewajiban |
| 3-1000 | Modal Tefa | Ekuitas |
| 3-2000 | Laba Ditahan | Ekuitas |
| 3-3000 | Ikhtisar Laba Rugi | Ekuitas (sementara) |
| 3-4000 | Setoran/Pengembalian ke Sekolah | Ekuitas |
| 4-1100 | Penjualan Barang Dagang | Pendapatan |
| 4-2100 | Pendapatan Jasa Fotocopy | Pendapatan |
| 4-2200 | Pendapatan Jasa Print | Pendapatan |
| 4-2300 | Pendapatan Jasa Laminating | Pendapatan |
| 4-2400 | Pendapatan Jasa Jilid | Pendapatan |
| 4-2900 | Pendapatan Jasa Lainnya | Pendapatan |
| 4-9100 | Diskon Penjualan | Pendapatan (kontra) |
| 4-9200 | Retur Penjualan | Pendapatan (kontra) |
| 5-1100 | HPP Barang Dagang | HPP |
| 5-2100 | Beban Pemakaian Bahan Jasa | HPP |
| 5-2200 | Beban Bahan Rusak (cetak gagal) | HPP |
| 5-3100 | Beban Barang Rusak/Kadaluarsa | HPP |
| 6-1100 | Beban Listrik | Beban |
| 6-1200 | Beban Servis & Perawatan Mesin | Beban |
| 6-1300 | Beban Perlengkapan Operasional | Beban |
| 6-1400 | Beban Honor/Insentif Petugas | Beban |
| 6-1500 | Beban Penyusutan | Beban |
| 6-1900 | Beban Lain-lain | Beban |
| 6-2100 | Beban Selisih Persediaan | Beban |
| 7-1100 | Pendapatan Lain-lain | Pendapatan lain |
| 7-2100 | Selisih Kas | Beban/Pendapatan lain |

Akun yang dipakai posting otomatis ditandai **akun sistem** dan tidak boleh dihapus; hanya boleh diganti pemetaannya di Pengaturan.

---

## 9. Spesifikasi UI/UX

### 9.1 Prinsip
BESTCO adalah alat kerja yang dipakai berulang oleh siswa di jam sibuk, sekaligus alat belajar akuntansi. Desain harus **tenang, padat informasi, cepat dipindai, dan terasa buatan manusia** — bukan template.

### 9.2 Larangan eksplisit ("anti AI-slop")
- Tidak ada gradasi ungu-biru, glow, glassmorphism, blob, atau hero dekoratif.
- Tidak ada emoji sebagai ikon atau salam ("Halo 👋"). Ikon memakai **satu** set konsisten (Lucide atau Phosphor), ukuran/ketebalan seragam.
- Tidak ada grid kartu rata dengan `rounded-2xl` + `shadow-xl` di mana-mana. Pisahkan area dengan garis 1px, spasi, dan hierarki tipografi.
- Tidak ada "stat card" generik 4 kotak berisi ikon bulat berwarna. Angka penting boleh tampil sebagai baris ringkasan bertipografi kuat dengan konteks (perbandingan, satuan).
- Tidak ada teks marketing, placeholder "Lorem ipsum", atau copy kosong ("Kelola bisnis Anda dengan mudah"). Copy singkat, spesifik, dalam bahasa Indonesia sehari-hari yang dipakai di toko.
- Tidak ada animasi berlebihan; transisi ≤ 150 ms hanya untuk umpan balik (hover, fokus, buka dialog).
- Tidak ada tabel yang buruk: kolom angka rata kanan, header lengket, baris padat tapi terbaca.

### 9.3 Arah visual
- **Warna:** biru tinta (deep ink blue) sebagai aksen utama dengan netral abu kebiruan hangat-dingin yang halus untuk latar/garis/teks. Hindari biru Tailwind default murni (`blue-500`); definisikan palet sendiri (token `--brand-50…950`). Semantik status terpisah dan konsisten: sukses, peringatan, bahaya, info; jangan memakai warna brand untuk status.
- **Tipografi:** satu keluarga sans humanis untuk UI (mis. *IBM Plex Sans* atau *Instrument Sans*) dan satu mono/tabular untuk angka dan kode (mis. *IBM Plex Mono*/*JetBrains Mono*); **angka tabular** (`font-variant-numeric: tabular-nums`) di semua nominal. Font dihosting lokal (jaringan sekolah bisa terbatas). Skala tipografi tegas: judul halaman, judul bagian, label, isi, keterangan.
- **Bentuk:** radius kecil (4–6 px), garis 1 px, bayangan hanya untuk elemen melayang (menu, dialog). Kepadatan kontrol 36–40 px di admin; target sentuh ≥ 48 px di layar kasir.
- **Format angka:** `Rp 1.250.000`; negatif dalam kurung `(1.250.000)` pada laporan akuntansi; tanggal `30 Okt 2026`; kuantitas tanpa nol desimal tak perlu.
- **Gaya laporan akuntansi:** meniru buku besar kertas — garis tipis horizontal, kolom Debit/Kredit/Saldo bergaris, total digarisbawahi tunggal/ganda, tipografi mono untuk angka. Harus nyaman dicetak A4 hitam-putih.

### 9.4 Tata letak
- **Admin:** sidebar kiri dikelompokkan (Operasional · Katalog · Persediaan · Keuangan · Akuntansi · Laporan · Pengaturan), dapat diciutkan; bar atas tipis dengan status shift (badge "Shift terbuka · Bu Rina · 3 siswa"), pencarian global (⌘/Ctrl+K), menu pengguna.
- **Kasir:** layout dua kolom — kiri pencarian + pintasan layanan + hasil; kanan keranjang & total yang selalu terlihat; area bayar besar. Tanpa sidebar penuh (mode fokus), tombol keluar ke admin bila berhak.
- **Responsif:** kasir tablet 10"+ prioritas; admin desktop; laporan dapat discroll horizontal di layar kecil tanpa memecah tabel.

### 9.5 Pola interaksi
- Dialog konfirmasi untuk aksi merusak (void, tutup shift) menyebut dampak konkret ("Stok 12 lembar akan dikembalikan, jurnal pembalik dibuat").
- Validasi inline, pesan galat spesifik dan menyarankan tindakan.
- Empty state informatif dengan satu tindakan jelas ("Belum ada layanan. Tambah layanan pertama").
- Keadaan loading memakai skeleton ringan; hindari spinner layar penuh.
- **Aksesibilitas:** kontras WCAG AA, fokus terlihat, navigasi keyboard penuh di kasir, label form jelas, tidak bergantung hanya pada warna.
- Mode gelap: opsional (fase akhir), tidak mengorbankan kualitas mode terang.

### 9.6 Kustomisasi tema (Superadmin)
- Input warna utama → algoritma menghasilkan skala 50–950 dalam ruang warna OKLCH, memastikan teks pada tombol primer memenuhi kontras AA; bila gagal, otomatis menyesuaikan warna teks/gelap.
- Implementasi: CSS variable `--color-brand-*` dalam `@theme` Tailwind v4; nilai dari `settings` di-inject pada `<style>` layout; cache.
- Pratinjau langsung sebelum simpan; tombol "Kembalikan ke biru bawaan".
- Semantik status tidak ikut berubah.

---

## 10. Rekomendasi Teknologi

| Lapisan | Pilihan | Alasan |
|---|---|---|
| Backend | **Laravel 13**, PHP 8.3+ (cek persyaratan resmi saat instalasi) | Sesuai permintaan |
| DB | **MySQL 8.x**, InnoDB, utf8mb4, `DECIMAL` untuk uang | Sesuai permintaan, transaksi ACID |
| Frontend | **Livewire (versi terbaru yang kompatibel Laravel 13) + Alpine.js** | Interaktif tanpa SPA berat; tim PHP lebih mudah merawat. Alternatif: Inertia + Vue bila ingin SPA |
| CSS | **Tailwind CSS v4** via Vite, komponen Blade sendiri (tanpa library UI siap pakai yang berpenampilan generik) | Kendali penuh agar tidak terlihat template |
| Hak akses | `spatie/laravel-permission` | Standar de facto |
| Audit | `spatie/laravel-activitylog` | Jejak perubahan |
| PDF | `spatie/laravel-pdf` (Chromium) atau `barryvdh/laravel-dompdf` | Laporan/struk |
| Excel | `maatwebsite/excel` | Impor/ekspor |
| Backup | `spatie/laravel-backup` | Cadangan terjadwal |
| Grafik | Chart.js (ringan, via Alpine) | Dashboard |
| Antrian | Database queue | Ekspor besar, backup |
| Kualitas | Pest, Laravel Pint, Larastan | Tes & konsistensi |
| Struk | CSS print 58/80 mm; ESC/POS langsung opsional (fase lanjutan) | Ringkas |
| Barcode | Pemindai tipe keyboard-wedge (tanpa driver) | Sederhana |
| Deployment | Server lokal sekolah (Laragon/Docker) atau VPS; HTTPS bila publik | Jaringan sekolah |

Pola kode: *Action/Service classes* untuk logika bisnis (`CheckoutSale`, `PostJournal`, `TransferMaterial`, `RecordWaste`, `ResolveServicePrice`), Form Request, Policy, Enum PHP untuk status/tipe, event & listener hanya bila memberi nilai (posting jurnal dijalankan **sinkron dalam transaksi yang sama**, bukan antrian, agar konsisten).

---

## 11. Persyaratan Non-Fungsional

- **Integritas:** semua operasi uang/stok dalam DB transaction; `lockForUpdate`; idempotency pada checkout; uji konkurensi dua kasir.
- **Keamanan:** hash password (bcrypt/argon), CSRF, rate limit login & PIN, otorisasi via Policy di setiap aksi, validasi server, pelindung mass-assignment, header keamanan, log akses.
- **Kinerja:** pencarian kasir < 200 ms untuk 10.000 item; halaman laporan 12 bulan < 3 s dengan indeks yang tepat dan agregasi di SQL.
- **Ketersediaan data:** backup harian otomatis + ekspor manual; uji restore.
- **Lokalisasi:** semua teks Indonesia; file lang terpisah; format tanggal/angka lokal.
- **Pemeliharaan:** cakupan tes untuk logika akuntansi & harga ≥ 90%, CI sederhana (Pint + Larastan + Pest).
- **Kemudahan belajar:** tooltip istilah akuntansi, tautan "lihat jurnalnya" pada setiap transaksi.

---

## 12. Data Awal (Seeder)

Peran & izin; Superadmin & Admin awal; bagan akun (bagian 8); satuan; kategori; layanan & tarif contoh (bagian 4 & 5.4) beserta BOM (tinta 0,05 g/lembar); bahan contoh; 30 produk ATK contoh; 1 kelas dan siswa demo; jadwal piket demo. Seeder demo dapat dimatikan untuk produksi.

---

## 13. Roadmap Pengembangan

| Fase | Isi | Hasil yang dapat diuji |
|---|---|---|
| 0 | Fondasi: proyek, auth, peran, layout, design system, tema | Login & kerangka UI |
| 1 | Master: pengguna, kelas/siswa, produk, bahan, layanan, BOM, tier harga, COA | Data master lengkap |
| 2 | Piket & shift | Buka/tutup shift, pengganti |
| 3 | Persediaan: pembelian, kartu stok, opname | Stok akurat |
| 4 | **POS** + jurnal otomatis penjualan + struk | Transaksi end-to-end |
| 5 | Transfer bahan, barang rusak/gagal | Alur kurang bahan & gagal cetak |
| 6 | Biaya, piutang, utang | Kas & kredit |
| 7 | Akuntansi: jurnal, buku besar, BKU, neraca saldo, L/R, neraca, arus kas, penutupan | Laporan lengkap |
| 8 | Laporan operasional, dashboard, ekspor | Pelaporan |
| 9 | Audit, backup, pengaturan, polesan UX, uji performa | Siap rilis |

---

## 14. Skenario Penerimaan (Acceptance)

1. **Tier fotocopy:** 2 lbr = Rp 1.000; 50 lbr = Rp 15.000; 100 lbr = Rp 28.500 (5%); 1.000 lbr = Rp 250.000. Jurnal: Kas Dr; Pendapatan Fotocopy Cr (bruto); Diskon Penjualan Dr bila ada potongan.
2. **Konsumsi tinta:** jual 100 lembar + 5 gagal → tinta berkurang 5,25 g; kertas berkurang 105 lembar; Beban Bahan Rusak mencatat 5 lembar × HPP/lembar.
3. **Yield:** tinta 1 kg → estimasi 20.000 lembar; setelah 4.000 lembar, estimasi sisa 16.000.
4. **Kertas jasa habis di tengah pekerjaan:** kasir menambah 300 lbr, stok jasa 120 → dialog kekurangan 180; siswa ajukan ambil dari etalase; guru approve dengan PIN; 1 rim (500 lbr) keluar dari etalase, 320 lbr masuk bahan jasa (nilai sama dengan HPP rim), 180 terpakai; jurnal transfer seimbang; transaksi lanjut.
5. **Laminating:** besar Rp 5.000, kecil Rp 3.000; masing-masing memotong pouch sesuai ukuran.
6. **Piket:** siswa terjadwal tidak hadir → diganti siswa lain; hanya pengganti yang dapat kasir; siswa tak terdaftar ditolak.
7. **Tutup shift:** kas seharusnya Rp 350.000, fisik Rp 349.000 → selisih −1.000 dijurnal ke Selisih Kas.
8. **Void:** transaksi hari sama di-void oleh guru piket → stok barang kembali, jurnal pembalik dibuat, struk void tercetak, log tercatat.
9. **Neraca balance:** Aset = Kewajiban + Ekuitas pada setiap tanggal; Laba bersih di L/R = perubahan Laba Ditahan setelah penutupan.
10. **Rekonsiliasi:** saldo akun persediaan = total kartu stok untuk kedua domain; saldo Kas = saldo akhir BKU.
11. **Periode terkunci:** posting ke periode terkunci ditolak dengan pesan jelas.
12. **Tema:** Superadmin mengganti warna utama → seluruh UI berubah, kontras tombol tetap AA, status (sukses/bahaya) tidak berubah.

---

## 15. Asumsi & Hal yang Perlu Dikonfirmasi

Asumsi yang dipakai di dokumen ini (ubah bila tidak sesuai):
1. Harga contoh fotocopy (Rp 300 dasar, tier 5%/10%/Rp 250) **hanya seed**; nilai riil ditentukan Admin.
2. Tier berlaku ke seluruh lembar, tidak menumpuk, dan tidak progresif.
3. Tinta fotocopy dan tinta printer dianggap bahan berbeda; print memakai BOM sendiri.
4. Satu shift per hari; beberapa shift per hari dapat diaktifkan di pengaturan bila diperlukan.
5. Metode persediaan rata-rata bergerak.
6. Pembayaran non-tunai hanya dicatat, bukan terintegrasi.
7. Siswa memiliki akun masing-masing (tidak berbagi akun kelas).
8. Pencatatan perpajakan tidak termasuk.

Pertanyaan terbuka: ukuran kertas/layanan lain apa saja yang dijual; apakah ada pelanggan tetap (guru/instansi) yang butuh piutang; apakah sekolah menetapkan bagan akun/format BKU khusus (mis. mengikuti format BOS/BLUD); jumlah perangkat kasir; apakah tersedia printer struk thermal.
