<?php
// api/kategori/delete.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth_middleware.php';

setApiHeaders('POST, DELETE, OPTIONS');

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'], true)) {
    sendJson(false, 'Metode request tidak diizinkan.', null, 405);
}

requireRole('admin');

$input = !empty($_POST) ? $_POST : getJsonInput();
$id = (int) ($input['id'] ?? $_GET['id'] ?? 0);

if ($id <= 0) {
    sendJson(false, 'ID kategori tidak valid.', null, 400);
}

try {
    $db = (new Database())->getConnection();

    // Cegah penghapusan jika masih ada produk aktif di kategori ini
    $stmtCek = $db->prepare("
        SELECT COUNT(*) FROM produk WHERE kategori_id = :id AND deleted_at IS NULL
    ");
    $stmtCek->execute(['id' => $id]);
    if ((int) $stmtCek->fetchColumn() > 0) {
        sendJson(false, 'Kategori tidak dapat dihapus karena masih memiliki produk aktif.', null, 409);
    }

    $stmt = $db->prepare("
        UPDATE kategori_produk
        SET deleted_at = NOW(), updated_at = NOW()
        WHERE id = :id AND deleted_at IS NULL
    ");
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 0) {
        sendJson(false, 'Kategori tidak ditemukan.', null, 404);
    }

    sendJson(true, 'Kategori berhasil dihapus.');
} catch (Throwable $e) {
    sendJson(false, 'Gagal menghapus kategori: ' . $e->getMessage(), null, 500);
}