<?php
require_once __DIR__ . '/../config/db.php';

$date = $_GET['date'] ?? date('Y-m-d');
$base = new DateTime($date);
$base->modify('monday this week');
$days = [];
for ($i=0; $i<7; $i++) {
  $d = clone $base;
  $d->modify("+{$i} day");
  $days[] = $d;
}

try {
  $pdo = db();
  $stmt = $pdo->prepare("
    SELECT r.id, r.start_datetime, r.end_datetime, r.status,
           c.name AS customer_name,
           s.name AS staff_name,
           m.name AS menu_name
    FROM reservations r
    LEFT JOIN customers c ON c.id = r.customer_id
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) BETWEEN ? AND ?
      AND r.status IN ('reserved','confirmed','completed')
    ORDER BY r.start_datetime ASC
  ");
  $stmt->execute([$days[0]->format('Y-m-d'), $days[6]->format('Y-m-d')]);
  $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
  $reservations = [];
}

$byDate = [];
foreach ($reservations as $r) {
  $key = date('Y-m-d', strtotime($r['start_datetime']));
  $byDate[$key][] = $r;
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Week | ON;ME</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=13">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>Salon</strong></div>
    <nav>
      <a href="./index.php">Dashboard</a>
      <a class="active" href="./calendar-pro.php">Calendar Pro</a>
      <a href="./reservations.php">Reservations</a>
      <a href="./customers.php">Customers</a>
    </nav>
  </aside>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">WEEK VIEW</p>
        <h1>Week</h1>
      </div>
      <div class="pro-actions">
        <a class="pro-button" href="./calendar-week.php?date=<?= date('Y-m-d', strtotime($date.' -7 day')) ?>">← 前週</a>
        <a class="pro-button primary" href="./calendar-pro.php?date=<?= htmlspecialchars($date) ?>">Day</a>
        <a class="pro-button" href="./calendar-week.php?date=<?= date('Y-m-d', strtotime($date.' +7 day')) ?>">翌週 →</a>
      </div>
    </header>

    <?php if (!empty($error)): ?><section class="week-card"><?= htmlspecialchars($error) ?></section><?php endif; ?>

    <section class="week-grid">
      <?php foreach($days as $d): ?>
        <?php $key = $d->format('Y-m-d'); ?>
        <article class="week-card <?= $key === date('Y-m-d') ? 'today' : '' ?>">
          <div class="week-head">
            <small><?= strtoupper($d->format('D')) ?></small>
            <strong><?= $d->format('m/d') ?></strong>
          </div>
          <div class="week-items">
            <?php if (empty($byDate[$key])): ?>
              <p>予約なし</p>
            <?php endif; ?>
            <?php foreach($byDate[$key] ?? [] as $r): ?>
              <a href="./calendar-pro.php?date=<?= $key ?>" class="week-item">
                <strong><?= date('H:i', strtotime($r['start_datetime'])) ?> <?= htmlspecialchars($r['customer_name'] ?? 'お客様') ?></strong>
                <span><?= htmlspecialchars($r['staff_name'] ?? '-') ?> / <?= htmlspecialchars($r['menu_name'] ?? '-') ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  </main>
</body>
</html>
