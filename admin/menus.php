<?php
require_once __DIR__ . '/../config/db.php';

try {
  $pdo = db();
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 0);
    $price = (int)($_POST['price'] ?? 0);
    if ($name && $duration > 0) {
      $stmt = $pdo->prepare("INSERT INTO menus (name, duration_minutes, price, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())");
      $stmt->execute([$name, $duration, $price]);
    }
    header('Location: ./menus.php');
    exit;
  }
  $menus = $pdo->query("SELECT * FROM menus ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
  $menus = [];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>メニュー管理 | ON;ME</title>
  <link rel="stylesheet" href="../assets/css/admin.css?v=6">
</head>
<body class="admin-body">
<header class="admin-header">
  <div><p class="eyebrow">ON;ME OS</p><h1>Menus</h1></div>
  <nav class="admin-nav">
    <a href="./index.php">Dashboard</a>
    <a href="./calendar.php">Calendar</a>
    <a href="./reservations.php">予約</a>
    <a href="./customers.php">顧客</a>
    <a class="active" href="./menus.php">メニュー</a>
  </nav>
</header>
<main class="admin-shell split-layout">
  <?php if (!empty($error)): ?><section class="panel notice"><?= htmlspecialchars($error) ?></section><?php endif; ?>
  <section class="panel">
    <div class="panel-head"><div><p class="eyebrow">CREATE</p><h2>メニュー追加</h2></div></div>
    <form method="post" class="admin-form">
      <label>メニュー名<input type="text" name="name" required placeholder="ワンカラー"></label>
      <label>所要時間（分）<input type="number" name="duration_minutes" required value="90"></label>
      <label>料金<input type="number" name="price" required value="6500"></label>
      <button class="primary full" type="submit">追加する</button>
    </form>
  </section>
  <section class="panel">
    <div class="panel-head"><div><p class="eyebrow">LIST</p><h2>登録メニュー</h2></div></div>
    <div class="menu-list">
      <?php foreach($menus as $m): ?>
        <article class="menu-row">
          <strong><?= htmlspecialchars($m['name']) ?></strong>
          <span><?= (int)$m['duration_minutes'] ?>分 / ¥<?= number_format((int)$m['price']) ?></span>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</main>
</body>
</html>
