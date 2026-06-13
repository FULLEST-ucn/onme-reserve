<?php
require_once __DIR__ . '/../config/db.php';

$q = trim($_GET['q'] ?? '');
$customers = [];
$error = '';

try {
  $pdo = db();
  if ($q !== '') {
    $stmt = $pdo->prepare("
      SELECT c.*,
             MAX(r.start_datetime) AS last_visit,
             COUNT(r.id) AS visit_count
      FROM customers c
      LEFT JOIN reservations r ON r.customer_id = c.id
      WHERE c.name LIKE ? OR c.phone LIKE ?
      GROUP BY c.id
      ORDER BY last_visit DESC
      LIMIT 30
    ");
    $stmt->execute(["%{$q}%", "%{$q}%"]);
  } else {
    $stmt = $pdo->query("
      SELECT c.*,
             MAX(r.start_datetime) AS last_visit,
             COUNT(r.id) AS visit_count
      FROM customers c
      LEFT JOIN reservations r ON r.customer_id = c.id
      GROUP BY c.id
      ORDER BY c.updated_at DESC, c.id DESC
      LIMIT 30
    ");
  }
  $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Carte Pro | ON;ME</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=14">
  <link rel="stylesheet" href="../assets/css/carte-pro.css?v=14">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>Carte</strong></div>
    <nav>
      <a href="./index.php">Dashboard</a>
      <a href="./calendar-pro.php">Calendar Pro</a>
      <a href="./customers.php">Customers</a>
      <a class="active" href="./carte-pro.php">Carte Pro</a>
      <a href="./menus.php">Menus</a>
      <a href="./line-settings.php">LINE</a>
    </nav>
  </aside>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">CUSTOMER BEAUTY RECORD</p>
        <h1>Carte Pro</h1>
      </div>
      <form class="carte-search" method="get">
        <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="名前・電話番号で検索">
        <button type="submit">Search</button>
      </form>
    </header>

    <?php if ($error): ?>
      <section class="carte-panel"><?= htmlspecialchars($error) ?></section>
    <?php endif; ?>

    <section class="carte-grid">
      <?php if (!$customers): ?>
        <article class="carte-panel">顧客データがありません。</article>
      <?php endif; ?>

      <?php foreach($customers as $c): ?>
        <a class="carte-customer-card" href="./carte-detail.php?id=<?= (int)$c['id'] ?>">
          <div>
            <small>CUSTOMER</small>
            <strong><?= htmlspecialchars($c['name']) ?></strong>
            <span><?= htmlspecialchars($c['phone'] ?? '-') ?></span>
          </div>
          <dl>
            <div><dt>来店</dt><dd><?= (int)$c['visit_count'] ?>回</dd></div>
            <div><dt>最終</dt><dd><?= $c['last_visit'] ? date('m/d', strtotime($c['last_visit'])) : '-' ?></dd></div>
          </dl>
        </a>
      <?php endforeach; ?>
    </section>
  </main>
</body>
</html>
