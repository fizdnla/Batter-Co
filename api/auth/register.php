<?php
// api/auth/register.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth_middleware.php';

setApiHeaders('POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(false, 'Metode request tidak diizinkan.', null, 405);
}

$input = !empty($_POST) ? $_POST : getJsonInput();
$nama = trim((string) ($input['nama'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');
$noTelp = trim((string) ($input['no_telp'] ?? ''));
$alamatDefault = trim((string) ($input['alamat_default'] ?? ''));

if ($nama === '' || $email === '' || $password === '') {
    sendJson(false, 'Nama, email, dan password wajib diisi.', null, 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJson(false, 'Format email tidak valid.', null, 400);
}

if (strlen($password) < 6) {
    sendJson(false, 'Password minimal terdiri dari 6 karakter.', null, 400);
}

$db = (new Database())->getConnection();

try {
    // Cek duplikasi email
    $stmtCek = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmtCek->execute(['email' => $email]);
    if ($stmtCek->fetch()) {
        sendJson(false, 'Email sudah terdaftar. Silakan gunakan email lain atau login.', null, 409);
    }

    $db->beginTransaction();

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmtInsert = $db->prepare("
        INSERT INTO users (nama, email, password, role, no_telp, alamat_default, created_at, updated_at)
        VALUES (:nama, :email, :password, 'pelanggan', :no_telp, :alamat_default, NOW(), NOW())
    ");
    $stmtInsert->execute([
        'nama'           => $nama,
        'email'          => $email,
        'password'       => $hashedPassword,
        'no_telp'        => $noTelp !== '' ? $noTelp : null,
        'alamat_default' => $alamatDefault !== '' ? $alamatDefault : null,
    ]);

    $newUserId = (int) $db->lastInsertId();

    // Inisialisasi keranjang persisten pelanggan
    $stmtCart = $db->prepare("INSERT INTO keranjang (user_id, last_updated) VALUES (:user_id, NOW())");
    $stmtCart->execute(['user_id' => $newUserId]);

    $db->commit();

    session_regenerate_id(true);

    $sessionUser = [
        'id'             => $newUserId,
        'nama'           => $nama,
        'email'          => $email,
        'role'           => 'pelanggan',
        'no_telp'        => $noTelp !== '' ? $noTelp : null,
        'alamat_default' => $alamatDefault !== '' ? $alamatDefault : null,
    ];

    $_SESSION['user'] = $sessionUser;

    sendJson(true, 'Registrasi berhasil.', [
        'user' => $sessionUser,
    ], 201);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    sendJson(false, 'Gagal memproses registrasi: ' . $e->getMessage(), null, 500);
}