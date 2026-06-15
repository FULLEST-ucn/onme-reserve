<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$error = '';
$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
  $month = date('Y-m');
}

$start = $month . '-01';
$end = date('Y-m-t', strtotime($start));

$kpi = [
  'gross' => 0,
  'net' => 0,
  'tax' => 0,
  'count' => 0,
  'avg_net' => 0,
  'repeat_rate' => 0,
];

$daily = [];
$menus = [];
$staffs = [];

function ensure_pos_sales_schema(PDO $pdo): void {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS pos_sales (
      id INT AUTO_INCREMENT PRIMARY KEY,
      reservation_id INT NULL,
      customer_id INT NULL,
      staff_id INT NULL,
      subtotal INT NOT NULL DEFAULT 0,
      discount INT NOT NULL DEFAULT 0,
      total INT NOT NULL DEFAULT 0,
      payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
      note TEXT NULL,
      status VARCHAR(50) NOT NULL DEFAULT 'paid',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_pos_sales_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");

  $cols = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM pos_sales")->fetchAll(PDO::FETCH_ASSOC));
  $add = [
    'reservation_id' => "ALTER TABLE pos_sales ADD COLUMN reservation_id INT NULL AFTER id",
    'customer_id' => "ALTER TABLE pos_sales ADD COLUMN customer_id INT NULL AFTER reservation_id",
    'staff_id' => "ALTER TABLE pos_sales ADD COLUMN staff_id INT NULL AFTER customer_id",
    'subtotal' => "ALTER TABLE pos_sales ADD COLUMN subtotal INT NOT NULL DEFAULT 0",
    'discount' => "ALTER TABLE pos_sales ADD COLUMN discount INT NOT NULL DEFAULT 0",
    'total' => "ALTER TABLE pos_sales ADD COLUMN total INT NOT NULL DEFAULT 0",
    'payment_method' => "ALTER TABLE pos_sales ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'cash'",
    'note' => "ALTER TABLE pos_sales ADD COLUMN note TEXT NULL",
    'status' => "ALTER TABLE pos_sales ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'paid'",
    'created_at' => "ALTER TABLE pos_sales ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
    'updated_at' => "ALTER TABLE pos_sales ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
  ];

  foreach ($add as $col => $sql) {
    if (!in_array($col, $cols, true)) {
      $pdo->exec($sql);
    }
  }
}

try {
  $pdo = db();
  ensure_pos_sales_schema($pdo);

  $stmt = $pdo->prepare("
    SELECT
      COALESCE(SUM(total),0) AS gross,
      COUNT(*) AS count
    FROM pos_sales
    WHERE DATE(created_at) BETWEEN ? AND ?
      AND COALESCE(status,'paid') <> 'void'
  ");
  $stmt->execute([$start, $end]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

  $kpi['gross'] = (int)($row['gross'] ?? 0);
  $kpi['net'] = (int)floor($kpi['gross'] / 1.1);
  $kpi['tax'] = $kpi['gross'] - $kpi['net'];
  $kpi['count'] = (int)($row['count'] ?? 0);
  $kpi['avg_net'] = $kpi['count'] ? (int)floor($kpi['net'] / $kpi['count']) : 0;

  $stmt = $pdo->prepare("
    SELECT customer_id, COUNT(*) AS c
    FROM pos_sales
    WHERE DATE(created_at) BETWEEN ? AND ?
      AND customer_id IS NOT NULL
      AND COALESCE(status,'paid') <> 'void'
    GROUP BY customer_id
  ");
  $stmt->execute([$start, $end]);
  $customerRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $totalCustomers = count($customerRows);
  $repeatCustomers = 0;
  foreach ($customerRows as $r) {
    if ((int)$r['c'] >= 2) $repeatCustomers++;
  }
  $kpi['repeat_rate'] = $totalCustomers ? round($repeatCustomers / $totalCustomers * 100) : 0;

  $stmt = $pdo->prepare("
    SELECT
      DATE(created_at) AS d,
      COALESCE(SUM(total),0) AS gross,
      COUNT(*) AS count
    FROM pos_sales
    WHERE DATE(created_at) BETWEEN ? AND ?
      AND COALESCE(status,'paid') <> 'void'
    GROUP BY DATE(created_at)
    ORDER BY d ASC
  ");
  $stmt->execute([$start, $end]);
  $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT
      COALESCE(m.name, '予約なし会計') AS name,
      COUNT(ps.id) AS count,
      COALESCE(SUM(ps.total),0) AS gross
    FROM pos_sales ps
    LEFT JOIN reservations r ON r.id = ps.reservation_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(ps.created_at) BETWEEN ? AND ?
      AND COALESCE(ps.status,'paid') <> 'void'
    GROUP BY m.id, m.name
    ORDER BY gross DESC
    LIMIT 10
  ");
  $stmt->execute([$start, $end]);
  $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT
      COALESCE(s.name, '未設定') AS name,
      COUNT(ps.id) AS count,
      COALESCE(SUM(ps.total),0) AS gross
    FROM pos_sales ps
    LEFT JOIN staffs s ON s.id = ps.staff_id
    WHERE DATE(ps.created_at) BETWEEN ? AND ?
      AND COALESCE(ps.status,'paid') <> 'void'
    GROUP BY ps.staff_id, s.name
    ORDER BY gross DESC
    LIMIT 10
  ");
  $stmt->execute([$start, $end]);
  $staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
  $error = $e->getMessage();
}

$maxDailyNet = 1;
foreach ($daily as $d) {
  $net = (int)floor((int)($d['gross'] ?? 0) / 1.1);
  $maxDailyNet = max($maxDailyNet, $net);
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Analytics | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=94">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=94">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=94">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=94">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=8">
  <link rel="stylesheet" href="../assets/css/analytics-pos-sync.css?v=1">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">BUSINESS ANALYTICS</p>
        <h1>Analytics</h1>
        <p class="analytics-lead">POS会計と連動し、税抜売上・税込売上・客単価を月次で確認できます。</p>
      </div>
      <form method="get" class="analytics-month-form">
        <input type="month" name="month" value="<?= htmlspecialchars($month) ?>">
        <button>表示</button>
      </form>
    </header>

    <?php if ($error): ?>
      <section class="os-panel"><?= htmlspecialchars($error) ?></section>
    <?php endif; ?>

    <section class="analytics-kpi">
      <article>
        <span>月間売上 税抜</span>
        <strong>¥<?= number_format($kpi['net']) ?></strong>
        <em>税込 ¥<?= number_format($kpi['gross']) ?></em>
      </article>
      <article>
        <span>会計数</span>
        <strong><?= number_format($kpi['count']) ?></strong>
        <em>VOID除外</em>
      </article>
      <article>
        <span>客単価 税抜</span>
        <strong>¥<?= number_format($kpi['avg_net']) ?></strong>
        <em>税込ベース ¥<?= number_format($kpi['count'] ? floor($kpi['gross'] / $kpi['count']) : 0) ?></em>
      </article>
      <article>
        <span>消費税</span>
        <strong>¥<?= number_format($kpi['tax']) ?></strong>
        <em>10%内税計算</em>
      </article>
      <article>
        <span>リピート率</span>
        <strong><?= number_format($kpi['repeat_rate']) ?>%</strong>
        <em>POS顧客連動ベース</em>
      </article>
    </section>

    <section class="analytics-layout">
      <article class="os-panel analytics-sales-panel">
        <p class="eyebrow">DAILY SALES</p>
        <h2>日別売上</h2>

        <div class="analytics-bars">
          <?php if (!$daily): ?>
            <p class="muted-text">データがありません。</p>
          <?php endif; ?>

          <?php foreach ($daily as $d): ?>
            <?php
              $gross = (int)($d['gross'] ?? 0);
              $net = (int)floor($gross / 1.1);
              $count = (int)($d['count'] ?? 0);
              $avgNet = $count ? (int)floor($net / $count) : 0;
            ?>
            <div>
              <span><?= htmlspecialchars(date('m/d', strtotime($d['d']))) ?></span>
              <i style="width: <?= max(2, ($net / $maxDailyNet) * 100) ?>%"></i>
              <b>税抜 ¥<?= number_format($net) ?></b>
              <em>税込 ¥<?= number_format($gross) ?> / 客単価 ¥<?= number_format($avgNet) ?></em>
            </div>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="os-panel">
        <p class="eyebrow">MENU TOP</p>
        <h2>メニュー別売上</h2>

        <div class="analytics-list">
          <?php if (!$menus): ?><p class="muted-text">データがありません。</p><?php endif; ?>
          <?php foreach ($menus as $i => $m): ?>
            <?php $gross = (int)($m['gross'] ?? 0); $net = (int)floor($gross / 1.1); ?>
            <article>
              <strong><?= $i + 1 ?>. <?= htmlspecialchars($m['name'] ?? '-') ?></strong>
              <span>税抜 ¥<?= number_format($net) ?> / 税込 ¥<?= number_format($gross) ?> / <?= (int)($m['count'] ?? 0) ?>件</span>
            </article>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="os-panel">
        <p class="eyebrow">STAFF TOP</p>
        <h2>スタッフ別売上</h2>

        <div class="analytics-list">
          <?php if (!$staffs): ?><p class="muted-text">データがありません。</p><?php endif; ?>
          <?php foreach ($staffs as $i => $s): ?>
            <?php $gross = (int)($s['gross'] ?? 0); $net = (int)floor($gross / 1.1); ?>
            <article>
              <strong><?= $i + 1 ?>. <?= htmlspecialchars($s['name'] ?? '-') ?></strong>
              <span>税抜 ¥<?= number_format($net) ?> / 税込 ¥<?= number_format($gross) ?> / <?= (int)($s['count'] ?? 0) ?>件</span>
            </article>
          <?php endforeach; ?>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
