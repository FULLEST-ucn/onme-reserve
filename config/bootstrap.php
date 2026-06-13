<?php
declare(strict_types=1);

session_start();
date_default_timezone_set('Asia/Tokyo');

define('BASE_PATH', dirname(__DIR__));
define('STORAGE_PATH', BASE_PATH . '/storage');

function app_config(): array {
    static $config = null;
    if ($config === null) {
        $config = require BASE_PATH . '/config/app.php';
    }
    return $config;
}

function storage_json(string $name, array $default = []): array {
    $path = STORAGE_PATH . '/' . $name . '.json';
    if (!file_exists($path)) {
        return $default;
    }
    $json = file_get_contents($path);
    $data = json_decode($json ?: '', true);
    return is_array($data) ? $data : $default;
}

function save_json(string $name, array $data): void {
    $path = STORAGE_PATH . '/' . $name . '.json';
    file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

function redirect(string $path): never {
    header('Location: ' . $path);
    exit;
}

function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function current_staff(): ?array {
    return $_SESSION['staff'] ?? null;
}

function require_admin(): void {
    if (!isset($_SESSION['staff'])) {
        redirect('/admin/login.php');
    }
}
