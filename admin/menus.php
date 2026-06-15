<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$error = '';
$saved = false;

try {
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 0);
    $price = (int)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($name !== '' && $duration > 0) {
      $stmt = $pdo->prepare("
        INSERT INTO menus (name, duration_minutes, price, description, is_active, sort_order, created_at, updated_at)
        VALUES (?, ?, ?, ?, 1, 999, NOW(), NOW())
      ");
      $stmt->execute([$name, $duration, $price, $description]);
      $saved = true;
    }
  }

  $menus = $pdo->query("
    SELECT *
    FROM menus
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
  $menus = [];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Menus | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=62">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=62">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=62">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=62">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=2">
  <link rel="stylesheet" href="../assets/css/menus-pro.css?v=1">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">MENU MANAGEMENT</p>
        <h1>Menus</h1>
      </div>
      <div class="pro-actions">
        <a class="pro-button primary" href="./menu-master.php">Menu Master</a>
      </div>
    </header>

    <?php if ($saved): ?>
      <section class="os-panel success">メニューを追加しました。</section>
    <?php endif; ?>

    <?php if ($error): ?>
      <section class="os-panel"><?= htmlspecialchars($error) ?></section>
    <?php endif; ?>

    <section class="menus-layout">
      <article class="os-panel">
        <p class="eyebrow">CREATE</p>
        <h2>メニュー追加</h2>

        <form method="post" class="menu-form">
          <label>
            メニュー名
            <input name="name" required placeholder="例）ワンカラー">
          </label>

          <div class="menu-form-grid">
            <label>
              所要時間
              <input type="number" name="duration_minutes" required value="90">
            </label>

            <label>
              料金
              <input type="number" name="price" required value="6500">
            </label>
          </div>

          <label>
            説明
            <textarea name="description" rows="4" placeholder="メニュー説明を入力"></textarea>
          </label>

          <button type="submit">追加する</button>
        </form>
      </article>

      <article class="os-panel">
        <p class="eyebrow">LIST</p>
        <h2>登録メニュー</h2>

        <div class="menu-list-pro">
          <?php if (!$menus): ?>
            <p class="muted-text">登録メニューがありません。</p>
          <?php endif; ?>

          <?php foreach ($menus as $menu): ?>
            <article class="menu-card-pro">
              <div>
                <strong><?= htmlspecialchars($menu['name']) ?></strong>
                <span><?= (int)$menu['duration_minutes'] ?>分 / ¥<?= number_format((int)$menu['price']) ?></span>
                <?php if (!empty($menu['description'])): ?>
                  <small><?= htmlspecialchars($menu['description']) ?></small>
                <?php endif; ?>
              </div>
              <em>ACTIVE</em>
            </article>
          <?php endforeach; ?>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
