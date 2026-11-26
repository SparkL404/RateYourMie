# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

PHP native (tanpa framework), MySQL/MariaDB via PDO (prepared statements, database `rating_mie`), Vanilla HTML/CSS/JavaScript (tanpa library/framework), target lingkungan server lokal XAMPP/Laragon.

## Users

- **Pengunjung (belum login):** Penikmat mie instan yang ingin menelusuri katalog mie, mencari atau memfilter berdasarkan brand/rasa/negara asal, membaca detail dan ulasan, serta melihat peringkat Top 10 dan Tierlist Komunitas otomatis.
- **Pengguna Terdaftar (role `user`):** Penggemar mie instan yang memberi penilaian terstruktur pada 4 aspek (rasa, tekstur, porsi, harga) beserta ulasan singkat, serta menyusun dan mengelola hingga 10 tierlist pribadi (S sampai F) menggunakan drag-and-drop atau kontrol alternatif layar sentuh.
- **Admin (role `admin`):** Pengelola konten yang memelihara katalog mie lewat panel CRUD (tambah, ubah, hapus beserta upload foto kemasan), memantau statistik di Dashboard Admin, dan memoderasi ulasan yang melanggar aturan.

## Product Purpose

Website komunitas tempat penggemar mie instan memberi rating terstruktur dan menyusun tierlist pribadi (baris S hingga F) dalam satu tempat terpadu. Produk ini mengatasi masalah penilaian mie yang tersebar dan pembuatan tierlist yang terpisah dari data rating komunitas. Keberhasilan diukur dari kemudahan pengguna menilai mie, menyusun tierlist pribadi yang persisten, serta pembaruan otomatis Tierlist Komunitas dan Top 10 berdasarkan rata-rata skor aktual.

## Positioning

Menggabungkan katalog ulasan multi-aspek (rasa, tekstur, porsi, harga dengan bobot setara 1–5) dan penyusun tierlist interaktif (S–F) dalam satu aplikasi web terintegrasi, di mana Tierlist Komunitas dan peringkat Top 10 dihitung secara langsung (`AVG()`) dari ulasan pengguna dengan syarat ambang minimal 3 penilai per mie agar representatif.

## Operating Context

Digunakan di peramban desktop maupun ponsel (layar sentuh) saat pengguna ingin mengecek reputasi varian mie instan, mencatat penilaian setelah mencoba mie, atau beradu argumen soal peringkat mie terbaik lewat tierlist. Berjalan di lingkungan lokal XAMPP/Laragon dengan MySQL/MariaDB.

## Capabilities and Constraints

- **Autentikasi & Keamanan:** Satu sistem login dengan role `user` dan `admin`; password di-hash dengan `password_hash()`, regenerasi session ID saat login, pembatasan percobaan login (anti-brute-force), proteksi CSRF pada seluruh form dan request `fetch()` pengubah data, aksi hapus wajib via POST, seluruh query memakai PDO prepared statements, dan seluruh output HTML di-escape (`htmlspecialchars`). Kolom `email_verified` disiapkan di tabel `users` (default 0) untuk pengembangan mendatang.
- **Katalog & Rating:** Pencarian nama, filter brand/rasa/negara asal, dan pagination. Satu pengguna hanya dapat memberi satu rating per mie (`UNIQUE(user_id, noodle_id)`), dapat memperbarui atau menghapus rating miliknya, serta dibatasi frekuensinya. Rata-rata skor dihitung dinamis dengan `AVG()` di SQL dan tidak disimpan sebagai kolom statis.
- **Tierlist Pribadi:** Maksimal 10 tierlist per pengguna (`MAX_TIERLISTS = 10`), satu mie hanya muncul sekali dalam satu tierlist (`UNIQUE(tierlist_id, noodle_id)`), disimpan ke database lewat `fetch()` dalam transaksi, serta wajib memiliki kontrol pilih tier ramah layar sentuh selain HTML5 Drag and Drop.
- **Tierlist Komunitas & Top 10:** Ambang tier komunitas bawaan: S (≥ 4,50), A (4,00–4,49), B (3,50–3,99), C (3,00–3,49), D (2,00–2,99), F (< 2,00), dengan syarat minimal 3 penilai (`MIN_RATERS = 3`).
- **Panel Admin & Upload:** Dashboard ringkas (jumlah mie, pengguna, rating, ulasan terbaru), CRUD mie dengan pencarian dan pagination, moderasi hapus ulasan, serta validasi upload gambar kemasan menggunakan `finfo_file` (JPG/PNG/WebP, maks 2 MB, nama file acak, `.htaccess` penonaktif eksekusi PHP di `uploads/`).
- **Bahasa:** Kode dan skema database dalam bahasa Inggris; seluruh teks antarmuka pengguna dalam bahasa Indonesia.

## Evidence on Hand

- Spesifikasi kebutuhan lengkap di `PRD.md` (v1.1) dan tahapan eksekusi di `AGENT_PLAN.md`.
- Data awal disiapkan melalui `database/seed.sql` berisi 15–20 mie instan populer beserta akun admin dan pengguna contoh.

## Product Principles

1. **Data Terstruktur di Atas Opini Lepas:** Setiap skor akhir mie selalu berasal dari rata-rata empat aspek terukur (rasa, tekstur, porsi, harga) dan baru masuk peringkat komunitas setelah memenuhi ambang jumlah penilai minimum.
2. **Interaksi Setara di Desktop dan Ponsel:** Penyusunan tierlist tidak boleh mengandalkan drag-and-drop semata; pemindahan kartu ke baris S–F harus sama cepat dan nyamannya di layar sentuh melalui kontrol langsung pada kartu.
3. **Keamanan dan Integritas Sejak Dasar:** Tidak ada kompromi pada prepared statements, validasi sisi server, token CSRF, pemeriksaan kepemilikan data, dan sanitasi output.
4. **Kejelasan Katalog dan Kecepatan Pemuatan:** Tanpa ketergantungan framework/library berat, antarmuka memprioritaskan keterbacaan skor, identitas kemasan/brand/rasa, dan waktu muat di bawah 3 detik.
