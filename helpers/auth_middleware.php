<?php
// helpers/auth_middleware.php

declare(strict_types=1);

require_once __DIR__ . '/response.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getCurrentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(): array
{
    $user = getCurrentUser();
    if (!$user) {
        sendJson(false, 'Sesi berakhir atau Anda belum login.', null, 401);
    }

    return $user;
}

function requireRole(string|array $allowedRoles): array
{
    $user = requireLogin();
    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];

    if (!in_array($user['role'], $roles, true)) {
        sendJson(false, 'Akses ditolak. Anda tidak memiliki izin untuk fitur ini.', null, 403);
    }

    return $user;
}