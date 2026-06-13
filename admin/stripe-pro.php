<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();

$payments = [];
try {
  $pdo = db();
  $payments = $pdo->query("
    SELECT p.*, c.name AS customer_name
    FROM stripe_payments p
    LEFT JOIN customers c ON c.id = p.customer_id
    ORDER BY p.created_at DESC
    LIMIT 100
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Stripe Pro | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=25">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=25">
  <link rel="stylesheet" href="../assets/css/suite-pro.css?v=25">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>Stripe</strong></div>
    <nav>
      <a href="./owner-dashboard.php">Owner</a>
      <a class="active" href="./stripe-pro.php">Stripe</a>
      <a href="./settings-pro.php">Settings</a>
    </nav>
  </aside>
  <main class="pro-main">
    <header class="pro-top">
      <div><p class="eyebrow">DEPOSIT PAYMENT</p><h1>Stripe Pro</h1></div>
      <div class="pro-actions"><a class="pro-button primary" href="./settings-pro.php">APIキー設定</a></div>
    </header>

    <section class="analytics-kpi">
      <article><span>支払件数</span><strong><?= count($payments) ?></strong></article>
      <article><span>入金済</span><strong><?= count(array_filter($payments, fn($p)=>$p['status']==='paid')) ?></strong></article>
      <article><span>未入金</span><strong><?= count(array_filter($payments, fn($p)=>$p['status']!=='paid')) ?></strong></article>
      <article><span>方式</span><strong>Stripe</strong></article>
    </section>

    <section class="os-panel">
      <p class="eyebrow">PAYMENT LINKS</p>
      <h2>決済履歴</h2>
      <div class="os-list">
        <?php if(!$payments): ?><p class="muted-text">決済データはまだありません。</p><?php endif; ?>
        <?php foreach($payments as $p): ?>
        <article class="os-list-item">
          <strong>¥<?= number_format((int)$p['amount']) ?> / <?= htmlspecialchars($p['status']) ?></strong>
          <span><?= htmlspecialchars($p['customer_name'] ?? '-') ?> / <?= date('Y/m/d H:i', strtotime($p['created_at'])) ?></span>
        </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</body>
</html>
