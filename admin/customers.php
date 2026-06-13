<?php
require_once __DIR__ . '/../config/db.php';

$rows = [];
try {
  $pdo = db();
  $stmt = $pdo->query("
    SELECT c.id, c.name, c.phone, c.line_user_id, c.memo,
           COUNT(r.id) AS visit_count,
           MAX(r.start_datetime) AS last_visit
    FROM customers c
    LEFT JOIN reservations r ON r.customer_id = c.id
    GROUP BY c.id
    ORDER BY c.updated_at DESC, c.id DESC
    LIMIT 200
  ");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>顧客一覧 | ON;ME</title>
  <link rel="stylesheet" href="../assets/css/admin.css?v=6">
</head>
<body class="admin-body">
<header class="admin-header">
  <div><p class="eyebrow">ON;ME OS</p><h1>Customers</h1></div>
  <nav class="admin-nav">
    <a href="./index.php">Dashboard</a>
    <a href="./calendar.php">Calendar</a>
    <a href="./reservations.php">予約</a>
    <a class="active" href="./customers.php">顧客</a>
    <a href="./menus.php">メニュー</a>
  </nav>
</header>
<main class="admin-shell">
  <?php if (!empty($error)): ?><section class="panel notice"><?= htmlspecialchars($error) ?></section><?php endif; ?>
  <section class="customer-grid">
    <?php if (!$rows): ?><section class="panel notice">顧客データがありません。</section><?php endif; ?>
    <?php foreach($rows as $c): ?>
      <article class="customer-card">
        <div>
          <p class="eyebrow">CUSTOMER</p>
          <h2><?= htmlspecialchars($c['name'] ?? '-') ?></h2>
        </div>
        <dl>
          <div><dt>電話</dt><dd><?= htmlspecialchars($c['phone'] ?? '-') ?></dd></div>
          <div><dt>来店回数</dt><dd><?= (int)$c['visit_count'] ?>回</dd></div>
          <div><dt>最終来店</dt><dd><?= $c['last_visit'] ? date('Y/m/d', strtotime($c['last_visit'])) : '-' ?></dd></div>
        </dl>
        <a class="admin-link-button" href="./customer-detail.php?id=<?= (int)$c['id'] ?>">カルテを見る</a>
      </article>
    <?php endforeach; ?>
  </section>
</main>
</body>
</html>
