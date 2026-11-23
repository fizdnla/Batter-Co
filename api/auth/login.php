<?php
// api/auth/login.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth_middleware.php';

setApiHeaders('POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(false, 'Metode request tidak diizinkan.', null, 405);
}

$input = !empty($_POST) ? $_POST : getJsonInput();
$email = trim((string) ($input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($email === '' || $password === '') {
    sendJson(false, 'Email dan password wajib diisi.', null, 400);
}

try {
    $db = (new Database())->getConnection();

    $stmt = $db->prepare("
        SELECT id, nama, email, password, role, no_telp, alamat_default
        FROM users
        WHERE email = :email AND deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        sendJson(false, 'Email atau password salah.', null, 401);
    }

    session_regenerate_id(true);

    $sessionUser = [
        'id'             => (int) $user['id'],
        'nama'           => $user['nama'],
        'email'          => $user['email'],
        'role'           => $user['role'],
        'no_telp'        => $user['no_telp'],
        'alamat_default' => $user['alamat_default'],
    ];

    $_SESSION['user'] = $sessionUser;

    sendJson(true, 'Login berhasil.', [
        'user' => $sessionUser,
    ]);
} catch (Throwable $e) {
    sendJson(false, 'Terjadi kesalahan server: ' . $e->getMessage(), null, 500);
}