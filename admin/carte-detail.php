<?php
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$error = '';

try {
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memo = trim($_POST['memo'] ?? '');
    $nailCondition = trim($_POST['nail_condition'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $materials = trim($_POST['materials'] ?? '');
    $designNote = trim($_POST['design_note'] ?? '');

    $stmt = $pdo->prepare("UPDATE customers SET memo = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$memo, $id]);

    $stmt = $pdo->prepare("
      INSERT INTO customer_carte_records
        (customer_id, nail_condition, color, materials, design_note, created_at)
      VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$id, $nailCondition, $color, $materials, $designNote]);

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
          VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$id, 'uploads/customer_photos/' . $filename, $designNote]);
      }
    }

    header('Location: ./carte-detail.php?id=' . $id . '&saved=1');
    exit;
  }

  $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
  $stmt->execute([$id]);
  $customer = $stmt->fetch(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT r.*, s.name AS staff_name, m.name AS menu_name, m.price
    FROM reservations r
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE r.customer_id = ?
    ORDER BY r.start_datetime DESC
    LIMIT 20
  ");
  $stmt->execute([$id]);
  $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("SELECT * FROM customer_photos WHERE customer_id = ? ORDER BY created_at DESC LIMIT 24");
  $stmt->execute([$id]);
  $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("SELECT * FROM customer_carte_records WHERE customer_id = ? ORDER BY created_at DESC LIMIT 20");
  $stmt->execute([$id]);
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Carte Detail | ON;ME</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=14">
  <link rel="stylesheet" href="../assets/css/carte-pro.css?v=14">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>Carte</strong></div>
    <nav>
      <a href="./carte-pro.php">Carte List</a>
      <a href="./calendar-pro.php">Calendar Pro</a>
      <a href="./customers.php">Customers</a>
      <a href="./reservations.php">Reservations</a>
    </nav>
  </aside>

  <main class="pro-main">
    <?php if ($error): ?><section class="carte-panel"><?= htmlspecialchars($error) ?></section><?php endif; ?>
    <?php if (!empty($_GET['saved'])): ?><section class="carte-panel success">カルテを保存しました。</section><?php endif; ?>

    <?php if (empty($customer)): ?>
      <section class="carte-panel">顧客が見つかりません。</section>
    <?php else: ?>
      <header class="carte-profile-hero">
        <div>
          <p class="eyebrow">CUSTOMER CARTE</p>
          <h1><?= htmlspecialchars($customer['name']) ?></h1>
          <p><?= htmlspecialchars($customer['phone'] ?? '-') ?></p>
        </div>
        <a class="pro-button primary" href="./calendar-pro.php">予約管理へ</a>
      </header>

      <section class="carte-layout">
        <section class="carte-panel">
          <div class="carte-panel-head">
            <p class="eyebrow">NEW RECORD</p>
            <h2>施術記録</h2>
          </div>
          <form class="carte-form" method="post" enctype="multipart/form-data">
            <label>爪の状態
              <textarea name="nail_condition" rows="3" placeholder="亀裂、浮き、乾燥、長さなど"></textarea>
            </label>
            <label>カラー履歴
              <input type="text" name="color" placeholder="例）ベージュ系 / マグネット03">
            </label>
            <label>使用商材
              <input type="text" name="materials" placeholder="例）ベース◯◯ / トップ◯◯">
            </label>
            <label>デザインメモ
              <textarea name="design_note" rows="4" placeholder="次回提案・好み・注意点など"></textarea>
            </label>
            <label>共通メモ
              <textarea name="memo" rows="5"><?= htmlspecialchars($customer['memo'] ?? '') ?></textarea>
            </label>
            <label>施術写真
              <input type="file" name="photo" accept="image/*">
            </label>
            <button class="primary full" type="submit">カルテを保存</button>
          </form>
        </section>

        <section class="carte-panel">
          <div class="carte-panel-head">
            <p class="eyebrow">PHOTO</p>
            <h2>施術写真</h2>
          </div>
          <div class="carte-photo-grid">
            <?php if (!$photos): ?><p class="muted-text">写真はまだありません。</p><?php endif; ?>
            <?php foreach($photos as $p): ?>
              <figure>
                <img src="../<?= htmlspecialchars($p['file_path']) ?>" alt="">
                <figcaption><?= date('Y/m/d', strtotime($p['created_at'])) ?></figcaption>
              </figure>
            <?php endforeach; ?>
          </div>
        </section>
      </section>

      <section class="carte-layout bottom">
        <section class="carte-panel">
          <div class="carte-panel-head">
            <p class="eyebrow">CARTE HISTORY</p>
            <h2>カルテ履歴</h2>
          </div>
          <div class="timeline-list">
            <?php if (!$records): ?><p class="muted-text">カルテ履歴はまだありません。</p><?php endif; ?>
            <?php foreach($records as $rec): ?>
              <article class="timeline-item">
                <strong><?= date('Y/m/d H:i', strtotime($rec['created_at'])) ?></strong>
                <span>Color：<?= htmlspecialchars($rec['color'] ?: '-') ?></span>
                <span>Material：<?= htmlspecialchars($rec['materials'] ?: '-') ?></span>
                <p><?= nl2br(htmlspecialchars($rec['design_note'] ?: $rec['nail_condition'] ?: '-')) ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="carte-panel">
          <div class="carte-panel-head">
            <p class="eyebrow">VISIT HISTORY</p>
            <h2>来店履歴</h2>
          </div>
          <div class="timeline-list">
            <?php if (!$reservations): ?><p class="muted-text">来店履歴はありません。</p><?php endif; ?>
            <?php foreach($reservations as $r): ?>
              <article class="timeline-item">
                <strong><?= date('Y/m/d H:i', strtotime($r['start_datetime'])) ?></strong>
                <span><?= htmlspecialchars($r['staff_name'] ?? '-') ?> / <?= htmlspecialchars($r['menu_name'] ?? '-') ?></span>
                <p>¥<?= number_format((int)($r['price'] ?? 0)) ?> / <?= htmlspecialchars($r['status']) ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
