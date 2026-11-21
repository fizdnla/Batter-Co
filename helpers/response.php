<?php
// helpers/response.php

declare(strict_types=1);

function setApiHeaders(string $allowedMethods = 'GET, POST, PUT, DELETE, OPTIONS'): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Methods: {$allowedMethods}");
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

function sendJson(bool $success, string $message, mixed $data = null, int $statusCode = 200): void
{
    http_response_code($statusCode);
    $payload = [
        'success' => $success,
        'message' => $message,
    ];

    if ($data !== null) {
        $payload['data'] = $data;
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput(): array
{
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw ?: '{}', true);
    return is_array($decoded) ? $decoded : [];
}