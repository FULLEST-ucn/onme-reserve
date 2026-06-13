<?php
require_once __DIR__ . '/db.php';

function current_store_id(): int {
  return 1;
}

function current_store(): array {
  try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM stores WHERE id = ? LIMIT 1");
    $stmt->execute([current_store_id()]);
    $store = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($store) return $store;
  } catch (Throwable $e) {}
  return ['id' => 1, 'name' => 'ON;ME NAIL', 'slug' => 'onme-nail'];
}
