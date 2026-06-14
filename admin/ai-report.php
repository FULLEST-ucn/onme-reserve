<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();
$today=date('Y-m-d');
try{
  $pdo=db();
  $stmt=$pdo->prepare("SELECT COUNT(*) c, COALESCE(SUM(m.price),0) sales FROM reservations r LEFT JOIN menus m ON m.id=r.menu_id WHERE DATE(r.start_datetime)=?");
  $stmt->execute([$today]); $row=$stmt->fetch(PDO::FETCH_ASSOC);
  $body="本日の予約は".(int)$row['c']."件、売上見込みは¥".number_format((int)$row['sales'])."です。\n\nおすすめ施策：\n・空き時間があるスタッフはInstagramストーリーで当日予約を訴求\n・90日未来店のお客様へLINE配信\n・人気メニューを固定投稿化";
}catch(Throwable $e){$body=$e->getMessage();}
?>
<!doctype html><html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>AI Report</title>
<link rel="stylesheet" href="../assets/css/admin-pro.css?v=40"><link rel="stylesheet" href="../assets/css/os-pro.css?v=40"><link rel="stylesheet" href="../assets/css/suite40.css?v=40"></head>
<body class="pro-body"><aside class="pro-sidebar"><div class="brand"><small>ON;ME OS</small><strong>AI</strong></div><nav><a href="./dashboard-v2.php">Dashboard</a><a class="active" href="./ai-report.php">AI Report</a><a href="./ai-concierge.php">AI Concierge</a></nav></aside>
<main class="pro-main"><header class="pro-top"><div><p class="eyebrow">DAILY AI INSIGHT</p><h1>AI Report</h1></div></header><section class="os-panel"><pre class="ai-result"><?= htmlspecialchars($body) ?></pre></section></main></body></html>
