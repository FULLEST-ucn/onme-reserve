<?php
require_once __DIR__ . '/../config/db.php';

$targetDate = $_GET['date'] ?? date('Y-m-d', strtotime('+1 day'));
$rows = [];
$error = '';
$sent = false;

try {
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? $targetDate;
    header('Location: ./reminders.php?date=' . urlencode($date) . '&sent=1');
    exit;
  }

  $sent = isset($_GET['sent']);

  $stmt = $pdo->prepare("
    SELECT r.id, r.start_datetime, r.end_datetime, r.status,
           c.name AS customer_name, c.phone, c.line_user_id,
           s.name AS staff_name,
           m.name AS menu_name
    FROM reservations r
    LEFT JOIN customers c ON c.id = r.customer_id
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) = ?
      AND r.status IN ('reserved','confirmed')
    ORDER BY r.start_datetime ASC
  ");
  $stmt->execute([$targetDate]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>リマインド | ON;ME</title>
  <link rel="stylesheet" href="../assets/css/admin.css?v=11">
</head>
<body class="admin-body">
<header class="admin-header">
  <div><p class="eyebrow">ON;ME OS</p><h1>Reminders</h1></div>
  <nav class="admin-nav">
    <a href="./index.php">Dashboard</a>
    <a href="./calendar.php">Calendar</a>
    <a href="./reservations.php">予約</a>
    <a href="./line-settings.php">LINE設定</a>
    <a class="active" href="./reminders.php">リマインド</a>
  </nav>
</header>

<main class="admin-shell">
  <?php if ($sent): ?>
    <section class="panel notice">送信候補を確認しました。実送信は次Sprintで自動化します。</section>
  <?php endif; ?>
  <?php if ($error): ?>
    <section class="panel notice"><?= htmlspecialchars($error) ?></section>
  <?php endif; ?>

  <section class="panel">
    <div class="panel-head">
      <div>
        <p class="eyebrow">BEFORE VISIT</p>
        <h2>前日リマインド候補</h2>
      </div>
      <form method="get" class="inline-form">
        <input type="date" name="date" value="<?= htmlspecialchars($targetDate) ?>">
        <button class="admin-link-button" type="submit">表示</button>
      </form>
    </div>

    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>日時</th>
            <th>お客様</th>
            <th>LINE</th>
            <th>担当</th>
            <th>メニュー</th>
            <th>文面</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="6" class="empty-cell">対象予約はありません。</td></tr>
          <?php endif; ?>
          <?php foreach($rows as $r): ?>
            <?php
              $message = "明日のご予約のお知らせです✨\n\n"
                . "【日時】" . date('Y/m/d H:i', strtotime($r['start_datetime'])) . "〜" . date('H:i', strtotime($r['end_datetime'])) . "\n"
                . "【担当】" . ($r['staff_name'] ?? '-') . "\n"
                . "【メニュー】" . ($r['menu_name'] ?? '-') . "\n\n"
                . "ご来店をお待ちしております。";
            ?>
            <tr>
              <td><?= date('Y/m/d H:i', strtotime($r['start_datetime'])) ?>〜<?= date('H:i', strtotime($r['end_datetime'])) ?></td>
              <td><?= htmlspecialchars($r['customer_name'] ?? '-') ?></td>
              <td><?= !empty($r['line_user_id']) ? '連携済' : '未連携' ?></td>
              <td><?= htmlspecialchars($r['staff_name'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['menu_name'] ?? '-') ?></td>
              <td><textarea readonly rows="5"><?= htmlspecialchars($message) ?></textarea></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
</body>
</html>
