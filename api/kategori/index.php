<?php
// api/kategori/index.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';

setApiHeaders('GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJson(false, 'Metode request tidak diizinkan.', null, 405);
}

try {
    $db = (new Database())->getConnection();

    $stmt = $db->query("
        SELECT 
            k.id,
            k.nama_kategori,
            k.created_at,
            k.updated_at,
            COUNT(p.id) AS total_produk
        FROM kategori_produk k
        LEFT JOIN produk p 
            ON p.kategori_id = k.id AND p.deleted_at IS NULL
        WHERE k.deleted_at IS NULL
        GROUP BY k.id, k.nama_kategori, k.created_at, k.updated_at
        ORDER BY k.id ASC
    ");

    $kategori = $stmt->fetchAll();

    sendJson(true, 'Daftar kategori berhasil dimuat.', $kategori);
} catch (Throwable $e) {
    sendJson(false, 'Gagal memuat kategori: ' . $e->getMessage(), null, 500);
}