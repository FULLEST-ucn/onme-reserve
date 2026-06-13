<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();

$segment = $_GET['segment'] ?? 'all';
$customers = [];
$message = '';
$saved = false;

try {
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $segment = $_POST['segment'] ?? 'all';
    $message = trim($_POST['message'] ?? '');
    $count = (int)($_POST['target_count'] ?? 0);

    $stmt = $pdo->prepare("INSERT INTO line_campaigns (title, segment, message, target_count, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$title, $segment, $message, $count]);
    $saved = true;
  }

  $where = "1=1";
  if ($segment === 'line') $where = "c.line_user_id IS NOT NULL AND c.line_user_id <> ''";
  if ($segment === 'lost90') $where = "latest.last_visit <= DATE_SUB(NOW(), INTERVAL 90 DAY)";
  if ($segment === 'repeat') $where = "latest.visit_count >= 2";

  $stmt = $pdo->query("
    SELECT c.*, latest.last_visit, latest.visit_count
    FROM customers c
    LEFT JOIN (
      SELECT customer_id, MAX(start_datetime) AS last_visit, COUNT(*) AS visit_count
      FROM reservations
      GROUP BY customer_id
    ) latest ON latest.customer_id = c.id
    WHERE {$where}
    ORDER BY latest.last_visit DESC
    LIMIT 300
  ");
  $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) {
  $error = $e->getMessage();
}

$template = "いつもON;ME NAILをご利用いただきありがとうございます✨\n\n新しいデザインをご用意しております。\nご予約お待ちしております💅";
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LINE CRM | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=20">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=20">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>CRM</strong></div>
    <nav>
      <a href="./staff-dashboard.php">Dashboard</a>
      <a href="./analytics-pro.php">Analytics</a>
      <a class="active" href="./crm-pro.php">LINE CRM</a>
      <a href="./owner-dashboard.php">Owner</a>
    </nav>
  </aside>

  <main class="pro-main">
    <header class="pro-top">
      <div><p class="eyebrow">LINE SEGMENT MESSAGE</p><h1>LINE CRM</h1></div>
      <form class="analytics-filter" method="get">
        <select name="segment">
          <option value="all" <?= $segment==='all'?'selected':'' ?>>全顧客</option>
          <option value="line" <?= $segment==='line'?'selected':'' ?>>LINE連携済</option>
          <option value="repeat" <?= $segment==='repeat'?'selected':'' ?>>リピーター</option>
          <option value="lost90" <?= $segment==='lost90'?'selected':'' ?>>90日以上未来店</option>
        </select>
        <button>抽出</button>
      </form>
    </header>

    <?php if (!empty($saved)): ?><section class="os-panel success">キャンペーン下書きを保存しました。</section><?php endif; ?>

    <section class="os-two">
      <section class="os-panel">
        <p class="eyebrow">MESSAGE</p>
        <h2>配信作成</h2>
        <form method="post" class="os-form">
          <input type="hidden" name="segment" value="<?= htmlspecialchars($segment) ?>">
          <input type="hidden" name="target_count" value="<?= count($customers) ?>">
          <label>タイトル<input name="title" value="<?= htmlspecialchars(date('Y/m/d').' 配信') ?>"></label>
          <label>本文<textarea name="message" rows="10"><?= htmlspecialchars($template) ?></textarea></label>
          <button>下書き保存</button>
        </form>
      </section>

      <section class="os-panel">
        <p class="eyebrow">TARGET</p>
        <h2>対象 <?= count($customers) ?>名</h2>
        <div class="os-list">
          <?php foreach($customers as $c): ?>
            <article class="os-list-item">
              <strong><?= htmlspecialchars($c['name']) ?></strong>
              <span><?= htmlspecialchars($c['phone'] ?? '-') ?> / <?= !empty($c['line_user_id']) ? 'LINE連携済' : '未連携' ?></span>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    </section>
  </main>
</body>
</html>
