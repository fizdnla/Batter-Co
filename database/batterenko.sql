CREATE TABLE `Users` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `email` varchar(255) UNIQUE NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` ENUM ('admin', 'karyawan', 'pelanggan') NOT NULL DEFAULT 'pelanggan',
  `no_telp` varchar(20),
  `alamat_default` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime COMMENT 'Soft delete agar data historis pesanan tidak error jika user dihapus'
);

CREATE TABLE `Kategori_Produk` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `nama_kategori` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime
);

CREATE TABLE `Produk` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `kategori_id` int NOT NULL,
  `nama_produk` varchar(255) NOT NULL,
  `deskripsi` text,
  `harga` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stok_ready` int NOT NULL DEFAULT 0,
  `tipe` ENUM ('ready_stock', 'pre_order') NOT NULL DEFAULT 'ready_stock',
  `gambar_url` varchar(255),
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime COMMENT 'Gunakan ini alih-alih menghapus produk secara permanen (Hard Delete)'
);

CREATE TABLE `Keranjang` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `user_id` int UNIQUE NOT NULL,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `Detail_Keranjang` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `keranjang_id` int NOT NULL,
  `produk_id` int NOT NULL,
  `qty` int NOT NULL DEFAULT 1
);

CREATE TABLE `Pesanan` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `no_invoice` varchar(100) UNIQUE NOT NULL,
  `user_id` int COMMENT 'Null jika transaksi POS offline dari pembeli guest',
  `karyawan_id` int COMMENT 'Diisi kasir POS (offline) atau staf pemroses (online)',
  `jenis_pesanan` ENUM ('online', 'offline') NOT NULL,
  `tgl_pesanan` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status_pesanan` ENUM ('menunggu_pembayaran', 'diproses', 'dikirim', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'menunggu_pembayaran',
  `total_harga` decimal(12,2) NOT NULL DEFAULT 0.00,
  `catatan_pesanan` text COMMENT 'Jadwal kirim/ambil atau catatan umum pesanan',
  `alasan_batal` text COMMENT 'Audit alasan jika dibatalkan pelanggan atau intervensi Admin',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `Detail_Pesanan` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `pesanan_id` int NOT NULL,
  `produk_id` int NOT NULL,
  `qty` int NOT NULL,
  `harga_satuan_saat_beli` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `catatan_khusus` text COMMENT 'Instruksi kustom per item, misal: tulisan ucapan di kue'
);

CREATE TABLE `Pembayaran` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `pesanan_id` int UNIQUE NOT NULL,
  `gateway_trx_id` varchar(255),
  `payment_type` varchar(100) COMMENT 'Contoh: qris, bank_transfer, atau cash (untuk POS)',
  `status_bayar` ENUM ('pending', 'settlement', 'expire', 'cancel') NOT NULL DEFAULT 'pending',
  `payment_url` varchar(255),
  `tgl_bayar` datetime,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `Log_Pergerakan_Stok` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `produk_id` int NOT NULL,
  `karyawan_id` int COMMENT 'Nullable jika pengurangan stok otomatis dari pesanan online pelanggan',
  `tipe_pergerakan` ENUM ('masuk', 'keluar') NOT NULL,
  `qty` int NOT NULL,
  `keterangan` varchar(255),
  `tgl_log` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `Wishlist` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT 'Pelanggan yang menyimpan barang',
  `produk_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `Ulasan` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `produk_id` int NOT NULL,
  `pesanan_id` int NOT NULL COMMENT 'Memastikan ulasan hanya dari pesanan yang valid/selesai',
  `rating` int NOT NULL COMMENT 'Skala 1 sampai 5',
  `komentar` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX `uq_keranjang_produk` ON `Detail_Keranjang` (`keranjang_id`, `produk_id`);
CREATE UNIQUE INDEX `uq_wishlist_user_produk` ON `Wishlist` (`user_id`, `produk_id`);
CREATE UNIQUE INDEX `uq_ulasan_per_pesanan` ON `Ulasan` (`user_id`, `produk_id`, `pesanan_id`);

ALTER TABLE `Produk` ADD FOREIGN KEY (`kategori_id`) REFERENCES `Kategori_Produk` (`id`);
ALTER TABLE `Keranjang` ADD FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`);
ALTER TABLE `Detail_Keranjang` ADD FOREIGN KEY (`keranjang_id`) REFERENCES `Keranjang` (`id`);
ALTER TABLE `Detail_Keranjang` ADD FOREIGN KEY (`produk_id`) REFERENCES `Produk` (`id`);
ALTER TABLE `Pesanan` ADD FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`);
ALTER TABLE `Pesanan` ADD FOREIGN KEY (`karyawan_id`) REFERENCES `Users` (`id`);
ALTER TABLE `Detail_Pesanan` ADD FOREIGN KEY (`pesanan_id`) REFERENCES `Pesanan` (`id`);
ALTER TABLE `Detail_Pesanan` ADD FOREIGN KEY (`produk_id`) REFERENCES `Produk` (`id`);
ALTER TABLE `Pembayaran` ADD FOREIGN KEY (`pesanan_id`) REFERENCES `Pesanan` (`id`);
ALTER TABLE `Log_Pergerakan_Stok` ADD FOREIGN KEY (`produk_id`) REFERENCES `Produk` (`id`);
ALTER TABLE `Log_Pergerakan_Stok` ADD FOREIGN KEY (`karyawan_id`) REFERENCES `Users` (`id`);
ALTER TABLE `Wishlist` ADD FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`);
ALTER TABLE `Wishlist` ADD FOREIGN KEY (`produk_id`) REFERENCES `Produk` (`id`);
ALTER TABLE `Ulasan` ADD FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`);
ALTER TABLE `Ulasan` ADD FOREIGN KEY (`produk_id`) REFERENCES `Produk` (`id`);
ALTER TABLE `Ulasan` ADD FOREIGN KEY (`pesanan_id`) REFERENCES `Pesanan` (`id`);