<?php
// api/auth/me.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth_middleware.php';

setApiHeaders('GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJson(false, 'Metode request tidak diizinkan.', null, 405);
}

$sessionUser = getCurrentUser();

if (!$sessionUser) {
    sendJson(true, 'Belum login.', [
        'authenticated' => false,
        'user'          => null,
    ]);
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("
        SELECT id, nama, email, role, no_telp, alamat_default
        FROM users
        WHERE id = :id AND deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute(['id' => $sessionUser['id']]);
    $freshUser = $stmt->fetch();

    if (!$freshUser) {
        $_SESSION = [];
        session_destroy();
        sendJson(true, 'Akun tidak aktif.', [
            'authenticated' => false,
            'user'          => null,
        ]);
    }

    $freshUser['id'] = (int) $freshUser['id'];
    $_SESSION['user'] = $freshUser;

    sendJson(true, 'Sesi aktif.', [
        'authenticated' => true,
        'user'          => $freshUser,
    ]);
} catch (Throwable $e) {
    sendJson(false, 'Terjadi kesalahan server: ' . $e->getMessage(), null, 500);
}