<?php
// api/kategori/store.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth_middleware.php';

setApiHeaders('POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(false, 'Metode request tidak diizinkan.', null, 405);
}

requireRole('admin');

$input = !empty($_POST) ? $_POST : getJsonInput();
$namaKategori = trim((string) ($input['nama_kategori'] ?? ''));

if ($namaKategori === '') {
    sendJson(false, 'Nama kategori wajib diisi.', null, 400);
}

try {
    $db = (new Database())->getConnection();

    $stmt = $db->prepare("
        INSERT INTO kategori_produk (nama_kategori, created_at, updated_at)
        VALUES (:nama_kategori, NOW(), NOW())
    ");
    $stmt->execute(['nama_kategori' => $namaKategori]);

    $newId = (int) $db->lastInsertId();

    sendJson(true, 'Kategori berhasil ditambahkan.', [
        'id'            => $newId,
        'nama_kategori' => $namaKategori,
    ], 201);
} catch (Throwable $e) {
    sendJson(false, 'Gagal menambahkan kategori: ' . $e->getMessage(), null, 500);
}