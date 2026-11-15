# RateYourMie — Website Rating Mie Instan & Tierlist

Website komunitas tempat penggemar mie instan memberi penilaian terstruktur pada **4 aspek (Rasa, Tekstur, Porsi, Harga)** serta menyusun **Tierlist Pribadi (S sampai F)** dengan drag-and-drop maupun kontrol layar sentuh. Dilengkapi **Tierlist Komunitas otomatis**, **Peringkat Top 10**, dan **Panel Admin CRUD**.

Dibangun menggunakan **PHP native (tanpa framework)**, **MySQL/MariaDB via PDO (prepared statements)**, dan **HTML/CSS/JavaScript murni (tanpa library)**.

---

## 1. Cara Instalasi di XAMPP / Laragon

1. **Salin Folder Proyek**
   - **XAMPP:** Letakkan folder proyek di dalam `htdocs` (misalnya `C:\xampp\htdocs\rating-mie` atau `D:\xampp\htdocs\rating-mie`).
   - **Laragon:** Letakkan folder proyek di dalam `www` (misalnya `C:\laragon\www\rating-mie`).

2. **Nyalakan Server Apache & MySQL**
   - Buka Control Panel XAMPP atau Laragon, lalu jalankan layanan **Apache** dan **MySQL / MariaDB**.

3. **Buat Database & Impor SQL**
   - Buka **phpMyAdmin** (`http://localhost/phpmyadmin`) atau terminal MySQL, lalu buat database bernama **`rating_mie`**:
     ```sql
     CREATE DATABASE rating_mie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     ```
   - Impor file skema tabel dan data awal secara berurutan:
     1. [`database/schema.sql`](file:///e:/Tugas%20Vscode/PemWeb/Rating%20Mie/database/schema.sql)
     2. [`database/seed.sql`](file:///e:/Tugas%20Vscode/PemWeb/Rating%20Mie/database/seed.sql)
   - Melalui CLI MySQL:
     ```bash
     mysql -u root -e "CREATE DATABASE IF NOT EXISTS rating_mie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
     mysql -u root rating_mie < database/schema.sql
     mysql -u root rating_mie < database/seed.sql
     ```

4. **Sesuaikan Konfigurasi Database (Jika Perlu)**
   - Buka [`config/config.php`](file:///e:/Tugas%20Vscode/PemWeb/Rating%20Mie/config/config.php) jika kredensial MySQL lokal Anda berbeda dari bawaan (`DB_HOST = 127.0.0.1`, `DB_NAME = rating_mie`, `DB_USER = root`, `DB_PASS = ''`).

5. **Buka di Peramban**
   - Akses `http://localhost/rating-mie/` (atau jalankan server bawaan PHP dari direktori proyek dengan `php -S 127.0.0.1:8080`).

---

## 2. Kredensial Akun Awal (Seed Data)

> **Penting:** Segera ganti password akun Admin setelah instalasi di lingkungan produksi/demo.

| Peran | Username | Email | Password Awal |
| --- | --- | --- | --- |
| **Admin** | `admin` | `admin@warkopmietier.local` | `AdminMie123!` |
| **User** | `budi_mie` | `budi@warkopmietier.local` | `UserMie123!` |
| **User** | `siti_ramen` | `siti@warkopmietier.local` | `UserMie123!` |
| **User** | `andi_warkop` | `andi@warkopmietier.local` | `UserMie123!` |
| **User** | `dewi_pedas` | `dewi@warkopmietier.local` | `UserMie123!` |

---

## 3. Struktur Folder Proyek

```text
config/
  config.php          # Konstanta aplikasi (DB, TIER_THRESHOLDS, MIN_RATERS, MAX_TIERLISTS, upload)
  database.php        # Fungsi db() PDO singleton & pembacaan tabel settings
includes/
  header.php          # Navigasi utama responsif & flash messages
  footer.php          # Footer situs
  auth.php            # Session HttpOnly/SameSite=Lax, current_user(), require_login(), require_admin(), rate limit
  csrf.php            # csrf_token(), csrf_field(), csrf_verify(), csrf_require()
  helpers.php         # e() anti-XSS, redirect(), flash(), json_response(), render_noodle_visual()
  noodle_repo.php     # Query katalog, filter, detail, AVG() 4 aspek, Tierlist Komunitas, Top 10
  tier.php            # score_to_tier() dan metadata baris S sampai F
admin/
  index.php           # Dashboard ringkas (total mie, pengguna, rating, ulasan terbaru)
  noodles.php         # Tabel daftar mie + pencarian + pagination
  noodle_form.php     # Form tambah/ubah mie + validasi upload finfo_file
  noodle_delete.php   # Hapus mie (POST + CSRF only)
  reviews.php         # Moderasi & hapus ulasan pengguna
api/
  rating_save.php     # Simpan/update rating 4 aspek (INSERT ... ON DUPLICATE KEY UPDATE)
  rating_delete.php   # Hapus rating milik pengguna sendiri
  tierlist_create.php # Buat tierlist baru (batas maksimal 10 per user)
  tierlist_save.php   # Simpan susunan kartu S-F dalam transaksi database
  tierlist_rename.php # Ubah judul tierlist milik sendiri
  tierlist_delete.php # Hapus tierlist milik sendiri
assets/
  css/style.css       # Stylesheet mobile-first
  js/rating.js        # Kalkulasi live skor 4 aspek & fetch() rating
  js/tierlist.js      # HTML5 Drag and Drop + dropdown layar sentuh & fetch() tierlist
uploads/
  .htaccess           # Penonaktif eksekusi script PHP di direktori upload
database/
  schema.sql          # Skema tabel MySQL/MariaDB (InnoDB, utf8mb4)
  seed.sql            # 18 mie populer, 1 akun admin, 4 akun user, data rating & tierlist contoh
index.php             # Katalog publik, pencarian nama, filter brand/rasa/negara, pagination
noodle.php            # Detail mie, grafik 4 aspek, daftar ulasan, form rating
tierlists.php         # Halaman Tierlist Saya (kelola hingga 10 tierlist)
tierlist.php          # Penyusun tierlist pribadi S-F (drag-and-drop & layar sentuh)
community.php         # Tierlist Komunitas otomatis (syarat minimal 3 penilai)
top10.php             # Peringkat Top 10 mie berdasarkan skor tertinggi
profile.php           # Profil pengguna, daftar tierlist pribadi, dan riwayat rating
login.php             # Halaman login dengan proteksi brute-force
register.php          # Halaman pendaftaran pengguna baru
logout.php            # Endpoint logout (POST + CSRF)
TESTING.md            # Dokumentasi & daftar uji keamanan dan fungsional (Tahap 7)
```

---

## 4. Cara Menjalankan Pengujian

Lihat rincian skenario dan hasil pengujian pada file [`TESTING.md`](file:///e:/Tugas%20Vscode/PemWeb/Rating%20Mie/TESTING.md).
Untuk memeriksa sintaks seluruh file PHP melalui terminal:
```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```
