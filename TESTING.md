# TESTING: Laporan Pengujian Keamanan, Integritas, dan Fungsional (Tahap 7)

Dokumen ini merangkum skenario pengujian berdasarkan `PRD.md` (Bagian 12 & 14) dan `AGENT_PLAN.md` (Tahap 7).

---

## 1. Pengujian Keamanan

| Area Uji | Skenario Pengujian | Mekanisme Pertahanan di Kode | Status |
| --- | --- | --- | --- |
| **SQL Injection** | Memasukkan `' OR 1=1 --` pada form `login.php`, parameter pencarian `?q=`, filter `?brand=`, dan `?id=` di `noodle.php` / `tierlist.php` | Seluruh query menggunakan `PDO::prepare()` dengan `PDO::ATTR_EMULATE_PREPARES => false` di `config/database.php` dan `includes/noodle_repo.php`. Parameter `id` divalidasi sebagai integer. | **Lolos** |
| **Cross-Site Scripting (XSS)** | Memasukkan `<script>alert(1)</script>` pada username, nama mie, deskripsi, ulasan rating, dan judul tierlist | Seluruh output ke HTML dibungkus helper `e()` (`htmlspecialchars($s, ENT_QUOTES, 'UTF-8')`). Payload tampil sebagai teks biasa tanpa dieksekusi browser. | **Lolos** |
| **Cross-Site Request Forgery (CSRF)** | Mengirimkan POST ke `login.php`, `register.php`, `logout.php`, `admin/*`, dan `api/*` tanpa token atau dengan token palsu | `csrf_require()` di `includes/csrf.php` memvalidasi token session dengan `hash_equals()`. Permintaan tanpa token ditolak dengan HTTP `403 Forbidden`. | **Lolos** |
| **Kontrol Akses & Kepemilikan** | 1) Pengunjung membuka `tierlists.php`, `tierlist.php`, `profile.php`, `admin/*`<br>2) User biasa (`budi_mie`) membuka `admin/index.php` atau `admin/noodles.php`<br>3) User A membuka/mengubah `tierlist.php?id=3` milik User B | 1) `require_login()` mengarahkan ke `login.php` dan menyimpan URL tujuan.<br>2) `require_admin()` menolak role `user` dengan HTTP `403`.<br>3) `tierlist.php` dan seluruh `api/tierlist_*.php` memeriksa `tierlist.user_id === current_user()['id']`. | **Lolos** |
| **Keamanan Upload File** | 1) Mengunggah file `.php` yang diubah namanya menjadi `.jpg`<br>2) Mengunggah file > 2 MB<br>3) Mengunggah file `.gif` | `admin/noodle_form.php` memeriksa MIME asli file dengan `finfo_file()` + `getimagesize()`, membatasi ukuran ≤ `2097152` byte (2 MB), mengganti nama file dengan `bin2hex(random_bytes(16))`, dan `uploads/.htaccess` mematikan engine PHP. | **Lolos** |

---

## 2. Pengujian Integritas Data & Aturan Bisnis

| ID PRD | Aturan Bisnis | Skenario & Hasil Verifikasi | Status |
| --- | --- | --- | --- |
| **FR-04 & FR-05** | Satu rating per pengguna per mie; 4 aspek wajib bulat 1–5 | `UNIQUE KEY uq_user_noodle (user_id, noodle_id)` di tabel `ratings` dipadukan dengan `INSERT ... ON DUPLICATE KEY UPDATE` di `api/rating_save.php`. Nilai di luar 1–5 ditolak dengan HTTP `422`. | **Lolos** |
| **FT-01 & FT-02** | Satu mie hanya muncul sekali dalam satu tierlist; tersimpan dengan transaksi | `UNIQUE KEY uq_tierlist_noodle (tierlist_id, noodle_id)` di DB dan validasi array `$seenNoodles` di `api/tierlist_save.php` dalam blok `beginTransaction()` / `commit()`. | **Lolos** |
| **FT-07** | Maksimal 10 tierlist per pengguna (`MAX_TIERLISTS = 10`) | `api/tierlist_create.php` menghitung `COUNT(*) FROM tierlists WHERE user_id = :uid`. Percobaan membuat tierlist ke-11 ditolak dengan HTTP `422`. | **Lolos** |
| **FT-03 & FR-06** | Ambang Tier Komunitas & minimal 3 penilai (`MIN_RATERS = 3`) | `score_to_tier()` membulatkan skor 2 desimal: `4.50` masuk **S** (contoh: *Indomie Mi Goreng Rendang* skor `4.50`), `4.44` masuk **A** (*Indomie Soto Mie*). Mie dengan 0–2 penilai (*Indomie Ayam Bawang*, *Mama Tom Yum*, *Nissin Raoh*, *Prima Taste Laksa*) tidak masuk papan utama Tierlist Komunitas maupun Top 10. | **Lolos** |
| **Responsif & Layar Sentuh** | Tampilan mobile (`360px`–`390px`) dan desktop (`1440px`); alternatif tanpa drag-and-drop | Setiap kartu di `tierlist.php` memiliki `<select class="tier-select-control js-tier-select">` ("Pindah ke Tier S/A/B/C/D/F" atau "Belum Dikelompokkan") yang langsung memindahkan kartu dan menyimpan otomatis. | **Lolos** |
