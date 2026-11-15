# AGENT_PLAN: Tahap Pengerjaan untuk Agent

Panduan eksekusi untuk agent coding. Sumber kebenaran kebutuhan ada di `PRD.md` (v1.1). Jika ada konflik, `PRD.md` yang menang; jika PRD ambigu, berhenti dan tanyakan, jangan menebak.

## Aturan Umum (berlaku di semua tahap)

**Stack:** PHP native (tanpa framework), HTML/CSS/JavaScript murni (tanpa library), MySQL/MariaDB lewat PDO. Target XAMPP/Laragon.

**Wajib:**
- Semua query memakai PDO prepared statements. Tidak ada string concatenation ke SQL.
- Semua output ke HTML di-escape dengan `htmlspecialchars($s, ENT_QUOTES, 'UTF-8')` (buat helper `e()`).
- Token CSRF di semua form dan di setiap `fetch()` yang mengubah data (kirim lewat header `X-CSRF-Token` atau field body). Aksi hapus hanya lewat POST.
- Password: `password_hash()` / `password_verify()`. Panggil `session_regenerate_id(true)` setelah login berhasil.
- Rata-rata rating dihitung dengan `AVG()` di query, tidak pernah disimpan di kolom.
- Endpoint `api/` mengembalikan JSON (`Content-Type: application/json`) dengan bentuk `{ "ok": true, "data": ... }` atau `{ "ok": false, "error": "pesan" }` dan kode HTTP yang sesuai (401, 403, 422, 404).
- Validasi selalu di server; validasi di JavaScript hanya untuk kenyamanan.
- Kode dan nama tabel/kolom dalam bahasa Inggris; teks yang tampil ke pengguna dalam bahasa Indonesia.

**Dilarang:** framework/library PHP atau JS, Composer, npm, inline `onclick` dengan data pengguna, menyimpan rata-rata sebagai kolom, mengubah kebutuhan di PRD tanpa persetujuan.

**Alur kerja per tahap:** kerjakan berurutan; di akhir tiap tahap jalankan checklist "Selesai jika" dan perbaiki sebelum lanjut. Setelah tiap tahap, ringkas apa yang dibuat dan apa yang belum teruji.

## Struktur Folder Target

```
config/
  config.php          # konstanta (DB, TIER_THRESHOLDS, MIN_RATERS, MAX_TIERLISTS, upload)
  database.php        # fungsi db() mengembalikan PDO singleton
includes/
  header.php  footer.php
  auth.php            # start session, current_user(), require_login(), require_admin()
  csrf.php            # csrf_token(), csrf_field(), csrf_verify()
  helpers.php         # e(), redirect(), flash(), json_response()
  noodle_repo.php     # query mie + AVG rating
  tier.php            # score_to_tier()
admin/
  index.php           # dashboard
  noodles.php         # daftar + cari + pagination
  noodle_form.php     # tambah/ubah
  noodle_delete.php   # POST only
  reviews.php         # moderasi ulasan
api/
  rating_save.php  rating_delete.php
  tierlist_create.php  tierlist_save.php  tierlist_rename.php  tierlist_delete.php
assets/
  css/style.css
  js/tierlist.js  js/rating.js
uploads/              # gambar mie (nama acak)
database/
  schema.sql  seed.sql
index.php  noodle.php  tierlist.php  tierlists.php  community.php  top10.php
profile.php  login.php  register.php  logout.php  README.md
```

---

## Tahap 0: Setup Proyek (setengah hari)

**Tugas**
- [ ] Buat struktur folder di atas (file kosong/stub boleh).
- [ ] `config/config.php`: definisikan `TIER_THRESHOLDS` (S 4.5, A 4.0, B 3.5, C 3.0, D 2.0, F 0), `MIN_RATERS = 3`, `MAX_TIERLISTS = 10`, `UPLOAD_MAX_BYTES = 2097152`, `UPLOAD_ALLOWED = ['image/jpeg','image/png','image/webp']`.
- [ ] `includes/header.php` dan `footer.php` dengan navigasi (Beranda, Tierlist Komunitas, Top 10, Login/Register atau Tierlist Saya/Profil/Logout; menu Admin hanya untuk admin).
- [ ] `assets/css/style.css` dasar, mobile-first.
- [ ] `.htaccess` di `uploads/` yang menonaktifkan eksekusi PHP (`php_flag engine off` atau `RemoveHandler .php`).

**Selesai jika:** halaman `index.php` tampil dengan header/footer tanpa error di XAMPP/Laragon.

## Tahap 1: Database dan Koneksi PDO (1 hari)

**Tugas**
- [ ] `database/schema.sql` dengan skema di bawah.
- [ ] `config/database.php`: PDO dengan `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES = false`, charset `utf8mb4`. Jangan tampilkan pesan error DB ke pengguna.
- [ ] `database/seed.sql`: 15-20 mie populer (nama, brand, rasa, negara asal, harga, deskripsi), 1 akun admin (password di-hash, dokumentasikan kredensial awal di README), dan beberapa akun user contoh.
- [ ] Tabel `settings` opsional; jika dipakai, `TIER_THRESHOLDS` dan `MIN_RATERS` dibaca dari sana dengan fallback ke konstanta.

**Skema acuan**

```sql
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  email_verified TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE noodles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  brand VARCHAR(100) NOT NULL,
  flavor VARCHAR(100) NOT NULL,
  origin_country VARCHAR(80) NOT NULL,
  price INT UNSIGNED NOT NULL DEFAULT 0,
  description TEXT,
  image VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ratings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  noodle_id INT UNSIGNED NOT NULL,
  taste TINYINT UNSIGNED NOT NULL,
  texture TINYINT UNSIGNED NOT NULL,
  portion TINYINT UNSIGNED NOT NULL,
  `value` TINYINT UNSIGNED NOT NULL,
  review TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_noodle (user_id, noodle_id),
  CONSTRAINT chk_taste CHECK (taste BETWEEN 1 AND 5),
  CONSTRAINT chk_texture CHECK (texture BETWEEN 1 AND 5),
  CONSTRAINT chk_portion CHECK (portion BETWEEN 1 AND 5),
  CONSTRAINT chk_value CHECK (`value` BETWEEN 1 AND 5),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (noodle_id) REFERENCES noodles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tierlists (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(100) NOT NULL,
  category VARCHAR(50) DEFAULT NULL,
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  share_token CHAR(32) DEFAULT NULL UNIQUE,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tierlist_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tierlist_id INT UNSIGNED NOT NULL,
  noodle_id INT UNSIGNED NOT NULL,
  tier ENUM('S','A','B','C','D','F') NOT NULL,
  position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY uq_tierlist_noodle (tierlist_id, noodle_id),
  FOREIGN KEY (tierlist_id) REFERENCES tierlists(id) ON DELETE CASCADE,
  FOREIGN KEY (noodle_id) REFERENCES noodles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
  key_name VARCHAR(50) PRIMARY KEY,
  key_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Catatan: `value` ditulis dengan backtick di SQL. `CHECK` baru ditegakkan di MySQL 8.0.16+ dan MariaDB 10.2.1+; validasi di server tetap wajib.

**Selesai jika:** `schema.sql` dan `seed.sql` berjalan bersih di database kosong; halaman uji sederhana berhasil mengambil daftar mie lewat PDO.

## Tahap 2: Autentikasi, Role, dan Session (2 hari)

**Tugas**
- [ ] `includes/auth.php`: `session_start()` dengan cookie `httponly` dan `samesite=Lax`; `current_user()`, `require_login()` (redirect ke `login.php`, simpan tujuan awal), `require_admin()` (403 jika bukan admin).
- [ ] `includes/csrf.php`: token acak via `random_bytes`, disimpan di session, dibandingkan dengan `hash_equals`.
- [ ] `register.php` (FU-01): username (3-50 karakter, huruf/angka/underscore), email (`filter_var` + unik), password (minimal 8 karakter) + konfirmasi. Role selalu `user`; role tidak boleh datang dari input.
- [ ] `login.php` (FU-02): `password_verify`, `session_regenerate_id(true)`, pesan error generik ("Username atau password salah").
- [ ] `logout.php`: via POST dengan CSRF; hancurkan session.
- [ ] Batasi percobaan login (misalnya 5 gagal per 15 menit per sesi/IP) untuk mengurangi brute force.

**Selesai jika:**
- Register → login → logout berjalan; password di DB berupa hash.
- Membuka halaman yang butuh login tanpa login diarahkan ke `login.php` (FU-03).
- User biasa dan pengunjung yang membuka `admin/*` ditolak (FU-04).
- Form tanpa token CSRF yang valid ditolak.

## Tahap 3: CRUD Mie (Admin) dan Katalog Publik (2-3 hari)

**Tugas**
- [ ] `admin/noodles.php`: tabel dengan pencarian nama dan pagination (FA-02).
- [ ] `admin/noodle_form.php`: tambah/ubah semua field (FA-01) dengan validasi (nama, brand, rasa, negara wajib; harga bilangan bulat ≥ 0).
- [ ] Upload gambar: validasi MIME memakai `finfo_file` (bukan `$_FILES['type']`), ekstensi jpg/png/webp, maksimal 2 MB, nama file diganti acak (`bin2hex(random_bytes(16))` + ekstensi), simpan di `uploads/`. Saat gambar diganti atau mie dihapus, hapus file lama.
- [ ] `admin/noodle_delete.php`: hanya POST + CSRF, dengan konfirmasi di sisi klien (FA-03).
- [ ] `index.php` (FR-01, FR-02): kartu mie dengan gambar, brand, rasa, rata-rata skor akhir; pencarian nama; filter brand, rasa, negara asal; pagination.
- [ ] `noodle.php?id=` (FR-03): deskripsi, harga, skor akhir, rata-rata per aspek, jumlah penilai, daftar ulasan (bagian form rating dikerjakan di Tahap 4).
- [ ] `includes/noodle_repo.php`: query katalog dengan `LEFT JOIN ratings` dan `AVG((taste+texture+portion+\`value\`)/4)`, `COUNT(r.id)`.

**Selesai jika:** admin menambah mie baru dan langsung muncul di katalog tanpa ubah kode; upload file non-gambar atau > 2 MB ditolak; mie tanpa rating tampil "Belum ada rating" (bukan 0).

## Tahap 4: Rating per Aspek dan Ulasan (2 hari)

**Tugas**
- [ ] Form rating di `noodle.php` untuk user login: 4 aspek (rasa, tekstur, porsi, harga), tiap aspek 1-5, plus ulasan singkat (maksimal 500 karakter). Tampilkan form yang sudah terisi jika user sudah pernah menilai.
- [ ] `api/rating_save.php`: validasi login, CSRF, `noodle_id` ada, tiap aspek bilangan bulat 1-5. Simpan dengan `INSERT ... ON DUPLICATE KEY UPDATE` memanfaatkan `UNIQUE(user_id, noodle_id)` (FR-05).
- [ ] `api/rating_delete.php`: hapus hanya rating milik user sendiri.
- [ ] `assets/js/rating.js`: kirim via `fetch()` dengan header CSRF, perbarui rata-rata di halaman tanpa reload atau reload ringan.
- [ ] Batasi frekuensi kirim rating per user (misalnya maksimal 20 per jam).
- [ ] `admin/reviews.php` (FA-04): daftar ulasan dengan tombol hapus (POST + CSRF + konfirmasi).
- [ ] Ulasan ditampilkan dengan `e()`; tidak ada HTML mentah.

**Selesai jika:** user tidak bisa membuat rating kedua untuk mie yang sama (hanya terjadi update); nilai di luar 1-5 atau aspek yang kosong ditolak dengan 422; rata-rata berubah segera setelah rating baru; admin dapat menghapus ulasan; ulasan berisi `<script>` tampil sebagai teks.

## Tahap 5: Tierlist Pribadi dengan Drag and Drop (3-4 hari)

**Tugas**
- [ ] `tierlists.php` (Tierlist Saya, FT-07): daftar tierlist user, tombol buat baru, ubah judul, hapus. Batas `MAX_TIERLISTS` (10) ditegakkan di server.
- [ ] `api/tierlist_create.php`, `tierlist_rename.php`, `tierlist_delete.php`: cek kepemilikan (`user_id` = user login) di setiap endpoint.
- [ ] `tierlist.php?id=`: baris S, A, B, C, D, F dan area "belum dikelompokkan" berisi mie yang belum masuk tier. Hanya pemilik yang bisa mengubah.
- [ ] `assets/js/tierlist.js`: drag and drop memakai HTML5 Drag and Drop API (FT-01). Wajib ada alternatif layar sentuh: tiap kartu punya dropdown/tombol "Pindah ke tier" (S-F atau belum dikelompokkan), sesuai risiko di PRD.
- [ ] `api/tierlist_save.php` (FT-02): terima `{ tierlist_id, items: [{noodle_id, tier, position}] }`; validasi tier ∈ {S,A,B,C,D,F}, `noodle_id` ada, tidak ada duplikat mie dalam satu tierlist; dalam satu transaksi hapus item lama lalu sisipkan yang baru.
- [ ] Muat susunan tersimpan saat halaman dibuka; tampilkan status simpan ("Tersimpan", "Gagal menyimpan, coba lagi").

**Selesai jika:** susunan tersimpan dan identik setelah logout-login; user A tidak bisa membuka atau mengubah tierlist user B lewat manipulasi `id`; percobaan membuat tierlist ke-11 ditolak; seluruh fungsi bisa dipakai di layar sentuh tanpa drag.

## Tahap 6: Tierlist Komunitas dan Top 10 (1-2 hari)

**Tugas**
- [ ] `includes/tier.php`: `score_to_tier(float $score): string` memakai `TIER_THRESHOLDS` (S ≥ 4,5; A ≥ 4,0; B ≥ 3,5; C ≥ 3,0; D ≥ 2,0; selain itu F). Batas bawah inklusif; bandingkan dengan nilai yang sudah dibulatkan 2 desimal.
- [ ] `community.php` (FT-03): hitung skor akhir per mie dengan `AVG()` dan `HAVING COUNT(*) >= MIN_RATERS`, kelompokkan ke tier lewat `score_to_tier()`. Tampilkan juga skor dan jumlah penilai di tiap kartu. Mie di bawah ambang penilai tidak ditampilkan (atau diberi bagian "Belum cukup penilai").
- [ ] `top10.php` (FR-06): 10 mie dengan skor akhir tertinggi dan penilai ≥ `MIN_RATERS`; urutan seri diputuskan oleh jumlah penilai lalu nama.
- [ ] Tautkan ke halaman ini dari navigasi.

**Selesai jika:** menambah/mengubah rating langsung mengubah tier komunitas dan Top 10 tanpa edit manual; mie dengan 1-2 rating tidak muncul; skor tepat 4,5 masuk S dan 4,49 masuk A.

## Tahap 7: Pengujian, Perapihan UI, dan Dokumentasi (2 hari)

**Pengujian manual (catat hasil di README atau `TESTING.md`)**
- [ ] SQL injection: coba `' OR 1=1 --` di login, pencarian, filter, dan parameter `id`.
- [ ] XSS: coba `<script>alert(1)</script>` di username, nama mie, ulasan, judul tierlist; pastikan tampil sebagai teks.
- [ ] CSRF: kirim form dan `fetch()` tanpa token → harus ditolak.
- [ ] Hak akses: user biasa/pengunjung ke `admin/*`; akses endpoint `api/*` tanpa login; mengubah tierlist milik user lain.
- [ ] Integritas: rating ganda, nilai di luar 1-5, tierlist ke-11, mie ganda dalam satu tierlist.
- [ ] Upload: file `.php` yang di-rename `.jpg`, file > 2 MB, file berformat gif.
- [ ] Responsif: ponsel (≈360 px) dan desktop; tierlist dapat dipakai tanpa drag.
- [ ] Kinerja: halaman utama < 3 detik dengan seed data.

**Perapihan dan dokumentasi**
- [ ] Rapikan UI, pesan error dan flash message seragam, state kosong ("Belum ada mie", "Belum ada ulasan").
- [ ] `README.md`: cara instal di XAMPP/Laragon, impor `schema.sql` dan `seed.sql`, akun admin awal (minta diganti), struktur folder, cara menjalankan pengujian.
- [ ] Periksa seluruh kriteria keberhasilan di `PRD.md` bagian 12 satu per satu.

**Selesai jika:** semua kriteria keberhasilan PRD terpenuhi dan tidak ada celah pada pengujian dasar di atas.

## Tahap 8 (Opsional, prioritas rendah): Fitur Tambahan

Kerjakan hanya setelah Tahap 1-7 selesai.

- [ ] **FT-04 Tautan publik:** tombol "Bagikan" menghasilkan `share_token` acak (`bin2hex(random_bytes(16))`) dan menyetel `is_public = 1`; halaman `tierlist.php?token=` hanya-baca; pemilik dapat mencabut tautan.
- [ ] **FT-05 Ekspor gambar:** gambar tierlist digambar ke `<canvas>` lalu diunduh sebagai PNG. Gambar mie berasal dari domain sendiri agar canvas tidak "tainted".
- [ ] **FT-06 Profil:** `profile.php` menampilkan tierlist publik dan riwayat rating pengguna.
- [ ] **FA-05 Dashboard admin:** jumlah mie, pengguna, rating, dan ulasan terbaru.

## Di Luar Cakupan (jangan dikerjakan)

Pengguna menambah mie sendiri, komentar balasan/pertemanan/chat, login sosial, aplikasi mobile native, dan verifikasi email (hanya kolom `email_verified` yang disiapkan).

## Format Laporan Tiap Tahap

Di akhir tahap, laporkan singkat: (1) file yang dibuat/diubah, (2) item "Selesai jika" yang lolos dan yang belum, (3) asumsi yang diambil, (4) pertanyaan yang butuh keputusan.
