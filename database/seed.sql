-- Seed Data: Website Rating Mie Instan dan Tierlist
-- Kredensial Awal:
--   Admin : username = admin       | password = AdminMie123!
--   User  : username = budi_mie    | password = UserMie123!
--   User  : username = siti_ramen  | password = UserMie123!
--   User  : username = andi_kuah   | password = UserMie123!
--   User  : username = dewi_pedas  | password = UserMie123!

SET NAMES utf8mb4;

INSERT INTO settings (key_name, key_value) VALUES
('min_raters', '3'),
('tier_S', '4.50'),
('tier_A', '4.00'),
('tier_B', '3.50'),
('tier_C', '3.00'),
('tier_D', '2.00');

INSERT INTO users (id, username, email, password_hash, role, email_verified, created_at) VALUES
(1, 'admin', 'admin@rateyourmie.local', '$2y$10$uVQ2IGps9FMbVhqG/QTe7e/nu2nr8h6Uw5W5TzsYXnIAjUgtoD4me', 'admin', 1, '2026-11-01 08:00:00'),
(2, 'budi_mie', 'budi@rateyourmie.local', '$2y$10$XKSmik8qDiZwDbVI.AVP8eRBA2yyGlwh7BNMn4QKB6p9iUgBTF.r6', 'user', 1, '2026-11-02 09:15:00'),
(3, 'siti_ramen', 'siti@rateyourmie.local', '$2y$10$XKSmik8qDiZwDbVI.AVP8eRBA2yyGlwh7BNMn4QKB6p9iUgBTF.r6', 'user', 1, '2026-11-03 11:30:00'),
(4, 'andi_kuah', 'andi@rateyourmie.local', '$2y$10$XKSmik8qDiZwDbVI.AVP8eRBA2yyGlwh7BNMn4QKB6p9iUgBTF.r6', 'user', 0, '2026-11-04 14:20:00'),
(5, 'dewi_pedas', 'dewi@rateyourmie.local', '$2y$10$XKSmik8qDiZwDbVI.AVP8eRBA2yyGlwh7BNMn4QKB6p9iUgBTF.r6', 'user', 0, '2026-11-05 19:45:00');

INSERT INTO noodles (id, name, brand, flavor, origin_country, price, description, image, created_at) VALUES
(1, 'Indomie Mi Goreng Spesial', 'Indomie', 'Mi Goreng Original', 'Indonesia', 3100, 'Standar emas mie goreng instan Indonesia dengan paduan kecap manis, minyak bawang, saus cabai, dan bawang goreng renyah.', 'noodle-01-indomie-goreng.jpg', '2026-11-01 09:00:00'),
(2, 'Indomie Mi Goreng Rendang', 'Indomie', 'Mi Goreng Rendang', 'Indonesia', 3200, 'Mie goreng berbumbu pasta rendang rempah Padang yang pekat, gurih, dan harum daun jeruk.', 'noodle-02-indomie-rendang.jpg', '2026-11-01 09:05:00'),
(3, 'Indomie Kuah Rasa Soto Mie', 'Indomie', 'Kuah Soto', 'Indonesia', 3000, 'Mie kuah segar dengan minyak bumbu soto kuning dan aroma jeruk nipis yang menenangkan saat hujan.', 'noodle-03-indomie-soto.jpg', '2026-11-01 09:10:00'),
(4, 'Indomie Kuah Ayam Bawang', 'Indomie', 'Kuah Ayam Bawang', 'Indonesia', 3000, 'Kaldu ayam bening klasik dengan minyak bawang gurih yang cocok ditambah telur setengah matang dan cabai rawit.', 'noodle-04-indomie-ayam-bawang.jpg', '2026-11-01 09:15:00'),
(5, 'Indomie Mi Goreng Aceh', 'Indomie', 'Mi Goreng Pedas Rempah', 'Indonesia', 3200, 'Helaian mie lebih tebal dan kenyal dengan bumbu kari rempah khas Aceh yang pedas hangat.', 'noodle-05-indomie-aceh.jpg', '2026-11-01 09:20:00'),
(6, 'Mie Sedaap Mi Goreng Original', 'Mie Sedaap', 'Mi Goreng Original', 'Indonesia', 3100, 'Mie goreng kenyal dengan porsi kriuk bawang goreng melimpah yang renyah di setiap suapan.', 'noodle-06-sedaap-goreng.jpg', '2026-11-01 09:25:00'),
(7, 'Mie Sedaap Kuah Soto', 'Mie Sedaap', 'Kuah Soto', 'Indonesia', 3000, 'Mie kuah soto dengan serbuk koya gurih yang membuat kuah lebih kental dan kaya rasa.', 'noodle-07-sedaap-soto.jpg', '2026-11-01 09:30:00'),
(8, 'Mie Sedaap Korean Spicy Chicken', 'Mie Sedaap', 'Mi Goreng Pedas Korea', 'Indonesia', 3500, 'Mie goreng tebal dengan dua tingkat kepedasan bumbu cabai Korea yang bisa diatur sesuai selera.', 'noodle-08-sedaap-korean-spicy.jpg', '2026-11-01 09:35:00'),
(9, 'Sarimi Isi 2 Mi Goreng Ayam Kecap', 'Sarimi', 'Mi Goreng Ayam Kecap', 'Indonesia', 4200, 'Dua keping mie dalam satu kemasan dengan bumbu ayam kecap manis gurih untuk porsi kenyang maksimal.', 'noodle-09-sarimi-ayam-kecap.jpg', '2026-11-01 09:40:00'),
(10, 'Samyang Buldak Hot Chicken Original', 'Samyang', 'Mi Goreng Pedas Ayam', 'Korea Selatan', 18500, 'Mie instan Korea ikonik dengan mie super tebal dan saus ayam pedas membakar lidah beserta taburan wijen dan rumput laut.', 'noodle-10-samyang-buldak.jpg', '2026-11-01 09:45:00'),
(11, 'Samyang Buldak Carbonara', 'Samyang', 'Mi Goreng Pedas Creamy', 'Korea Selatan', 19500, 'Perpaduan saus pedas Buldak dengan bubuk keju krim carbonara yang gurih dan lebih ramah di lidah.', 'noodle-11-samyang-carbonara.jpg', '2026-11-01 09:50:00'),
(12, 'Nongshim Shin Ramyun', 'Nongshim', 'Kuah Kaldu Sapi Pedas', 'Korea Selatan', 16500, 'Mie kuah kaldu sapi dan jamur shiitake pedas dengan tekstur mie tebal yang tidak mudah lembek.', 'noodle-12-shin-ramyun.jpg', '2026-11-01 09:55:00'),
(13, 'Nissin Raoh Tonkotsu Ramen', 'Nongshim / Nissin', 'Kuah Tonkotsu', 'Jepang', 24000, 'Mie ramen instan tanpa digoreng (air-dried) dengan kuah kaldu tulang-babi/wijen gurih bertekstur lurus.', 'noodle-13-nissin-raoh.jpg', '2026-11-01 10:00:00'),
(14, 'Lemonilo Mi Instan Goreng', 'Lemonilo', 'Mi Goreng Original', 'Indonesia', 6800, 'Mie instan panggang tanpa proses penggorengan dengan warna hijau sari bayam dan bumbu yang lebih ringan.', 'noodle-14-lemonilo-goreng.jpg', '2026-11-01 10:05:00'),
(15, 'Pop Mie Ayam Cup', 'Pop Mie', 'Kuah Ayam', 'Indonesia', 5500, 'Mie seduh kemasan cup praktis dengan sayuran kering dan garpu lipat, teman perjalanan darat dan laut.', 'noodle-15-pop-mie-ayam.jpg', '2026-11-01 10:10:00'),
(16, 'Prima Taste Laksa La Mian', 'Prima Taste', 'Kuah Laksa Santan', 'Singapura', 39000, 'Mie tarik tebal dengan pasta rempah laksa dan bubuk santan kelapa asli yang kental seperti kedai Singapura.', 'noodle-16-prima-taste-laksa.jpg', '2026-11-01 10:15:00'),
(17, 'Mama Tom Yum Shrimp Creamy', 'Mama', 'Kuah Tom Yum Udang', 'Thailand', 7500, 'Mie kuah asam pedas khas Thailand dengan aroma serai, lengkuas, daun jeruk purut, dan kuah kental.', 'noodle-17-mama-tom-yum.jpg', '2026-11-01 10:20:00'),
(18, 'Gaga 100 Extra Pedas Goreng Jalapeno', 'Gaga', 'Mi Goreng Pedas Jalapeno', 'Indonesia', 3300, 'Mie goreng dengan sensasi pedas cabai jalapeno yang menyengat tajam namun tetap gurih.', 'noodle-18-gaga-jalapeno.jpg', '2026-11-01 10:25:00');

-- Data Rating (4 aspek: taste, texture, portion, value)
-- Mie 1: Indomie Mi Goreng Spesial (4 penilai, skor akhir = 4.75 -> Tier S)
INSERT INTO ratings (user_id, noodle_id, taste, texture, `portion`, `value`, review, created_at) VALUES
(2, 1, 5, 5, 4, 5, 'Tak tergantikan di warkop tengah malam. Bumbu minyak bawang dan kecapnya pas, harga sangat ramah kantong.', '2026-11-06 20:10:00'),
(3, 1, 5, 4, 4, 5, 'Rasa selalu konsisten sejak dulu. Satu bungkus kadang nanggung, dua bungkus kekenyangan.', '2026-11-06 21:00:00'),
(4, 1, 5, 5, 5, 5, 'Menu wajib nomor satu. Ditambah telur setengah matang dan sawi hijau langsung sempurna.', '2026-11-07 10:15:00'),
(5, 1, 5, 5, 4, 5, 'Keseimbangan rasa manis, gurih, dan aroma bawang gorengnya juara.', '2026-11-07 12:30:00'),

-- Mie 2: Indomie Mi Goreng Rendang (4 penilai, skor akhir = 4.50 -> tepat batas Tier S)
(2, 2, 5, 4, 4, 5, 'Aroma bumbu rendangnya kuat sekali, enak dimakan pakai nasi hangat.', '2026-11-07 13:00:00'),
(3, 2, 5, 4, 4, 5, 'Rempahnya berani dan tidak bikin enek.', '2026-11-07 14:10:00'),
(4, 2, 4, 5, 4, 5, 'Tekstur mienya pas dan bumbunya melimpah.', '2026-11-07 15:20:00'),
(5, 2, 5, 4, 4, 5, 'Varian goreng favorit kedua setelah original.', '2026-11-07 16:00:00'),

-- Mie 3: Indomie Kuah Rasa Soto Mie (4 penilai, skor akhir = 4.44 -> Tier A)
(2, 3, 5, 4, 4, 5, 'Segar banget aroma jeruk nipis dari minyak bumbunya.', '2026-11-08 08:00:00'),
(3, 3, 4, 4, 4, 5, 'Kuah paling nyaman saat cuaca dingin atau hujan.', '2026-11-08 09:00:00'),
(4, 3, 5, 4, 4, 5, 'Ditambah irisan cabai rawit dan jeruk nipis asli makin mantap.', '2026-11-08 10:00:00'),
(5, 3, 5, 4, 4, 5, 'Harga murah meriah dengan rasa kuah soto yang kaya.', '2026-11-08 11:00:00'),

-- Mie 5: Indomie Mi Goreng Aceh (3 penilai, skor akhir = 4.58 -> Tier S)
(2, 5, 5, 5, 4, 5, 'Mienya lebih tebal seperti mie Aceh asli, bumbu karinya nendang!', '2026-11-08 12:00:00'),
(3, 5, 5, 5, 4, 5, 'Tekstur mie bulat tebalnya luar biasa kenyal.', '2026-11-08 13:00:00'),
(5, 5, 5, 4, 4, 5, 'Pedas rempahnya berkarakter dan wangi.', '2026-11-08 14:00:00'),

-- Mie 6: Mie Sedaap Mi Goreng Original (4 penilai, skor akhir = 4.38 -> Tier A)
(2, 6, 4, 5, 4, 5, 'Kriuk bawangnya juara dan mienya sedikit lebih kenyal.', '2026-11-08 15:00:00'),
(3, 6, 4, 4, 4, 5, 'Porsi pas dan kriuknya bikin tekstur makin ramai.', '2026-11-08 16:00:00'),
(4, 6, 5, 4, 4, 5, 'Saingan ketat di kategori mie goreng klasik.', '2026-11-08 17:00:00'),
(5, 6, 4, 4, 4, 5, 'Gurih manisnya pas di lidah.', '2026-11-08 18:00:00'),

-- Mie 7: Mie Sedaap Kuah Soto (3 penilai, skor akhir = 4.25 -> Tier A)
(2, 7, 4, 4, 4, 5, 'Bubuk koyanya bikin kuah jadi gurih dan agak kental.', '2026-11-09 08:00:00'),
(4, 7, 5, 4, 4, 5, 'Koya sotonya benar-benar pembeda utama.', '2026-11-09 09:00:00'),
(5, 7, 4, 4, 4, 4, 'Enak dan hangat, cocok buat sarapan cepat.', '2026-11-09 10:00:00'),

-- Mie 8: Mie Sedaap Korean Spicy Chicken (3 penilai, skor akhir = 4.08 -> Tier A)
(2, 8, 4, 4, 4, 4, 'Bisa atur pedas level 1 atau tuang semua buat pedas gila.', '2026-11-09 11:00:00'),
(3, 8, 4, 4, 4, 4, 'Alternatif mie pedas gaya Korea dengan harga lokal.', '2026-11-09 12:00:00'),
(5, 8, 5, 4, 4, 4, 'Pedasnya nampol tapi harga cuma tiga ribuan!', '2026-11-09 13:00:00'),

-- Mie 9: Sarimi Isi 2 Mi Goreng Ayam Kecap (3 penilai, skor akhir = 4.17 -> Tier A)
(2, 9, 4, 3, 5, 5, 'Penyelamat tanggal tua, porsi dua keping bikin kenyang seharian.', '2026-11-09 14:00:00'),
(3, 9, 4, 4, 5, 5, 'Porsi dan harga tidak ada lawan.', '2026-11-09 15:00:00'),
(4, 9, 3, 4, 5, 5, 'Bumbu ayam kecapnya manis gurih, porsi jumbo.', '2026-11-09 16:00:00'),

-- Mie 10: Samyang Buldak Original (3 penilai, skor akhir = 3.67 -> Tier B)
(2, 10, 4, 5, 4, 2, 'Tekstur mie dan pedasnya mantap, tapi harganya lumayan menguras dompet anak kos.', '2026-11-10 08:00:00'),
(3, 10, 4, 5, 4, 2, 'Mienya kenyal banget, sayang harga cukup tinggi dibanding mie lokal.', '2026-11-10 09:00:00'),
(5, 10, 5, 5, 4, 1, 'Pedasnya legendaris tapi skor harga agak berat.', '2026-11-10 10:00:00'),

-- Mie 11: Samyang Buldak Carbonara (3 penilai, skor akhir = 3.83 -> Tier B)
(2, 11, 5, 5, 4, 2, 'Creamy dan pedasnya seimbang banget, tekstur mie tebal memuaskan.', '2026-11-10 11:00:00'),
(3, 11, 5, 5, 4, 2, 'Varian Buldak paling enak menurutku meski harganya premium.', '2026-11-10 12:00:00'),
(4, 11, 4, 4, 4, 2, 'Enak ditambah keju slice, walau harga hampir 20 ribu.', '2026-11-10 13:00:00'),

-- Mie 12: Nongshim Shin Ramyun (3 penilai, skor akhir = 3.92 -> Tier B)
(2, 12, 4, 5, 5, 2, 'Kuah pedas gurih dengan porsi besar dan mie super kenyal.', '2026-11-10 14:00:00'),
(3, 12, 5, 5, 4, 2, 'Potongan jamur keringnya enak banget.', '2026-11-10 15:00:00'),
(4, 12, 4, 5, 4, 2, 'Porsi mangkuk besar, puas dimakan saat hujan.', '2026-11-10 16:00:00'),

-- Mie 14: Lemonilo Mi Instan Goreng (3 penilai, skor akhir = 3.17 -> Tier C)
(2, 14, 3, 3, 3, 3, 'Rasanya cukup ringan, cocok kalau lagi ingin mie yang tidak terlalu berminyak.', '2026-11-11 08:00:00'),
(3, 14, 4, 3, 3, 3, 'Bumbunya enak tapi tekstur mienya kurang kenyal dibanding mie goreng biasa.', '2026-11-11 09:00:00'),
(4, 14, 3, 3, 4, 3, 'Harga dua kali lipat mie goreng biasa dengan rasa yang lebih kalem.', '2026-11-11 10:00:00'),

-- Mie 15: Pop Mie Ayam Cup (3 penilai, skor akhir = 2.67 -> Tier D)
(2, 15, 3, 2, 3, 3, 'Praktis tinggal seduh di kapal atau kereta, tapi mienya cepat lembek.', '2026-11-11 11:00:00'),
(3, 15, 3, 2, 2, 3, 'Menang di kepraktisan cup, porsinya agak sedikit.', '2026-11-11 12:00:00'),
(4, 15, 3, 3, 2, 3, 'Nostalgia liburan, tapi buat di rumah lebih pilih mie bungkus.', '2026-11-11 13:00:00'),

-- Mie 18: Gaga 100 Extra Pedas Goreng Jalapeno (3 penilai, skor akhir = 4.42 -> Tier A)
(2, 18, 5, 4, 4, 5, 'Pedas jalapenonya unik dan gurih banget dengan harga terjangkau.', '2026-11-11 14:00:00'),
(4, 18, 4, 4, 4, 5, 'Bumbu berminyak pedasnya bikin nagih.', '2026-11-11 15:00:00'),
(5, 18, 5, 4, 4, 5, 'Wajib coba buat penggemar mie goreng pedas!', '2026-11-11 16:00:00'),

-- Mie 4: Indomie Kuah Ayam Bawang (hanya 2 penilai -> belum masuk Tierlist Komunitas & Top 10)
(2, 4, 4, 4, 4, 5, 'Klasik dan menenangkan, kurang 1 penilai lagi untuk masuk tier komunitas.', '2026-11-12 08:00:00'),
(3, 4, 4, 4, 4, 5, 'Minyak bawangnya harum sekali.', '2026-11-12 09:00:00'),

-- Mie 17: Mama Tom Yum Shrimp Creamy (hanya 1 penilai -> belum masuk Tierlist Komunitas & Top 10)
(5, 17, 5, 4, 3, 4, 'Kuah asam pedas serainya segar banget!', '2026-11-12 10:00:00');
-- Catatan: Mie 13 (Nissin Raoh) dan Mie 16 (Prima Taste Laksa) sengaja belum memiliki rating (0 penilai) untuk menguji state "Belum ada rating".

INSERT INTO tierlists (id, user_id, title, category, is_public, share_token, updated_at) VALUES
(1, 2, 'Tierlist Mie Warkop Tengah Malam', 'Semua Varian', 1, 'a1b2c3d4e5f60718293a4b5c6d7e8f90', '2026-11-12 20:00:00'),
(2, 2, 'Edisi Mie Pedas Terbaik', 'Mie Pedas', 0, NULL, '2026-11-12 21:00:00'),
(3, 3, 'Favorit Anak Kos Hemat', 'Harga Terjangkau', 1, 'f0e9d8c7b6a594837261504f3e2d1c0b', '2026-11-13 10:00:00');

INSERT INTO tierlist_items (tierlist_id, noodle_id, tier, position) VALUES
(1, 1, 'S', 0),
(1, 2, 'S', 1),
(1, 5, 'S', 2),
(1, 3, 'A', 0),
(1, 6, 'A', 1),
(1, 18, 'A', 2),
(1, 9, 'B', 0),
(1, 11, 'B', 1),
(1, 14, 'C', 0),
(1, 15, 'D', 0),
(2, 5, 'S', 0),
(2, 18, 'S', 1),
(2, 11, 'A', 0),
(2, 8, 'A', 1),
(2, 10, 'B', 0),
(3, 1, 'S', 0),
(3, 9, 'S', 1),
(3, 3, 'A', 0),
(3, 6, 'A', 1);
