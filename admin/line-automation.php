<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();

$logs = [];
try {
  $pdo = db();
  $logs = $pdo->query("
    SELECT * FROM notification_logs
    ORDER BY created_at DESC
    LIMIT 100
  ")->fetchAll(PDO::FETCH_ASSOC);

  $items = $pdo->query("
    SELECT * FROM rich_menu_items
    WHERE is_active=1
    ORDER BY sort_order ASC
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) {
  $error = $e->getMessage();
  $items = [];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LINE Automation | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=25">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=25">
  <link rel="stylesheet" href="../assets/css/suite-pro.css?v=25">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>LINE</strong></div>
    <nav>
      <a href="./owner-dashboard.php">Owner</a>
      <a class="active" href="./line-automation.php">LINE Automation</a>
      <a href="./crm-pro.php">LINE CRM</a>
      <a href="./settings-pro.php">Settings</a>
    </nav>
  </aside>
  <main class="pro-main">
    <header class="pro-top"><div><p class="eyebrow">MESSAGING API</p><h1>LINE Automation</h1></div></header>
    <?php if(!empty($error)): ?><section class="os-panel"><?= htmlspecialchars($error) ?></section><?php endif; ?>

    <section class="suite-grid">
      <article class="os-panel">
        <p class="eyebrow">RICH MENU</p>
        <h2>リッチメニュー構成</h2>
        <div class="os-list">
          <?php foreach($items as $item): ?>
          <article class="os-list-item">
            <strong><?= htmlspecialchars($item['label']) ?></strong>
            <span><?= htmlspecialchars($item['url']) ?></span>
          </article>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="os-panel">
        <p class="eyebrow">AUTO MESSAGE</p>
        <h2>自動通知</h2>
        <div class="suite-check-list">
          <span>予約完了通知</span>
          <span>前日リマインド</span>
          <span>キャンセル通知</span>
          <span>90日未来店配信</span>
        </div>
      </article>
    </section>

    <section class="os-panel">
      <p class="eyebrow">LOGS</p>
      <h2>通知ログ</h2>
      <div class="os-list">
        <?php if(!$logs): ?><p class="muted-text">通知ログはまだありません。</p><?php endif; ?>
        <?php foreach($logs as $log): ?>
        <article class="os-list-item">
          <strong><?= htmlspecialchars($log['event_type']) ?> / <?= htmlspecialchars($log['status']) ?></strong>
          <span><?= htmlspecialchars($log['recipient'] ?? '-') ?> / <?= date('Y/m/d H:i', strtotime($log['created_at'])) ?></span>
        </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</body>
</html>
