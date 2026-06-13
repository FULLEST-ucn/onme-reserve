<?php
require dirname(__DIR__) . '/config/bootstrap.php';
require_admin();
$staff = current_staff();
$reservations = storage_json('reservations', []);
$availability = storage_json('availability', []);
?>
<!doctype html><html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ON;ME Admin</title><link rel="stylesheet" href="/assets/css/admin.css"></head>
<body>
<header class="admin-header"><div><p>ON;ME OS</p><h1><?=h($staff['name'])?> Dashboard</h1></div><a href="/admin/logout.php">ログアウト</a></header>
<main class="admin-wrap">
<section class="stats">
 <div><span>本日予約</span><b><?=count($reservations)?></b></div>
 <div><span>受付可能時間</span><b><?=count($availability)?></b></div>
 <div><span>スタッフ</span><b>2</b></div>
</section>
<section class="board">
 <div class="board-head"><h2>受付可能時間</h2><a class="btn" href="/admin/availability.php">追加・編集</a></div>
 <?php foreach($availability as $a): ?>
   <article class="row"><strong><?=h(strtoupper($a['staff']))?></strong><span><?=h($a['date'])?> <?=h($a['start'])?>〜<?=h($a['end'])?></span></article>
 <?php endforeach; ?>
</section>
<section class="board">
 <div class="board-head"><h2>予約一覧</h2></div>
 <?php foreach(array_reverse($reservations) as $r): ?>
   <article class="row"><strong><?=h($r['name'] ?? '')?></strong><span><?=h($r['slot']['date'] ?? '')?> <?=h($r['slot']['start'] ?? '')?>〜<?=h($r['slot']['end'] ?? '')?> / <?=h($r['menu']['name'] ?? '')?></span></article>
 <?php endforeach; ?>
</section>
</main>
</body></html>
