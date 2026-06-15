<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$error = '';
$todayReservations = [];
$dailySales = [];
$staffRanking = [];

$todaySalesGross = 0;
$todaySalesNet = 0;
$monthSalesGross = 0;
$monthSalesNet = 0;
$todayReservationCount = 0;
$repeatRate = 0;
$todaySalesCount = 0;
$monthSalesCount = 0;
$todayAvgNet = 0;
$monthAvgNet = 0;

try {
  $pdo = db();

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

  $todayRow = $pdo->query("
    SELECT COALESCE(SUM(total),0) AS gross, COUNT(*) AS c
    FROM pos_sales
    WHERE DATE(created_at) = CURDATE()
      AND COALESCE(status,'paid') <> 'void'
  ")->fetch(PDO::FETCH_ASSOC) ?: [];

  $monthRow = $pdo->query("
    SELECT COALESCE(SUM(total),0) AS gross, COUNT(*) AS c
    FROM pos_sales
    WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
      AND COALESCE(status,'paid') <> 'void'
  ")->fetch(PDO::FETCH_ASSOC) ?: [];

  $todaySalesGross = (int)($todayRow['gross'] ?? 0);
  $todaySalesCount = (int)($todayRow['c'] ?? 0);
  $monthSalesGross = (int)($monthRow['gross'] ?? 0);
  $monthSalesCount = (int)($monthRow['c'] ?? 0);

  $todaySalesNet = (int)floor($todaySalesGross / 1.1);
  $monthSalesNet = (int)floor($monthSalesGross / 1.1);
  $todayAvgNet = $todaySalesCount ? (int)floor($todaySalesNet / $todaySalesCount) : 0;
  $monthAvgNet = $monthSalesCount ? (int)floor($monthSalesNet / $monthSalesCount) : 0;

  $todayReservationCount = (int)$pdo->query("
    SELECT COUNT(*)
    FROM reservations
    WHERE DATE(start_datetime) = CURDATE()
      AND status IN ('reserved','confirmed','completed')
  ")->fetchColumn();

  $todayReservations = $pdo->query("
    SELECT
      r.*,
      c.name AS customer_name,
      c.phone AS customer_phone,
      m.name AS menu_name,
      s.name AS staff_name
    FROM reservations r
    LEFT JOIN customers c ON c.id = r.customer_id
    LEFT JOIN menus m ON m.id = r.menu_id
    LEFT JOIN staffs s ON s.id = r.staff_id
    WHERE DATE(r.start_datetime) = CURDATE()
      AND r.status IN ('reserved','confirmed','completed')
    ORDER BY r.start_datetime ASC
    LIMIT 12
  ")->fetchAll(PDO::FETCH_ASSOC);

  $dailySales = $pdo->query("
    SELECT
      DATE(created_at) AS sales_date,
      COALESCE(SUM(total),0) AS gross,
      COUNT(*) AS count
    FROM pos_sales
    WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
      AND COALESCE(status,'paid') <> 'void'
    GROUP BY DATE(created_at)
    ORDER BY sales_date ASC
  ")->fetchAll(PDO::FETCH_ASSOC);

  $staffRanking = $pdo->query("
    SELECT
      COALESCE(s.name, '未設定') AS staff_name,
      COALESCE(SUM(ps.total),0) AS gross,
      COUNT(ps.id) AS count
    FROM pos_sales ps
    LEFT JOIN staffs s ON s.id = ps.staff_id
    WHERE DATE_FORMAT(ps.created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
      AND COALESCE(ps.status,'paid') <> 'void'
    GROUP BY ps.staff_id, s.name
    ORDER BY gross DESC
    LIMIT 5
  ")->fetchAll(PDO::FETCH_ASSOC);

  $customerRows = $pdo->query("
    SELECT customer_id, COUNT(*) AS c
    FROM pos_sales
    WHERE customer_id IS NOT NULL
      AND COALESCE(status,'paid') <> 'void'
    GROUP BY customer_id
  ")->fetchAll(PDO::FETCH_ASSOC);

  $totalCustomers = count($customerRows);
  $repeatCustomers = 0;
  foreach ($customerRows as $row) {
    if ((int)$row['c'] >= 2) $repeatCustomers++;
  }
  $repeatRate = $totalCustomers ? round($repeatCustomers / $totalCustomers * 100) : 0;

} catch (Throwable $e) {
  $error = $e->getMessage();
}

$maxDailyNet = 1;
foreach ($dailySales as $d) {
  $gross = (int)$d['gross'];
  $net = (int)floor($gross / 1.1);
  $maxDailyNet = max($maxDailyNet, $net);
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard V2 | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=93">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=93">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=93">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=93">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=8">
  <link rel="stylesheet" href="../assets/css/dashboard-v2-pos-sync.css?v=2">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">REALTIME SALON BI</p>
        <h1>Dashboard V2</h1>
      </div>
      <div class="pro-actions">
        <a class="pro-button primary" href="./pos-pro.php">POS</a>
      </div>
    </header>

    <?php if ($error): ?>
      <section class="os-panel"><?= htmlspecialchars($error) ?></section>
    <?php endif; ?>

    <section class="dashboard-kpi">
      <article>
        <span>本日売上 税抜</span>
        <strong>¥<?= number_format($todaySalesNet) ?></strong>
        <em>税込 ¥<?= number_format($todaySalesGross) ?></em>
      </article>
      <article>
        <span>今月売上 税抜</span>
        <strong>¥<?= number_format($monthSalesNet) ?></strong>
        <em>税込 ¥<?= number_format($monthSalesGross) ?></em>
      </article>
      <article>
        <span>客単価 税抜</span>
        <strong>¥<?= number_format($monthAvgNet) ?></strong>
        <em>今月<?= number_format($monthSalesCount) ?>件ベース</em>
      </article>
      <article>
        <span>本日予約</span>
        <strong><?= number_format($todayReservationCount) ?></strong>
        <em>件</em>
      </article>
      <article>
        <span>リピート率</span>
        <strong><?= number_format($repeatRate) ?>%</strong>
        <em>POS顧客連動ベース</em>
      </article>
    </section>

    <section class="dashboard-layout">
      <article class="os-panel today-panel">
        <div class="panel-head">
          <div>
            <p class="eyebrow">TODAY</p>
            <h2>本日の予約</h2>
          </div>
          <a href="./calendar-master.php">カレンダーを見る</a>
        </div>

        <div class="today-list">
          <?php if (!$todayReservations): ?>
            <p class="muted-text">本日の予約はありません。</p>
          <?php endif; ?>

          <?php foreach ($todayReservations as $r): ?>
            <article>
              <time><?= htmlspecialchars(date('H:i', strtotime($r['start_datetime']))) ?></time>
              <div>
                <strong><?= htmlspecialchars($r['customer_name'] ?? 'お客様') ?></strong>
                <span><?= htmlspecialchars($r['menu_name'] ?? '-') ?> / <?= htmlspecialchars($r['staff_name'] ?? '-') ?></span>
              </div>
              <a href="./customer-360.php?id=<?= (int)($r['customer_id'] ?? 0) ?>">カルテ</a>
            </article>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="os-panel quick-panel">
        <p class="eyebrow">SHORTCUT</p>
        <h2>Quick Actions</h2>
        <div class="quick-list">
          <a href="./calendar-master.php">カレンダーを見る</a>
          <a href="./staff.php">スタッフ画面</a>
          <a href="./carte-master.php">カルテを見る</a>
          <a href="./analytics-pro.php">売上分析</a>
          <a href="./crm-pro.php">LINE配信</a>
          <a href="./menu-master.php">メニュー管理</a>
        </div>
      </article>

      <article class="os-panel sales-panel">
        <p class="eyebrow">MONTHLY SALES</p>
        <h2>日別売上</h2>

        <div class="sales-bars">
          <?php if (!$dailySales): ?>
            <p class="muted-text">データがありません。</p>
          <?php endif; ?>

          <?php foreach ($dailySales as $d): ?>
            <?php
              $gross = (int)$d['gross'];
              $net = (int)floor($gross / 1.1);
              $avgNet = ((int)$d['count']) ? (int)floor($net / (int)$d['count']) : 0;
            ?>
            <div>
              <span><?= htmlspecialchars(date('m/d', strtotime($d['sales_date']))) ?></span>
              <i style="width: <?= max(2, ($net / $maxDailyNet) * 100) ?>%"></i>
              <b>税抜 ¥<?= number_format($net) ?></b>
              <em>税込 ¥<?= number_format($gross) ?> / 客単価 ¥<?= number_format($avgNet) ?></em>
            </div>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="os-panel staff-panel">
        <p class="eyebrow">STAFF TOP</p>
        <h2>スタッフランキング</h2>

        <div class="staff-rank-list">
          <?php if (!$staffRanking): ?>
            <p class="muted-text">データがありません。</p>
          <?php endif; ?>

          <?php foreach ($staffRanking as $i => $s): ?>
            <?php $staffNet = (int)floor((int)$s['gross'] / 1.1); ?>
            <article>
              <strong><?= $i + 1 ?>. <?= htmlspecialchars($s['staff_name'] ?? '-') ?></strong>
              <span>税抜 ¥<?= number_format($staffNet) ?> / 税込 ¥<?= number_format((int)$s['gross']) ?> / <?= (int)$s['count'] ?>件</span>
            </article>
          <?php endforeach; ?>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
