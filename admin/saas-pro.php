<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();
require_once __DIR__ . '/../config/store.php';
$store = current_store();
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SaaS Pro | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=25">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=25">
  <link rel="stylesheet" href="../assets/css/suite-pro.css?v=25">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>SaaS</strong></div>
    <nav>
      <a href="./owner-dashboard.php">Owner</a>
      <a class="active" href="./saas-pro.php">SaaS Pro</a>
      <a href="./settings-pro.php">Settings</a>
    </nav>
  </aside>
  <main class="pro-main">
    <header class="pro-top"><div><p class="eyebrow">BEAUTY SALON OPERATING SYSTEM</p><h1>SaaS Pro</h1></div></header>
    <section class="suite-hero">
      <article><span>店舗</span><strong><?= htmlspecialchars($store['name'] ?? 'ON;ME') ?></strong></article>
      <article><span>Plan</span><strong>Pro</strong></article>
      <article><span>Status</span><strong>Ready</strong></article>
    </section>

    <section class="suite-grid three">
      <a class="suite-card" href="./calendar-pro.php"><small>01</small><strong>Calendar</strong><span>予約・受付枠・スタッフ別管理</span></a>
      <a class="suite-card" href="./carte-pro.php"><small>02</small><strong>Carte</strong><span>写真付きカルテ・施術履歴</span></a>
      <a class="suite-card" href="./line-automation.php"><small>03</small><strong>LINE</strong><span>通知・CRM・リマインド</span></a>
      <a class="suite-card" href="./pos-pro.php"><small>04</small><strong>POS</strong><span>会計・支払方法・日報</span></a>
      <a class="suite-card" href="./analytics-pro.php"><small>05</small><strong>Analytics</strong><span>売上・リピート・客単価</span></a>
      <a class="suite-card" href="./ai-concierge.php"><small>06</small><strong>AI</strong><span>空き時間提案・カルテ要約</span></a>
    </section>
  </main>
</body>
</html>
