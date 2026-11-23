<?php
// api/kategori/update.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth_middleware.php';

setApiHeaders('POST, PUT, OPTIONS');

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'], true)) {
    sendJson(false, 'Metode request tidak diizinkan.', null, 405);
}

requireRole('admin');

$input = !empty($_POST) ? $_POST : getJsonInput();
$id = (int) ($input['id'] ?? 0);
$namaKategori = trim((string) ($input['nama_kategori'] ?? ''));

if ($id <= 0 || $namaKategori === '') {
    sendJson(false, 'ID kategori dan nama kategori wajib diisi.', null, 400);
}

try {
    $db = (new Database())->getConnection();

    $stmt = $db->prepare("
        UPDATE kategori_produk
        SET nama_kategori = :nama_kategori, updated_at = NOW()
        WHERE id = :id AND deleted_at IS NULL
    ");
    $stmt->execute([
        'nama_kategori' => $namaKategori,
        'id'            => $id,
    ]);

    if ($stmt->rowCount() === 0) {
        sendJson(false, 'Kategori tidak ditemukan atau tidak ada perubahan.', null, 404);
    }

    sendJson(true, 'Kategori berhasil diperbarui.', [
        'id'            => $id,
        'nama_kategori' => $namaKategori,
    ]);
} catch (Throwable $e) {
    sendJson(false, 'Gagal memperbarui kategori: ' . $e->getMessage(), null, 500);
}