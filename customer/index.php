<?php
require dirname(__DIR__) . '/config/bootstrap.php';
$config = app_config();
$menus = storage_json('menus', [
    ['id'=>'onecolor','name'=>'ワンカラー','price'=>6500,'minutes'=>90],
    ['id'=>'gradation','name'=>'グラデーション','price'=>7000,'minutes'=>90],
    ['id'=>'french','name'=>'フレンチ','price'=>8000,'minutes'=>120],
    ['id'=>'magnet','name'=>'マグネットネイル','price'=>7500,'minutes'=>90],
    ['id'=>'simple','name'=>'定額シンプル','price'=>8000,'minutes'=>120],
    ['id'=>'design','name'=>'定額デザイン','price'=>9500,'minutes'=>120],
    ['id'=>'bring','name'=>'持ち込みデザイン','price'=>11000,'minutes'=>150],
    ['id'=>'long10','name'=>'長さ出し10本','price'=>14500,'minutes'=>180],
    ['id'=>'foot','name'=>'フットワンカラー','price'=>7500,'minutes'=>90],
    ['id'=>'off','name'=>'オフのみ','price'=>4000,'minutes'=>60],
]);
$staffs = [
    ['id'=>'kiho','name'=>'KIHO'],
    ['id'=>'yuina','name'=>'YUINA'],
];
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>ON;ME RESERVATION</title>
<link rel="stylesheet" href="/assets/css/customer.css">
<script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
</head>
<body>
<div class="noise"></div>
<main class="app">
    <header class="hero">
        <p>LINE RESERVATION</p>
        <h1>ON;ME NAIL</h1>
        <span>Beauty begins at your fingertips.</span>
    </header>

    <section class="panel step active" data-step="1">
        <div class="step-head"><h2>Menu</h2><b>01</b></div>
        <p class="lead">ご希望メニューをお選びください。</p>
        <div class="list">
        <?php foreach ($menus as $m): ?>
            <button class="choice menu-choice" data-id="<?=h($m['id'])?>" data-name="<?=h($m['name'])?>" data-minutes="<?=h((string)$m['minutes'])?>" data-price="<?=h((string)$m['price'])?>">
                <strong><?=h($m['name'])?></strong>
                <span>¥<?=number_format((int)$m['price'])?> / <?=h((string)$m['minutes'])?>分</span>
            </button>
        <?php endforeach; ?>
        </div>
    </section>

    <section class="panel step" data-step="2">
        <div class="step-head"><h2>Staff</h2><b>02</b></div>
        <p class="lead">担当スタッフをお選びください。</p>
        <div class="list">
        <?php foreach ($staffs as $s): ?>
            <button class="choice staff-choice" data-id="<?=h($s['id'])?>" data-name="<?=h($s['name'])?>">
                <strong><?=h($s['name'])?></strong>
                <span>指名する</span>
            </button>
        <?php endforeach; ?>
        </div>
        <button class="back" data-back="1">戻る</button>
    </section>

    <section class="panel step" data-step="3">
        <div class="step-head"><h2>Date</h2><b>03</b></div>
        <p class="lead">選択メニューの施術時間に合わせて、予約可能な開始時間のみ表示します。</p>
        <div id="slots" class="slots"></div>
        <button class="back" data-back="2">戻る</button>
    </section>

    <section class="panel step" data-step="4">
        <div class="step-head"><h2>Confirm</h2><b>04</b></div>
        <div class="confirm" id="confirmBox"></div>
        <input id="customerName" placeholder="お名前" autocomplete="name">
        <input id="customerPhone" placeholder="電話番号" autocomplete="tel">
        <button id="submitReserve" class="primary">予約を確定する</button>
        <button class="back" data-back="3">戻る</button>
    </section>
</main>
<script>
window.ONME_LIFF_ID = <?=json_encode($config['liff_id'])?>;
</script>
<script src="/assets/js/customer.js"></script>
</body>
</html>
