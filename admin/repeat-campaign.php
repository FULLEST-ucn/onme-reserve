<?php
require_once __DIR__ . '/../config/db.php';

$days = (int)($_GET['days'] ?? 28);
$rows = [];

try {
  $pdo = db();
  $stmt = $pdo->prepare("
    SELECT c.id, c.name, c.phone, c.line_user_id,
           MAX(r.start_datetime) AS last_visit,
           COUNT(r.id) AS visit_count
    FROM customers c
    JOIN reservations r ON r.customer_id = c.id
    WHERE r.status IN ('completed','confirmed','reserved')
    GROUP BY c.id
    HAVING last_visit <= DATE_SUB(NOW(), INTERVAL ? DAY)
    ORDER BY last_visit DESC
    LIMIT 200
  ");
  $stmt->execute([$days]);
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
  <title>リピート配信 | ON;ME</title>
  <link rel="stylesheet" href="../assets/css/admin.css?v=11">
</head>
<body class="admin-body">
<header class="admin-header">
  <div><p class="eyebrow">ON;ME OS</p><h1>Repeat</h1></div>
  <nav class="admin-nav">
    <a href="./index.php">Dashboard</a>
    <a href="./customers.php">顧客</a>
    <a href="./reminders.php">リマインド</a>
    <a class="active" href="./repeat-campaign.php">リピート配信</a>
  </nav>
</header>

<main class="admin-shell">
  <?php if (!empty($error)): ?><section class="panel notice"><?= htmlspecialchars($error) ?></section><?php endif; ?>

  <section class="panel">
    <div class="panel-head">
      <div>
        <p class="eyebrow">REPEAT MARKETING</p>
        <h2><?= $days ?>日以上未来店のお客様</h2>
      </div>
      <form method="get" class="inline-form">
        <input type="number" name="days" value="<?= $days ?>" min="1">
        <button class="admin-link-button" type="submit">表示</button>
      </form>
    </div>

    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>お客様</th>
            <th>電話</th>
            <th>LINE</th>
            <th>最終来店</th>
            <th>来店回数</th>
            <th>配信文面</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="6" class="empty-cell">対象顧客はありません。</td></tr><?php endif; ?>
          <?php foreach($rows as $c): ?>
            <?php
              $message = "{$c['name']}様\n\nそろそろ付け替えの時期です✨\n前回のご来店から{$days}日以上経過しております。\n\nご予約はこちらからお願いいたします💅";
            ?>
            <tr>
              <td><?= htmlspecialchars($c['name']) ?></td>
              <td><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
              <td><?= !empty($c['line_user_id']) ? '連携済' : '未連携' ?></td>
              <td><?= $c['last_visit'] ? date('Y/m/d', strtotime($c['last_visit'])) : '-' ?></td>
              <td><?= (int)$c['visit_count'] ?>回</td>
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
