<?php
require_once __DIR__ . '/../config/db.php';

$staffs = [];
$menus = [];
$options = [];

try {
  $pdo = db();
  $staffs = $pdo->query("SELECT id, name FROM staffs WHERE is_active = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
  $menus = $pdo->query("SELECT id, name, duration_minutes, price FROM menus WHERE is_active = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
  $options = $pdo->query("SELECT id, name, duration_minutes, price FROM menu_options WHERE is_active = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $staffs = [
    ['id' => 1, 'name' => 'KIHO'],
    ['id' => 2, 'name' => 'YUINA'],
  ];
  $menus = [
    ['id' => 1, 'name' => 'ワンカラー', 'duration_minutes' => 90, 'price' => 6500],
    ['id' => 2, 'name' => 'マグネットネイル', 'duration_minutes' => 90, 'price' => 7500],
    ['id' => 3, 'name' => '持ち込みデザイン', 'duration_minutes' => 150, 'price' => 11000],
  ];
  $options = [
    ['id' => 1, 'name' => 'オフあり', 'duration_minutes' => 30, 'price' => 0],
    ['id' => 2, 'name' => '長さ出し', 'duration_minutes' => 30, 'price' => 1500],
  ];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>ON;ME LINE Reservation</title>
  <link rel="stylesheet" href="../assets/css/customer.css?v=4">
</head>
<body>
  <main class="reserve-app">
    <section class="hero">
      <p class="eyebrow">LINE RESERVATION</p>
      <h1>ON;ME NAIL</h1>
      <p class="lead">ご希望のメニュー・スタッフ・日時をお選びください。</p>
    </section>

    <section class="step-card" data-step="1">
      <div class="step-head">
        <div>
          <span>01</span>
          <h2>Menu</h2>
        </div>
        <p>施術メニュー</p>
      </div>
      <div class="select-list" id="menuList">
        <?php foreach($menus as $menu): ?>
          <button type="button" class="select-item menu-item"
            data-id="<?= (int)$menu['id'] ?>"
            data-duration="<?= (int)$menu['duration_minutes'] ?>"
            data-price="<?= (int)$menu['price'] ?>">
            <strong><?= htmlspecialchars($menu['name']) ?></strong>
            <span>¥<?= number_format((int)$menu['price']) ?> / <?= (int)$menu['duration_minutes'] ?>分</span>
          </button>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="step-card" data-step="2">
      <div class="step-head">
        <div>
          <span>02</span>
          <h2>Option</h2>
        </div>
        <p>追加メニュー</p>
      </div>
      <div class="option-grid" id="optionList">
        <?php foreach($options as $option): ?>
          <button type="button" class="option-item"
            data-id="<?= (int)$option['id'] ?>"
            data-duration="<?= (int)$option['duration_minutes'] ?>"
            data-price="<?= (int)$option['price'] ?>">
            <strong><?= htmlspecialchars($option['name']) ?></strong>
            <span>+<?= (int)$option['duration_minutes'] ?>分 / ¥<?= number_format((int)$option['price']) ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="step-card" data-step="3">
      <div class="step-head">
        <div>
          <span>03</span>
          <h2>Staff</h2>
        </div>
        <p>担当者</p>
      </div>
      <div class="staff-grid" id="staffList">
        <?php foreach($staffs as $staff): ?>
          <button type="button" class="staff-item" data-id="<?= (int)$staff['id'] ?>">
            <span><?= htmlspecialchars($staff['name']) ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="step-card" data-step="4">
      <div class="step-head">
        <div>
          <span>04</span>
          <h2>Date</h2>
        </div>
        <p>空き時間</p>
      </div>
      <div class="date-line">
        <button type="button" id="prevDate">前日</button>
        <input type="date" id="reserveDate" value="<?= date('Y-m-d') ?>">
        <button type="button" id="nextDate">翌日</button>
      </div>
      <div class="summary-bar">
        <div>
          <small>合計時間</small>
          <strong id="totalDuration">-</strong>
        </div>
        <div>
          <small>目安金額</small>
          <strong id="totalPrice">-</strong>
        </div>
      </div>
      <div class="slot-list" id="slotList">
        <p class="empty">メニューとスタッフを選択してください。</p>
      </div>
    </section>

    <section class="step-card" data-step="5">
      <div class="step-head">
        <div>
          <span>05</span>
          <h2>Confirm</h2>
        </div>
        <p>お客様情報</p>
      </div>
      <form id="reserveForm" class="reserve-form">
        <input type="hidden" name="menu_id" id="formMenuId">
        <input type="hidden" name="staff_id" id="formStaffId">
        <input type="hidden" name="date" id="formDate">
        <input type="hidden" name="start_time" id="formStart">
        <input type="hidden" name="duration" id="formDuration">
        <label>お名前<input type="text" name="name" required placeholder="例）山田 花子"></label>
        <label>電話番号<input type="tel" name="phone" required placeholder="例）09012345678"></label>
        <label>お支払い方法
          <select name="payment_method">
            <option value="cash">現金</option>
            <option value="card">クレジット</option>
            <option value="paypay">PayPay</option>
          </select>
        </label>
        <button type="submit" class="reserve-submit">予約する</button>
      </form>
    </section>
  </main>

  <div class="toast" id="toast"></div>
  <script src="../assets/js/customer-reserve.js?v=4"></script>
</body>
</html>
