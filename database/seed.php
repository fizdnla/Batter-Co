<?php
// database/seed.php

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../config/database.php';

$db = (new Database())->getConnection();

try {
    $cekUser = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($cekUser > 0) {
        echo "[INFO] Seeder dibatalkan: Tabel 'users' sudah berisi data.\n";
        echo "Kosongkan tabel terlebih dahulu jika ingin menjalankan ulang seeder.\n";
        exit;
    }

    $db->beginTransaction();

    // 1. Seed Users (Password semua akun: password123)
    $defaultPassword = password_hash('password123', PASSWORD_BCRYPT);

    $stmtUser = $db->prepare("
        INSERT INTO users (nama, email, password, role, no_telp, alamat_default, created_at, updated_at)
        VALUES (:nama, :email, :password, :role, :no_telp, :alamat_default, NOW(), NOW())
    ");

    $usersData = [
        [
            'nama'           => 'Admin Batter & Co.',
            'email'          => 'admin@batterenko.com',
            'password'       => $defaultPassword,
            'role'           => 'admin',
            'no_telp'        => '081234567890',
            'alamat_default' => 'Toko Pusat Batter & Co., Padang',
        ],
        [
            'nama'           => 'Kasir Toko',
            'email'          => 'kasir@batterenko.com',
            'password'       => $defaultPassword,
            'role'           => 'karyawan',
            'no_telp'        => '081234567891',
            'alamat_default' => 'Toko Pusat Batter & Co., Padang',
        ],
        [
            'nama'           => 'Budi Santoso',
            'email'          => 'budi@gmail.com',
            'password'       => $defaultPassword,
            'role'           => 'pelanggan',
            'no_telp'        => '081298765432',
            'alamat_default' => 'Jl. Khatib Sulaiman No. 45, Padang',
        ],
    ];

    $userIds = [];
    foreach ($usersData as $u) {
        $stmtUser->execute($u);
        $userIds[$u['role']] = (int) $db->lastInsertId();
    }

    // Buat keranjang persisten untuk akun pelanggan
    $stmtCart = $db->prepare("INSERT INTO keranjang (user_id, last_updated) VALUES (:user_id, NOW())");
    $stmtCart->execute(['user_id' => $userIds['pelanggan']]);

    // 2. Seed 6 Kategori Produk
    $stmtKategori = $db->prepare("
        INSERT INTO kategori_produk (nama_kategori, created_at, updated_at)
        VALUES (:nama_kategori, NOW(), NOW())
    ");

    $kategoriList = [
        'Bakery & Pastry',      // index 0
        'Cakes',                // index 1
        'Cookies & Brownies',   // index 2
        'Desserts',             // index 3
        'Coffee & Drinks',      // index 4
        'Bread',                // index 5
    ];

    $kategoriIds = [];
    foreach ($kategoriList as $kat) {
        $stmtKategori->execute(['nama_kategori' => $kat]);
        $kategoriIds[] = (int) $db->lastInsertId();
    }

    // 3. Seed 29 Produk & Log Pergerakan Stok Awal
    $stmtProduk = $db->prepare("
        INSERT INTO produk (kategori_id, nama_produk, deskripsi, harga, stok_ready, tipe, gambar_url, created_at, updated_at)
        VALUES (:kategori_id, :nama_produk, :deskripsi, :harga, :stok_ready, :tipe, :gambar_url, NOW(), NOW())
    ");

    $stmtLogStok = $db->prepare("
        INSERT INTO log_pergerakan_stok (produk_id, karyawan_id, tipe_pergerakan, qty, keterangan, tgl_log)
        VALUES (:produk_id, :karyawan_id, 'masuk', :qty, 'Stok awal sistem (Seeder)', NOW())
    ");

    $produkList = [
        // --- 1. Bakery & Pastry ---
        [
            'kategori_id' => $kategoriIds[0],
            'nama_produk' => 'Croissant',
            'deskripsi'   => 'Croissant klasik berlapis renyah menggunakan 100% mentega Prancis berkualitas tinggi.',
            'harga'       => 25000,
            'stok_ready'  => 25,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/croissant.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[0],
            'nama_produk' => 'Cinnamon Roll',
            'deskripsi'   => 'Roti gulung lembut dengan aroma kayu manis hangat dan lapisan cream cheese glaze.',
            'harga'       => 28000,
            'stok_ready'  => 20,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/cinnamon-roll.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[0],
            'nama_produk' => 'Danish',
            'deskripsi'   => 'Pastry renyah berlapis dengan isian vla vanila lembut dan topping buah segar.',
            'harga'       => 30000,
            'stok_ready'  => 18,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/danish.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[0],
            'nama_produk' => 'Pain au Chocolat',
            'deskripsi'   => 'Pastry flaky khas Prancis dengan isian dua batang dark chocolate premium di dalamnya.',
            'harga'       => 30000,
            'stok_ready'  => 22,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/pain-au-chocolat.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[0],
            'nama_produk' => 'Puff Pastry',
            'deskripsi'   => 'Kue pastri panggang berlapis yang ringan, garing, dan gurih mentega.',
            'harga'       => 22000,
            'stok_ready'  => 25,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/puff-pastry.jpg',
        ],

        // --- 2. Cakes ---
        [
            'kategori_id' => $kategoriIds[1],
            'nama_produk' => 'Birthday Cake',
            'deskripsi'   => 'Kue ulang tahun spons lembut tiga lapis dengan krim vanila dan dekorasi khusus. Pre-order H-2.',
            'harga'       => 285000,
            'stok_ready'  => 0,
            'tipe'        => 'pre_order',
            'gambar_url'  => 'assets/img/uploads/birthday-cake.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[1],
            'nama_produk' => 'Chocolate Cake',
            'deskripsi'   => 'Kue cokelat pekat dengan lapisan ganache cokelat Belgia 70% yang kaya rasa. Pre-order H-1.',
            'harga'       => 265000,
            'stok_ready'  => 0,
            'tipe'        => 'pre_order',
            'gambar_url'  => 'assets/img/uploads/chocolate-cake.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[1],
            'nama_produk' => 'Cheesecake',
            'deskripsi'   => 'Burnt Basque Cheesecake panggang dengan bagian luar karamel dan tekstur dalam yang lembut lumer. Pre-order H-1.',
            'harga'       => 250000,
            'stok_ready'  => 0,
            'tipe'        => 'pre_order',
            'gambar_url'  => 'assets/img/uploads/cheesecake.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[1],
            'nama_produk' => 'Red Velvet',
            'deskripsi'   => 'Kue merah beludru klasik dengan perpaduan rasa kakao ringan dan cream cheese frosting. Pre-order H-1.',
            'harga'       => 260000,
            'stok_ready'  => 0,
            'tipe'        => 'pre_order',
            'gambar_url'  => 'assets/img/uploads/red-velvet.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[1],
            'nama_produk' => 'Slice Cake',
            'deskripsi'   => 'Potongan kue harian siap santap dengan tekstur lembut dan krim segar.',
            'harga'       => 38000,
            'stok_ready'  => 15,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/slice-cake.jpg',
        ],

        // --- 3. Cookies & Brownies ---
        [
            'kategori_id' => $kategoriIds[2],
            'nama_produk' => 'Chocolate Cookies',
            'deskripsi'   => 'Soft-baked cookies renyah di luar dan lembut di dalam dengan lelehan dark chocolate chunks.',
            'harga'       => 20000,
            'stok_ready'  => 30,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/chocolate-cookies.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[2],
            'nama_produk' => 'Matcha Cookies',
            'deskripsi'   => 'Cookies panggang dengan bubuk uji matcha Jepang dan potongan white chocolate.',
            'harga'       => 22000,
            'stok_ready'  => 25,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/matcha-cookies.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[2],
            'nama_produk' => 'Fudgy Brownies',
            'deskripsi'   => 'Brownies panggang bertekstur padat fudgy dengan lapisan shiny crust dan cokelat pekat.',
            'harga'       => 65000,
            'stok_ready'  => 18,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/fudgy-brownies.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[2],
            'nama_produk' => 'Blondies',
            'deskripsi'   => 'Brownies versi brown sugar dan mentega karamel dengan taburan kacang kenari panggang.',
            'harga'       => 60000,
            'stok_ready'  => 16,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/blondies.jpg',
        ],

        // --- 4. Desserts ---
        [
            'kategori_id' => $kategoriIds[3],
            'nama_produk' => 'Tiramisu',
            'deskripsi'   => 'Dessert klasik Italia berlapis biskuit ladyfinger rendaman espresso dan krim mascarpone.',
            'harga'       => 42000,
            'stok_ready'  => 14,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/tiramisu.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[3],
            'nama_produk' => 'Panna Cotta',
            'deskripsi'   => 'Puding krim lembut khas Italia dengan siraman saus mixed berry segar.',
            'harga'       => 32000,
            'stok_ready'  => 16,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/panna-cotta.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[3],
            'nama_produk' => 'Pudding',
            'deskripsi'   => 'Puding susu karamel lembut dengan tekstur kenyal dan rasa manis yang pas.',
            'harga'       => 24000,
            'stok_ready'  => 20,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/pudding.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[3],
            'nama_produk' => 'Mousse',
            'deskripsi'   => 'Dessert krim cokelat bertekstur ringan seperti awan yang langsung lumer di mulut.',
            'harga'       => 36000,
            'stok_ready'  => 15,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/mousse.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[3],
            'nama_produk' => 'Dessert Box',
            'deskripsi'   => 'Lapisan kue cokelat lembut, krim keju manis, dan saus cokelat lumer dalam kemasan boks praktis.',
            'harga'       => 55000,
            'stok_ready'  => 18,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/dessert-box.jpg',
        ],

        // --- 5. Coffee & Drinks ---
        [
            'kategori_id' => $kategoriIds[4],
            'nama_produk' => 'Americano',
            'deskripsi'   => 'Espresso house-blend yang disajikan dengan air mineral dingin atau panas.',
            'harga'       => 22000,
            'stok_ready'  => 50,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/americano.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[4],
            'nama_produk' => 'Café Latte',
            'deskripsi'   => 'Perpaduan seimbang antara double shot espresso dan susu segar steamed yang lembut.',
            'harga'       => 28000,
            'stok_ready'  => 50,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/cafe-latte.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[4],
            'nama_produk' => 'Cappuccino',
            'deskripsi'   => 'Kopi espresso klasik dengan komposisi susu dan microfoam tebal yang kaya rasa.',
            'harga'       => 28000,
            'stok_ready'  => 50,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/cappuccino.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[4],
            'nama_produk' => 'Matcha Latte',
            'deskripsi'   => 'Minuman teh hijau matcha autentik yang dikocok dengan susu segar creamy.',
            'harga'       => 32000,
            'stok_ready'  => 40,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/matcha-latte.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[4],
            'nama_produk' => 'Chocolate',
            'deskripsi'   => 'Minuman cokelat pekat premium yang dipadukan dengan susu segar dingin maupun hangat.',
            'harga'       => 30000,
            'stok_ready'  => 40,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/chocolate-drink.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[4],
            'nama_produk' => 'Strawberry Milk',
            'deskripsi'   => 'Susu segar dengan selai stroberi buatan sendiri dan potongan buah stroberi asli.',
            'harga'       => 30000,
            'stok_ready'  => 35,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/strawberry-milk.jpg',
        ],

        // --- 6. Bread ---
        [
            'kategori_id' => $kategoriIds[5],
            'nama_produk' => 'Sourdough',
            'deskripsi'   => 'Roti artisan fermentasi alami 24 jam dengan kulit luar renyah dan bagian dalam kenyal.',
            'harga'       => 48000,
            'stok_ready'  => 12,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/sourdough.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[5],
            'nama_produk' => 'Brioche',
            'deskripsi'   => 'Roti tawar khas Prancis bertekstur sangat lembut dengan kandungan telur dan mentega melimpah.',
            'harga'       => 42000,
            'stok_ready'  => 14,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/brioche.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[5],
            'nama_produk' => 'Milk Bread',
            'deskripsi'   => 'Roti sobek susu ala Jepang (shokupan) yang empuk, berserat halus, dan gurih susu.',
            'harga'       => 35000,
            'stok_ready'  => 18,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/milk-bread.jpg',
        ],
        [
            'kategori_id' => $kategoriIds[5],
            'nama_produk' => 'Garlic Bread',
            'deskripsi'   => 'Roti panggang olesan mentega bawang putih panggang, peterseli, dan keju gurih.',
            'harga'       => 26000,
            'stok_ready'  => 20,
            'tipe'        => 'ready_stock',
            'gambar_url'  => 'assets/img/uploads/garlic-bread.jpg',
        ],
    ];

    foreach ($produkList as $p) {
        $stmtProduk->execute($p);
        $produkId = (int) $db->lastInsertId();

        if ($p['tipe'] === 'ready_stock' && $p['stok_ready'] > 0) {
            $stmtLogStok->execute([
                'produk_id'   => $produkId,
                'karyawan_id' => $userIds['karyawan'],
                'qty'         => $p['stok_ready'],
            ]);
        }
    }

    $db->commit();

    echo "[BERHASIL] Seeding 6 kategori & 29 menu ke database 'batterenko' selesai!\n";
    echo "Akun Default (Password: password123):\n";
    echo "- Admin     : admin@batterenko.com\n";
    echo "- Kasir     : kasir@batterenko.com\n";
    echo "- Pelanggan : budi@gmail.com\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "[ERROR] Gagal menjalankan seeder: " . $e->getMessage() . "\n";
}