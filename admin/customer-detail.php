<?php
require_once __DIR__ . '/../config/db.php';
$id = (int)($_GET['id'] ?? 0);

try {
  $pdo = db();
  $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
  $stmt->execute([$id]);
  $customer = $stmt->fetch(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT r.*, s.name AS staff_name, m.name AS menu_name
    FROM reservations r
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE r.customer_id = ?
    ORDER BY r.start_datetime DESC
  ");
  $stmt->execute([$id]);
  $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>カルテ | ON;ME</title>
  <link rel="stylesheet" href="../assets/css/admin.css?v=6">
</head>
<body class="admin-body">
<header class="admin-header">
  <div><p class="eyebrow">CUSTOMER CARD</p><h1>Carte</h1></div>
  <nav class="admin-nav">
    <a href="./customers.php">顧客一覧へ</a>
    <a href="./index.php">Dashboard</a>
  </nav>
</header>
<main class="admin-shell">
  <?php if (!empty($error)): ?><section class="panel notice"><?= htmlspecialchars($error) ?></section><?php endif; ?>
  <?php if (empty($customer)): ?>
    <section class="panel notice">顧客が見つかりません。</section>
  <?php else: ?>
    <section class="panel customer-detail">
      <div class="panel-head">
        <div>
          <p class="eyebrow">PROFILE</p>
          <h2><?= htmlspecialchars($customer['name']) ?></h2>
        </div>
        <p><?= htmlspecialchars($customer['phone'] ?? '-') ?></p>
      </div>
      <div class="history-list">
        <?php if (!$history): ?><p class="empty-cell">来店履歴はありません。</p><?php endif; ?>
        <?php foreach($history as $h): ?>
          <article class="history-card">
            <strong><?= date('Y/m/d H:i', strtotime($h['start_datetime'])) ?></strong>
            <span><?= htmlspecialchars($h['staff_name'] ?? '-') ?> / <?= htmlspecialchars($h['menu_name'] ?? '-') ?></span>
            <em><?= htmlspecialchars($h['status']) ?></em>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</main>
</body>
</html>
