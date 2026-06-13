<?php
require_once __DIR__ . '/../config/db.php';
$id = (int)($_GET['id'] ?? 0);

try {
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memo = trim($_POST['memo'] ?? '');
    $stmt = $pdo->prepare("UPDATE customers SET memo = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$memo, $id]);

    if (!empty($_FILES['photo']['name'])) {
      $uploadDir = __DIR__ . '/../uploads/customer_photos/';
      if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

      $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
      $allowed = ['jpg', 'jpeg', 'png', 'webp'];

      if (in_array($ext, $allowed, true)) {
        $filename = 'customer_' . $id . '_' . date('YmdHis') . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename);

        $stmt = $pdo->prepare("
          INSERT INTO customer_photos (customer_id, file_path, note, created_at)
          VALUES (?, ?, '', NOW())
        ");
        $stmt->execute([$id, 'uploads/customer_photos/' . $filename]);
      }
    }

    header('Location: ./customer-detail.php?id=' . $id);
    exit;
  }

  $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
  $stmt->execute([$id]);
  $customer = $stmt->fetch(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT r.*, s.name AS staff_name, m.name AS menu_name
    FROM reservations r
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE r.customer_id = ?
    ORDER BY r.start_datetime DESC
  ");
  $stmt->execute([$id]);
  $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("SELECT * FROM customer_photos WHERE customer_id = ? ORDER BY created_at DESC");
  $stmt->execute([$id]);
  $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>カルテ | ON;ME</title>
  <link rel="stylesheet" href="../assets/css/admin.css?v=7">
</head>
<body class="admin-body">
<header class="admin-header">
  <div><p class="eyebrow">CUSTOMER CARD</p><h1>Carte</h1></div>
  <nav class="admin-nav">
    <a href="./customers.php">顧客一覧へ</a>
    <a href="./index.php">Dashboard</a>
  </nav>
</header>
<main class="admin-shell">
  <?php if (!empty($error)): ?><section class="panel notice"><?= htmlspecialchars($error) ?></section><?php endif; ?>
  <?php if (empty($customer)): ?>
    <section class="panel notice">顧客が見つかりません。</section>
  <?php else: ?>
    <section class="split-layout">
      <section class="panel customer-detail">
        <div class="panel-head">
          <div>
            <p class="eyebrow">PROFILE</p>
            <h2><?= htmlspecialchars($customer['name']) ?></h2>
          </div>
          <p><?= htmlspecialchars($customer['phone'] ?? '-') ?></p>
        </div>
        <form method="post" enctype="multipart/form-data" class="admin-form">
          <label>カルテメモ
            <textarea name="memo" rows="8"><?= htmlspecialchars($customer['memo'] ?? '') ?></textarea>
          </label>
          <label>施術写真
            <input type="file" name="photo" accept="image/*">
          </label>
          <button class="primary full" type="submit">カルテを保存</button>
        </form>
      </section>

      <section class="panel">
        <div class="panel-head"><div><p class="eyebrow">PHOTOS</p><h2>施術写真</h2></div></div>
        <div class="photo-grid">
          <?php if (!$photos): ?><p class="empty-cell">写真はまだありません。</p><?php endif; ?>
          <?php foreach($photos as $p): ?>
            <img src="../<?= htmlspecialchars($p['file_path']) ?>" alt="">
          <?php endforeach; ?>
        </div>
      </section>
    </section>

    <section class="panel" style="margin-top:18px;">
      <div class="panel-head"><div><p class="eyebrow">HISTORY</p><h2>来店履歴</h2></div></div>
      <div class="history-list">
        <?php if (!$history): ?><p class="empty-cell">来店履歴はありません。</p><?php endif; ?>
        <?php foreach($history as $h): ?>
          <article class="history-card">
            <strong><?= date('Y/m/d H:i', strtotime($h['start_datetime'])) ?></strong>
            <span><?= htmlspecialchars($h['staff_name'] ?? '-') ?> / <?= htmlspecialchars($h['menu_name'] ?? '-') ?></span>
            <em><?= htmlspecialchars($h['status']) ?></em>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</main>
</body>
</html>
