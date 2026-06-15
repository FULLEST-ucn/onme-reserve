<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();

$month = $_GET['month'] ?? date('Y-m');
$start = $month . '-01';
$end = date('Y-m-t', strtotime($start));
$kpi = ['sales'=>0,'count'=>0,'avg'=>0,'customers'=>0,'repeat'=>0,'ltv'=>0];
$daily=[]; $menus=[];

try {
  $pdo = db();
  $stmt=$pdo->prepare("SELECT COUNT(r.id)c, COALESCE(SUM(m.price),0)sales, COUNT(DISTINCT r.customer_id) customers FROM reservations r LEFT JOIN menus m ON m.id=r.menu_id WHERE DATE(r.start_datetime) BETWEEN ? AND ? AND r.status IN ('reserved','confirmed','completed')");
  $stmt->execute([$start,$end]); $row=$stmt->fetch(PDO::FETCH_ASSOC);
  $kpi['sales']=(int)$row['sales']; $kpi['count']=(int)$row['c']; $kpi['customers']=(int)$row['customers']; $kpi['avg']=$kpi['count']?floor($kpi['sales']/$kpi['count']):0; $kpi['ltv']=$kpi['customers']?floor($kpi['sales']/$kpi['customers']):0;

  $stmt=$pdo->prepare("SELECT customer_id, COUNT(*) c FROM reservations WHERE DATE(start_datetime) BETWEEN ? AND ? GROUP BY customer_id"); $stmt->execute([$start,$end]);
  $total=0;$repeat=0; foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r){ $total++; if((int)$r['c']>=2)$repeat++; } $kpi['repeat']=$total?round($repeat/$total*100):0;

  $stmt=$pdo->prepare("SELECT DATE(r.start_datetime)d, COALESCE(SUM(m.price),0)sales FROM reservations r LEFT JOIN menus m ON m.id=r.menu_id WHERE DATE(r.start_datetime) BETWEEN ? AND ? GROUP BY DATE(r.start_datetime) ORDER BY d"); $stmt->execute([$start,$end]); $daily=$stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt=$pdo->prepare("SELECT m.name, COUNT(r.id)c, COALESCE(SUM(m.price),0)sales FROM reservations r LEFT JOIN menus m ON m.id=r.menu_id WHERE DATE(r.start_datetime) BETWEEN ? AND ? GROUP BY m.id,m.name ORDER BY sales DESC LIMIT 5"); $stmt->execute([$start,$end]); $menus=$stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) { $error=$e->getMessage(); }
$max=1; foreach($daily as $d)$max=max($max,(int)$d['sales']);
?>
<!doctype html><html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Owner BI</title><link rel="stylesheet" href="../assets/css/admin-pro.css?v=60">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=60">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=60"></head>
<body class="pro-body"><aside class="pro-sidebar"><div class="brand"><small>ON;ME OS</small><strong>BI</strong></div><nav><a href="./dashboard-v2.php">Dashboard</a><a class="active" href="./owner-bi.php">Owner BI</a><a href="./ai-executive.php">AI Executive</a><a href="./suite60.php">Suite60</a></nav></aside>
<main class="pro-main"><header class="pro-top"><div><p class="eyebrow">OWNER BUSINESS INTELLIGENCE</p><h1>Owner BI</h1></div><form class="s60-form" method="get" style="max-width:220px"><label>月<input type="month" name="month" value="<?= htmlspecialchars($month) ?>"></label><button>表示</button></form></header>
<?php if(!empty($error)): ?><section class="s60-panel"><?= htmlspecialchars($error) ?></section><?php endif; ?>
<section class="s60-hero"><article class="s60-card"><span>月商</span><strong>¥<?= number_format($kpi['sales']) ?></strong></article><article class="s60-card"><span>客単価</span><strong>¥<?= number_format($kpi['avg']) ?></strong></article><article class="s60-card"><span>リピート率</span><strong><?= $kpi['repeat'] ?>%</strong></article><article class="s60-card"><span>LTV</span><strong>¥<?= number_format($kpi['ltv']) ?></strong></article></section>
<section class="s60-grid"><article class="s60-panel"><p class="eyebrow">SALES TREND</p><h2>日別売上</h2><div class="s60-bars"><?php foreach($daily as $d): ?><div><span><?= date('m/d',strtotime($d['d'])) ?></span><i style="width:<?= max(2,((int)$d['sales']/$max)*100) ?>%"></i><b>¥<?= number_format((int)$d['sales']) ?></b></div><?php endforeach; ?></div></article>
<article class="s60-panel"><p class="eyebrow">MENU TOP</p><h2>人気メニュー</h2><div class="s60-list"><?php foreach($menus as $i=>$m): ?><article class="s60-item"><strong><?= $i+1 ?>. <?= htmlspecialchars($m['name']??'-') ?></strong><span><?= (int)$m['c'] ?>件 / ¥<?= number_format((int)$m['sales']) ?></span></article><?php endforeach; ?></div></article></section></main></body></html>
