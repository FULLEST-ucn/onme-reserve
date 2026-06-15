<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();
try {
  $pdo=db();
  $sales=(int)$pdo->query("SELECT COALESCE(SUM(m.price),0) FROM reservations r LEFT JOIN menus m ON m.id=r.menu_id WHERE DATE(r.start_datetime)=CURDATE()")->fetchColumn();
  $count=(int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE DATE(start_datetime)=CURDATE()")->fetchColumn();
  $lost=(int)$pdo->query("SELECT COUNT(*) FROM customers c LEFT JOIN (SELECT customer_id,MAX(start_datetime) last_visit FROM reservations GROUP BY customer_id) x ON x.customer_id=c.id WHERE x.last_visit <= DATE_SUB(NOW(),INTERVAL 90 DAY)")->fetchColumn();
} catch(Throwable $e) { $sales=0;$count=0;$lost=0;$error=$e->getMessage(); }
$reply="本日の売上見込みは¥".number_format($sales)."、予約は".$count."件です。\n\n改善提案：\n・90日未来店 ".$lost."名へLINE配信\n・予約が少ない曜日に限定クーポン\n・上位メニューをInstagram投稿に活用\n・次回来店周期30日前後の顧客へ自動リマインド";
?>
<!doctype html><html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>AI Executive</title><link rel="stylesheet" href="../assets/css/admin-pro.css?v=60">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=60">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=60"></head>
<body class="pro-body"><aside class="pro-sidebar"><div class="brand"><small>ON;ME OS</small><strong>AI Exec</strong></div><nav><a href="./owner-bi.php">Owner BI</a><a class="active" href="./ai-executive.php">AI Executive</a><a href="./ai-concierge.php">AI Concierge</a></nav></aside>
<main class="pro-main"><header class="pro-top"><div><p class="eyebrow">AI MANAGEMENT ASSISTANT</p><h1>AI Executive</h1></div></header><section class="s60-grid"><article class="s60-panel"><p class="eyebrow">ASK AI</p><h2>経営AI</h2><div class="s60-chat"><div class="s60-bubble">今月の改善点は？</div><pre class="s60-bubble ai"><?= htmlspecialchars($reply) ?></pre></div></article><article class="s60-panel"><p class="eyebrow">AUTO ACTION</p><h2>推奨アクション</h2><div class="s60-list"><article class="s60-item"><strong>LINE配信</strong><span>木曜18時に配信推奨</span></article><article class="s60-item"><strong>クーポン</strong><span>平日限定で稼働率改善</span></article><article class="s60-item"><strong>Instagram</strong><span>人気メニューを投稿化</span></article></div></article></section></main></body></html>
