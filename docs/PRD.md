# PRD: Website Rating Mie Instan dan Tierlist

Product Requirements Document · Versi 1.1 · 15 November 2026

## 1. Ringkasan Produk

Website tempat pengguna memberi rating mie instan dan menyusun tierlist pribadi (S sampai F) dengan drag and drop. Daftar mie disediakan dan dikelola admin lewat CRUD. Mie dinilai dari empat aspek (rasa, tekstur, porsi, harga), dan satu pengguna dapat memiliki beberapa tierlist. Tierlist komunitas dihitung otomatis dari rata-rata rating semua pengguna.

## 2. Latar Belakang dan Masalah

- Penggemar mie instan sering berdebat soal mie terbaik, tetapi tidak ada tempat yang merangkum penilaian secara terstruktur.
- Tierlist biasanya dibuat di aplikasi lain, terpisah dari data rating.
- Pengguna ingin satu tempat untuk menilai, membandingkan, dan membagikan tierlist mereka.

## 3. Tujuan

- Pengguna dapat mendaftar, login, dan memberi rating serta ulasan pada mie instan.
- Pengguna dapat membuat dan menyimpan tierlist pribadi dengan drag and drop.
- Sistem menampilkan rata-rata rating dan tierlist komunitas secara otomatis.
- Admin dapat mengelola data mie lewat panel CRUD.

## 4. Target Pengguna

| Peran | Deskripsi | Kebutuhan Utama |
| --- | --- | --- |
| Pengunjung | Belum login | Melihat daftar mie, rata-rata rating, dan tierlist komunitas |
| Pengguna | Sudah login | Memberi rating, menulis ulasan, membuat tierlist pribadi |
| Admin | Pengelola konten | Menambah, mengubah, menghapus data mie dan moderasi ulasan |

## 5. Ruang Lingkup

**Termasuk (In Scope):**

- Register, login, dan logout pengguna; satu sistem login dengan role admin dan user.
- CRUD data mie oleh admin.
- Rating per aspek (rasa, tekstur, porsi, harga) dan ulasan oleh pengguna.
- Tierlist pribadi dengan drag and drop; satu pengguna boleh memiliki beberapa tierlist.
- Tierlist komunitas otomatis dari rata-rata rating.
- Pencarian dan filter mie.

**Tidak Termasuk (Out of Scope):**

- Pengguna menambah mie sendiri.
- Komentar balasan, fitur pertemanan, atau chat.
- Login lewat Google/sosial media.
- Aplikasi mobile native.
- Verifikasi email lewat tautan (ditunda ke versi berikutnya; kolom `email_verified` disiapkan di tabel `users`).

## 6. Kebutuhan Fungsional

### 6.1 Akun dan Autentikasi

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FU-01 | Pengguna dapat register dengan username, email, dan password; format email divalidasi dan email harus unik. Verifikasi email tidak diwajibkan pada versi 1.0. | Tinggi |
| FU-02 | Pengguna dapat login dan logout; password disimpan dengan `password_hash()`. | Tinggi |
| FU-03 | Halaman yang butuh login (rating, tierlist, admin) mengarahkan ke halaman login jika belum login. | Tinggi |
| FU-04 | Halaman admin hanya dapat diakses role admin. | Tinggi |

### 6.2 Katalog dan Rating

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-01 | Pengunjung dapat melihat daftar mie dengan gambar, brand, rasa, dan rata-rata rating. | Tinggi |
| FR-02 | Pengunjung dapat mencari mie berdasarkan nama dan memfilter berdasarkan brand, rasa, atau negara asal. | Sedang |
| FR-03 | Halaman detail mie menampilkan deskripsi, harga, rata-rata rating (skor akhir dan per aspek), jumlah penilai, dan daftar ulasan. | Tinggi |
| FR-04 | Pengguna memberi rating per aspek (rasa, tekstur, porsi, harga), masing-masing skor 1-5, dan ulasan singkat. Skor akhir adalah rata-rata keempat aspek (bobot sama pada versi 1.0). | Tinggi |
| FR-05 | Satu pengguna hanya dapat memberi satu rating per mie, dan boleh mengubah atau menghapus ratingnya sendiri. | Tinggi |
| FR-06 | Peringkat Top 10 mie berdasarkan skor akhir; hanya mie dengan minimal 3 penilai yang dihitung. | Rendah |

### 6.3 Tierlist

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FT-01 | Pengguna dapat menyusun tierlist pribadi dengan drag and drop kartu mie ke baris S, A, B, C, D, F. Satu mie hanya muncul sekali dalam satu tierlist. | Tinggi |
| FT-02 | Susunan tierlist disimpan ke database lewat `fetch()` dan dimuat kembali saat dibuka. | Tinggi |
| FT-03 | Tierlist komunitas dihitung otomatis dari skor akhir rata-rata; ambang tier mengikuti tabel di bawah dan dapat diubah lewat konstanta di `config/` atau tabel `settings`. Mie baru masuk tierlist komunitas setelah memiliki minimal 3 penilai. | Tinggi |
| FT-04 | Pengguna dapat membagikan tierlist lewat tautan publik. | Rendah |
| FT-05 | Pengguna dapat mengekspor tierlist menjadi gambar memakai `<canvas>`. | Rendah |
| FT-06 | Halaman profil menampilkan tierlist dan riwayat rating pengguna. | Rendah |
| FT-07 | Pengguna dapat memiliki beberapa tierlist (maksimal 10): membuat baru, mengubah judul, dan menghapus lewat halaman Tierlist Saya. | Tinggi |

Ambang tier komunitas (bawaan):

| Tier | Rata-rata skor akhir |
| --- | --- |
| S | 4,5 ke atas |
| A | 4,0 – 4,49 |
| B | 3,5 – 3,99 |
| C | 3,0 – 3,49 |
| D | 2,0 – 2,99 |
| F | Di bawah 2,0 |

### 6.4 Sisi Admin

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FA-01 | CRUD Mie: nama, brand, varian rasa, negara asal, harga, deskripsi, gambar. | Tinggi |
| FA-02 | Tabel admin memiliki pencarian dan pagination. | Sedang |
| FA-03 | Konfirmasi sebelum menghapus data. | Sedang |
| FA-04 | Admin dapat menghapus ulasan yang melanggar aturan. | Sedang |
| FA-05 | Dashboard ringkas: jumlah mie, pengguna, dan rating. | Rendah |

## 7. Kebutuhan Non-Fungsional

- **Keamanan:** prepared statements (PDO) untuk semua query; `password_hash()` dan `password_verify()`; `session_regenerate_id()` setelah login; escape output dengan `htmlspecialchars()`; token CSRF pada semua form dan permintaan `fetch()` yang mengubah data; aksi hapus lewat POST.
- **Upload gambar:** validasi tipe (jpg/png/webp), ukuran maksimum (misalnya 2 MB), dan nama file diganti acak.
- **Integritas data:** batasan `UNIQUE(user_id, noodle_id)` pada tabel rating; rata-rata dihitung dengan `AVG()`, tidak disimpan manual.
- **Validasi rating:** setiap aspek (rasa, tekstur, porsi, harga) wajib diisi dengan nilai bulat 1-5, divalidasi di sisi server; skor akhir dihitung dari rata-rata keempat aspek.
- **Kinerja:** halaman dimuat kurang dari 3 detik pada koneksi normal.
- **Responsif:** tampil baik di ponsel dan desktop; drag and drop tierlist tetap dapat dipakai di layar sentuh (misalnya dengan tombol pilih tier sebagai alternatif).

## 8. Alur Pengguna Utama

**Pengguna:** Register → Login → Telusuri mie → Buka detail → Beri rating dan ulasan → Buka halaman Tierlist → Seret mie ke tier → Simpan.

**Admin:** Login → Dashboard → Kelola Mie (tambah, ubah, hapus) → Moderasi ulasan → Logout.

## 9. Rancangan Data (Ringkas)

| Tabel | Kolom Utama |
| --- | --- |
| users | id, username, email, password_hash, role, email_verified (bawaan 0, disiapkan untuk versi berikutnya), created_at |
| noodles | id, name, brand, flavor, origin_country, price, description, image, created_at |
| ratings | id, user_id, noodle_id, taste, texture, portion, value, review, created_at, UNIQUE(user_id, noodle_id) |
| tierlists | id, user_id, title, category (opsional), is_public, share_token, updated_at |
| tierlist_items | id, tierlist_id, noodle_id, tier, position |
| settings (opsional) | key, value (ambang tier dan jumlah penilai minimum; bisa diganti konstanta di `config/`) |

## 10. Struktur Folder yang Disarankan

- `config/` : koneksi database dan konstanta
- `includes/` : header, footer, fungsi bantu (auth, CSRF, sanitasi)
- `admin/` : dashboard dan modul CRUD mie
- `api/` : endpoint PHP untuk `fetch()` (simpan tierlist, kirim rating)
- `assets/` : CSS, JavaScript, gambar statis
- `uploads/` : gambar mie
- halaman publik di root: index, detail, tierlist, profil, login, register

## 11. Teknologi

- **Back-end:** PHP native, tanpa framework
- **Front-end:** HTML, CSS, dan JavaScript (tanpa library atau framework)
- **Database:** SQL (MySQL/MariaDB), diakses dengan PDO dan prepared statements
- **Lingkungan pengembangan:** server lokal XAMPP/Laragon

## 12. Kriteria Keberhasilan

- Admin dapat menambah mie baru dan langsung muncul di katalog tanpa mengubah kode.
- Pengguna tidak dapat memberi lebih dari satu rating pada mie yang sama.
- Tierlist pribadi tersimpan dan tampil sama setelah logout dan login kembali.
- Rata-rata rating dan tierlist komunitas berubah otomatis saat ada rating baru.
- Halaman admin tidak dapat dibuka oleh pengguna biasa atau pengunjung.
- Tidak ada celah SQL injection dan XSS pada pengujian dasar.
- Pengguna dapat membuat lebih dari satu tierlist, dan batas maksimal 10 per pengguna ditegakkan.
- Mie dengan kurang dari 3 penilai tidak muncul di tierlist komunitas dan Top 10.

## 13. Rencana Pengerjaan

| Tahap | Isi | Perkiraan |
| --- | --- | --- |
| 1 | Desain database dan koneksi PDO | 1 hari |
| 2 | Register, login, role, dan proteksi session | 2 hari |
| 3 | CRUD Mie (admin) dan katalog publik | 2-3 hari |
| 4 | Rating per aspek dan ulasan | 2 hari |
| 5 | Tierlist pribadi (banyak tierlist) dengan drag and drop | 3 hari |
| 6 | Tierlist komunitas (ambang tier, minimal 3 penilai) dan Top 10 | 1-2 hari |
| 7 | Pengujian, perapihan UI, dan dokumentasi | 2 hari |

Rincian langkah per tahap untuk agent ada di `AGENT_PLAN.md`.

## 14. Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Drag and drop sulit di layar sentuh | Sediakan tombol atau dropdown pilih tier sebagai alternatif |
| Rating palsu atau spam | Satu rating per pengguna, batasi frekuensi, admin dapat menghapus ulasan |
| Katalog kosong di awal | Siapkan seed data (SQL) berisi 15-20 mie populer |
| Upload file berbahaya | Validasi MIME dan ekstensi, simpan dengan nama acak |
| Waktu pengerjaan terbatas | Kerjakan sesuai prioritas; fitur ekspor gambar dan tautan publik bisa menyusul |
| Mie dengan sedikit rating membuat tier komunitas tidak representatif | Syarat minimal 3 penilai sebelum mie masuk tierlist komunitas dan Top 10 |

## 15. Keputusan Desain

- **Rating:** memakai empat aspek (rasa, tekstur, porsi, harga) skor 1-5; skor akhir adalah rata-rata dengan bobot sama. Pembobotan dapat dipertimbangkan di versi berikutnya.
- **Ambang tier komunitas:** S 4,5 ke atas; A 4,0–4,49; B 3,5–3,99; C 3,0–3,49; D 2,0–2,99; F di bawah 2,0, dengan syarat minimal 3 penilai per mie.
- **Tierlist:** pengguna boleh memiliki beberapa tierlist (maksimal 10), satu mie sekali per tierlist.
- **Verifikasi email:** tidak diwajibkan pada versi 1.0 karena membutuhkan SMTP dan library tambahan; hanya validasi format dan keunikan email. Kolom `email_verified` disiapkan untuk versi berikutnya.
- **Pertanyaan terbuka:** tidak ada.
