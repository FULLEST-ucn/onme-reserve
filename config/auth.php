<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/db.php';

function auth_user(): ?array {
  return $_SESSION['auth_user'] ?? null;
}

function require_login(): array {
  $user = auth_user();
  if (!$user) {
    header('Location: ./login.php');
    exit;
  }
  return $user;
}

function require_owner(): array {
  $user = require_login();
  if (($user['role'] ?? '') !== 'owner') {
    http_response_code(403);
    echo '権限がありません。';
    exit;
  }
  return $user;
}

function can_view_owner_menu(): bool {
  $user = auth_user();
  return $user && (($user['role'] ?? '') === 'owner');
}
