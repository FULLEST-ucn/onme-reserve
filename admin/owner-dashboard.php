<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();

$month = date('Y-m');
$start = $month . '-01';
$end = date('Y-m-t');

$kpi = ['sales'=>0,'reservations'=>0,'customers'=>0,'avg'=>0,'repeat_rate'=>0];
$staff = [];

try {
  $pdo = db();
  $stmt = $pdo->prepare("
    SELECT COALESCE(SUM(m.price),0) sales, COUNT(r.id) reservations, COUNT(DISTINCT r.customer_id) customers
    FROM reservations r
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) BETWEEN ? AND ?
      AND r.status IN ('reserved','confirmed','completed')
  ");
  $stmt->execute([$start,$end]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
  $kpi['sales'] = (int)$row['sales'];
  $kpi['reservations'] = (int)$row['reservations'];
  $kpi['customers'] = (int)$row['customers'];
  $kpi['avg'] = $kpi['reservations'] ? floor($kpi['sales'] / $kpi['reservations']) : 0;

  $stmt = $pdo->prepare("
    SELECT s.name, COUNT(r.id) count, COALESCE(SUM(m.price),0) sales
    FROM reservations r
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) BETWEEN ? AND ?
      AND r.status IN ('reserved','confirmed','completed')
    GROUP BY s.id, s.name
    ORDER BY sales DESC
  ");
  $stmt->execute([$start,$end]);
  $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT customer_id, COUNT(*) c FROM reservations
    WHERE DATE(start_datetime) BETWEEN ? AND ?
    GROUP BY customer_id
  ");
  $stmt->execute([$start,$end]);
  $total = 0; $repeat = 0;
  foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r){ $total++; if((int)$r['c'] >= 2) $repeat++; }
  $kpi['repeat_rate'] = $total ? round($repeat / $total * 100) : 0;
} catch(Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Owner Dashboard | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=20">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=20">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>Owner</strong></div>
    <nav>
      <a href="./staff-dashboard.php">Today</a>
      <a href="./calendar-pro.php">Calendar</a>
      <a href="./analytics-pro.php">Analytics</a>
      <a href="./crm-pro.php">LINE CRM</a>
      <a href="./pos-pro.php">POS</a>
      <a href="./ai-concierge.php">AI</a>
      <a class="active" href="./owner-dashboard.php">Owner</a>
    </nav>
  </aside>

  <main class="pro-main">
    <header class="pro-top"><div><p class="eyebrow">OWNER KPI</p><h1>Owner Dashboard</h1></div></header>
    <section class="owner-hero">
      <article><span>月商</span><strong>¥<?= number_format($kpi['sales']) ?></strong></article>
      <article><span>予約</span><strong><?= number_format($kpi['reservations']) ?></strong></article>
      <article><span>客単価</span><strong>¥<?= number_format($kpi['avg']) ?></strong></article>
      <article><span>リピート率</span><strong><?= $kpi['repeat_rate'] ?>%</strong></article>
    </section>

    <section class="os-two">
      <section class="os-panel">
        <p class="eyebrow">STAFF RANKING</p>
        <h2>スタッフランキング</h2>
        <div class="os-list">
          <?php foreach($staff as $i=>$s): ?>
            <article class="os-list-item">
              <strong><?= $i+1 ?>. <?= htmlspecialchars($s['name'] ?? '-') ?></strong>
              <span><?= (int)$s['count'] ?>件 / ¥<?= number_format((int)$s['sales']) ?></span>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="os-panel">
        <p class="eyebrow">AI INSIGHT</p>
        <h2>おすすめ施策</h2>
        <div class="os-list">
          <article class="os-list-item"><strong>再来率UP</strong><span>90日未来店にLINE配信をおすすめします。</span></article>
          <article class="os-list-item"><strong>売上UP</strong><span>人気メニュー上位をInstagram投稿に活用してください。</span></article>
          <article class="os-list-item"><strong>空き枠対策</strong><span>予約が少ない曜日に限定クーポンを配信してください。</span></article>
        </div>
      </section>
    </section>
  </main>
</body>
</html>
