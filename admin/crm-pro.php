<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
$error=''; $customers=[]; $segments=['all'=>0,'vip'=>0,'lost'=>0,'new'=>0];
try {
  $pdo=db();
  $customers=$pdo->query("
    SELECT c.*, COUNT(r.id) visit_count, MAX(r.start_datetime) last_visit, COALESCE(SUM(m.price),0) ltv
    FROM customers c
    LEFT JOIN reservations r ON r.customer_id=c.id
    LEFT JOIN menus m ON m.id=r.menu_id
    GROUP BY c.id
    ORDER BY c.id DESC
    LIMIT 100
  ")->fetchAll(PDO::FETCH_ASSOC);
  $segments['all']=count($customers);
  foreach($customers as $c){
    if((int)($c['ltv']??0)>=30000) $segments['vip']++;
    if(empty($c['last_visit']) || strtotime($c['last_visit']) <= strtotime('-90 days')) $segments['lost']++;
    if((int)($c['visit_count']??0)<=1) $segments['new']++;
  }
} catch(Throwable $e){ $error=$e->getMessage(); }
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LINE | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=79">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=79">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=79">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=79">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=7">
  <link rel="stylesheet" href="../assets/css/crm-unified.css?v=1">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="pro-main">
    <header class="pro-top">
      <div><p class="eyebrow">CRM / LINE</p><h1>LINE</h1><p class="crm-lead">顧客セグメント・配信対象・来店状況を管理できます。</p></div>
      <div class="pro-actions"><a class="pro-button primary" href="./line-automation.php">LINE Automation</a></div>
    </header>
    <?php if($error): ?><section class="os-panel"><?= htmlspecialchars($error) ?></section><?php endif; ?>
    <section class="crm-kpi">
      <article><span>全顧客</span><strong><?= number_format($segments['all']) ?></strong></article>
      <article><span>VIP</span><strong><?= number_format($segments['vip']) ?></strong></article>
      <article><span>休眠</span><strong><?= number_format($segments['lost']) ?></strong></article>
      <article><span>新規</span><strong><?= number_format($segments['new']) ?></strong></article>
    </section>
    <section class="crm-layout">
      <article class="os-panel">
        <p class="eyebrow">SEGMENT</p><h2>配信セグメント</h2>
        <div class="crm-segment-list">
          <a href="./line-segment-pro.php?segment=all">全員配信</a>
          <a href="./line-segment-pro.php?segment=vip">VIP顧客</a>
          <a href="./line-segment-pro.php?segment=lost">90日未来店</a>
          <a href="./line-segment-pro.php?segment=new">新規顧客</a>
        </div>
      </article>
      <article class="os-panel">
        <p class="eyebrow">CUSTOMERS</p><h2>顧客一覧</h2>
        <div class="crm-customer-list">
          <?php if(!$customers): ?><p class="muted-text">顧客データがありません。</p><?php endif; ?>
          <?php foreach($customers as $c): ?>
            <article class="crm-customer-card">
              <div><strong><?= htmlspecialchars($c['name'] ?? '-') ?></strong><span>来店 <?= (int)($c['visit_count'] ?? 0) ?>回 / LTV ¥<?= number_format((int)($c['ltv'] ?? 0)) ?></span></div>
              <a href="./customer-360.php?id=<?= (int)$c['id'] ?>">詳細</a>
            </article>
          <?php endforeach; ?>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
