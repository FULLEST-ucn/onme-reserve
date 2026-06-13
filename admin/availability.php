<?php
require dirname(__DIR__) . '/config/bootstrap.php';
require_admin();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $items = storage_json('availability', []);
    $items[] = [
        'id'=>'a_' . date('YmdHis') . '_' . bin2hex(random_bytes(2)),
        'staff'=>$_POST['staff'] ?? 'kiho',
        'date'=>$_POST['date'] ?? date('Y-m-d'),
        'start'=>$_POST['start'] ?? '10:00',
        'end'=>$_POST['end'] ?? '18:00',
    ];
    save_json('availability',$items);
    redirect('/admin/availability.php');
}
$items=storage_json('availability', []);
?>
<!doctype html><html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>受付可能時間</title><link rel="stylesheet" href="/assets/css/admin.css"></head>
<body>
<header class="admin-header"><div><p>Availability</p><h1>受付可能時間</h1></div><a href="/admin/">戻る</a></header>
<main class="admin-wrap">
<form method="post" class="form-card">
<select name="staff"><option value="kiho">KIHO</option><option value="yuina">YUINA</option></select>
<input type="date" name="date" value="<?=date('Y-m-d')?>">
<input type="time" name="start" value="13:00">
<input type="time" name="end" value="17:00">
<button>追加する</button>
</form>
<section class="board">
<?php foreach(array_reverse($items) as $a): ?><article class="row"><strong><?=h(strtoupper($a['staff']))?></strong><span><?=h($a['date'])?> <?=h($a['start'])?>〜<?=h($a['end'])?></span></article><?php endforeach; ?>
</section>
</main></body></html>
