<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$q = trim($_GET['q'] ?? '');
$rows = [];
try {
  $pdo = db();
  $sql = "
    SELECT c.*, COUNT(r.id) visit_count, MAX(r.start_datetime) last_visit,
           COALESCE(SUM(m.price),0) ltv
    FROM customers c
    LEFT JOIN reservations r ON r.customer_id = c.id
    LEFT JOIN menus m ON m.id = r.menu_id
  ";
  $params = [];
  if ($q !== '') { $sql .= " WHERE c.name LIKE ? OR c.phone LIKE ? "; $params=["%$q%","%$q%"]; }
  $sql .= " GROUP BY c.id ORDER BY c.updated_at DESC LIMIT 80";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) { $error=$e->getMessage(); }
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Carte Master | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=40">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=40">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=40">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>Carte</strong></div>
    <nav>
      <a href="./dashboard-v2.php">Dashboard V2</a>
      <a class="active" href="./carte-master.php">Carte Master</a>
      <a href="./carte-pro.php">Carte Pro</a>
    </nav>
  </aside>
  <main class="pro-main">
    <header class="pro-top">
      <div><p class="eyebrow">NOTION STYLE CARTE</p><h1>Carte Master</h1></div>
      <form class="suite40-search" method="get"><input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="顧客検索"><button>Search</button></form>
    </header>
    <section class="suite40-customer-grid">
      <?php foreach($rows as $c): ?>
      <a class="suite40-customer" href="./carte-detail.php?id=<?= (int)$c['id'] ?>">
        <small>CUSTOMER</small>
        <strong><?= htmlspecialchars($c['name']) ?></strong>
        <span><?= htmlspecialchars($c['phone'] ?? '-') ?></span>
        <dl><div><dt>来店</dt><dd><?= (int)$c['visit_count'] ?></dd></div><div><dt>LTV</dt><dd>¥<?= number_format((int)$c['ltv']) ?></dd></div></dl>
        <em>AI提案を見る →</em>
      </a>
      <?php endforeach; ?>
      <?php if(!$rows): ?><section class="os-panel">顧客データがありません。</section><?php endif; ?>
    </section>
  </main>
</body>
</html>
