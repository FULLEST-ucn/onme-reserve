<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();

$rows = [];
try {
  $pdo = db();
  $rows = $pdo->query("
    SELECT g.*, r.start_datetime, r.end_datetime, c.name AS customer_name
    FROM google_calendar_sync g
    LEFT JOIN reservations r ON r.id = g.reservation_id
    LEFT JOIN customers c ON c.id = r.customer_id
    ORDER BY g.created_at DESC
    LIMIT 100
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Google Calendar | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=25">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=25">
  <link rel="stylesheet" href="../assets/css/suite-pro.css?v=25">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>Google</strong></div>
    <nav>
      <a href="./owner-dashboard.php">Owner</a>
      <a href="./calendar-pro.php">Calendar Pro</a>
      <a class="active" href="./google-calendar-pro.php">Google Sync</a>
      <a href="./settings-pro.php">Settings</a>
    </nav>
  </aside>
  <main class="pro-main">
    <header class="pro-top"><div><p class="eyebrow">CALENDAR SYNC</p><h1>Google Calendar</h1></div></header>
    <section class="os-panel">
      <p class="eyebrow">SYNC STATUS</p>
      <h2>同期キュー</h2>
      <div class="os-list">
        <?php if(!$rows): ?><p class="muted-text">同期データはまだありません。</p><?php endif; ?>
        <?php foreach($rows as $r): ?>
        <article class="os-list-item">
          <strong><?= htmlspecialchars($r['customer_name'] ?? '-') ?> / <?= htmlspecialchars($r['status']) ?></strong>
          <span><?= $r['start_datetime'] ? date('Y/m/d H:i', strtotime($r['start_datetime'])) : '-' ?></span>
        </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</body>
</html>
