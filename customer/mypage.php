<?php
require_once __DIR__ . '/../config/db.php';

$lineUserId = $_GET['line_user_id'] ?? '';
$phone = $_GET['phone'] ?? '';

$customer = null;
$nextReservation = null;
$history = [];
$error = '';

try {
  $pdo = db();

  if ($lineUserId !== '') {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE line_user_id = ? LIMIT 1");
    $stmt->execute([$lineUserId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
  }

  if (!$customer && $phone !== '') {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE phone = ? LIMIT 1");
    $stmt->execute([$phone]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
  }

  if ($customer) {
    $stmt = $pdo->prepare("
      SELECT r.*, s.name AS staff_name, m.name AS menu_name, m.price
      FROM reservations r
      LEFT JOIN staffs s ON s.id = r.staff_id
      LEFT JOIN menus m ON m.id = r.menu_id
      WHERE r.customer_id = ?
        AND r.start_datetime >= NOW()
        AND r.status IN ('reserved','confirmed')
      ORDER BY r.start_datetime ASC
      LIMIT 1
    ");
    $stmt->execute([$customer['id']]);
    $nextReservation = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
      SELECT r.*, s.name AS staff_name, m.name AS menu_name, m.price
      FROM reservations r
      LEFT JOIN staffs s ON s.id = r.staff_id
      LEFT JOIN menus m ON m.id = r.menu_id
      WHERE r.customer_id = ?
      ORDER BY r.start_datetime DESC
      LIMIT 20
    ");
    $stmt->execute([$customer['id']]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>My Page | ON;ME</title>
  <link rel="stylesheet" href="../assets/css/customer.css?v=9">
  <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
</head>
<body>
  <main class="reserve-app">
    <section class="hero compact">
      <p class="eyebrow">MY PAGE</p>
      <h1>ON;ME<br><span>Member.</span></h1>
      <p class="lead">予約確認・来店履歴・次回予約をこちらで確認できます。</p>
    </section>

    <section class="step-card">
      <div class="step-head">
        <div>
          <span>01</span>
          <h2>Profile</h2>
        </div>
        <p>会員情報</p>
      </div>

      <?php if ($error): ?>
        <p class="empty"><?= htmlspecialchars($error) ?></p>
      <?php elseif (!$customer): ?>
        <div class="mypage-search">
          <p class="empty">LINE連携中です。表示されない場合は電話番号で検索できます。</p>
          <form id="phoneSearchForm" class="reserve-form">
            <label>電話番号<input type="tel" id="phoneSearch" placeholder="09012345678"></label>
            <button type="submit" class="reserve-submit">予約履歴を確認する</button>
          </form>
        </div>
      <?php else: ?>
        <div class="profile-card">
          <strong><?= htmlspecialchars($customer['name']) ?></strong>
          <span><?= htmlspecialchars($customer['phone'] ?? '') ?></span>
        </div>
      <?php endif; ?>
    </section>

    <section class="step-card">
      <div class="step-head">
        <div>
          <span>02</span>
          <h2>Next</h2>
        </div>
        <p>次回予約</p>
      </div>

      <?php if (!$customer): ?>
        <p class="empty">会員情報を取得すると表示されます。</p>
      <?php elseif (!$nextReservation): ?>
        <p class="empty">次回予約はありません。</p>
        <a class="complete-button" href="./index.php">予約する</a>
      <?php else: ?>
        <article class="reservation-card">
          <small>NEXT RESERVATION</small>
          <strong><?= date('Y/m/d H:i', strtotime($nextReservation['start_datetime'])) ?></strong>
          <span><?= htmlspecialchars($nextReservation['staff_name']) ?> / <?= htmlspecialchars($nextReservation['menu_name']) ?></span>
          <div class="reservation-actions">
            <a href="./index.php" class="complete-button">変更する</a>
            <button type="button" class="ghost-button" data-cancel-id="<?= (int)$nextReservation['id'] ?>">キャンセル</button>
          </div>
        </article>
      <?php endif; ?>
    </section>

    <section class="step-card">
      <div class="step-head">
        <div>
          <span>03</span>
          <h2>History</h2>
        </div>
        <p>来店履歴</p>
      </div>

      <div class="history-list-customer">
        <?php if (!$history): ?>
          <p class="empty">来店履歴はまだありません。</p>
        <?php endif; ?>

        <?php foreach($history as $r): ?>
          <article class="history-item-customer">
            <strong><?= date('Y/m/d', strtotime($r['start_datetime'])) ?></strong>
            <span><?= htmlspecialchars($r['staff_name'] ?? '-') ?> / <?= htmlspecialchars($r['menu_name'] ?? '-') ?></span>
            <em><?= htmlspecialchars($r['status']) ?></em>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <nav class="bottom-nav">
    <a href="./index.php">Reserve</a>
    <a href="./mypage.php" class="active">My Page</a>
    <a href="../admin/">Admin</a>
  </nav>

  <div class="toast" id="toast"></div>
  <script src="../assets/js/customer-mypage.js?v=9"></script>
</body>
</html>
