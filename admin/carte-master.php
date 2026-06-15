<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$error = '';
$customers = [];

try {
  $pdo = db();

  $customerCols = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_ASSOC));

  if (!in_array('is_active', $customerCols, true)) {
    $pdo->exec("ALTER TABLE customers ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
    $customerCols[] = 'is_active';
  }

  $customers = $pdo->query("
    SELECT
      c.*,
      COUNT(r.id) AS visit_count,
      MAX(r.start_datetime) AS last_visit,
      COALESCE(SUM(m.price),0) AS ltv
    FROM customers c
    LEFT JOIN reservations r ON r.customer_id = c.id
      AND r.status IN ('reserved','confirmed','completed')
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE COALESCE(c.is_active,1) = 1
    GROUP BY c.id
    ORDER BY c.id DESC
    LIMIT 300
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Carte Master | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=89">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=89">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=89">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=89">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=8">
  <link rel="stylesheet" href="../assets/css/carte-master-v2.css?v=2">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">CUSTOMER CARTE</p>
        <h1>Carte Master</h1>
      </div>
      <div class="pro-actions">
        <a class="pro-button primary" href="./customer-create.php">顧客情報追加</a>
      </div>
    </header>

    <?php if ($error): ?><section class="os-panel"><?= htmlspecialchars($error) ?></section><?php endif; ?>

    <section class="carte-grid">
      <?php if (!$customers): ?>
        <section class="os-panel">顧客データがありません。</section>
      <?php endif; ?>

      <?php foreach ($customers as $c): ?>
        <?php
          $name = trim((string)($c['name'] ?? 'お客様'));
          $displayName = preg_match('/様$/u', $name) ? $name : $name . '様';
          $lastVisit = !empty($c['last_visit']) ? date('Y/m/d H:i', strtotime($c['last_visit'])) : '来店履歴なし';
          $visitCount = (int)($c['visit_count'] ?? 0);
          $ltv = (int)($c['ltv'] ?? 0);
          $rank = $ltv >= 200000 ? 'ROYAL' : ($ltv >= 100000 ? 'VIP' : 'NORMAL');
        ?>
        <article class="customer-card" data-customer-id="<?= (int)$c['id'] ?>" data-customer-name="<?= htmlspecialchars($displayName) ?>">
          <a class="customer-card-link" href="./customer-360.php?id=<?= (int)$c['id'] ?>">
            <div class="customer-card-head">
              <div>
                <strong class="customer-name press-delete-target"><?= htmlspecialchars($displayName) ?></strong>
                <span><?= htmlspecialchars($c['phone'] ?? '-') ?></span>
              </div>
              <em><?= htmlspecialchars($rank) ?></em>
            </div>

            <div class="customer-meta">
              <article>
                <span>最終来店</span>
                <strong><?= htmlspecialchars($lastVisit) ?></strong>
              </article>
              <article>
                <span>来店回数</span>
                <strong><?= number_format($visitCount) ?>回</strong>
              </article>
              <article>
                <span>LTV</span>
                <strong>¥<?= number_format($ltv) ?></strong>
              </article>
            </div>
          </a>
        </article>
      <?php endforeach; ?>
    </section>
  </main>

  <script src="../assets/js/carte-master-v2.js?v=2"></script>
</body>
</html>
