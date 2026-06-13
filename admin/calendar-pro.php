<?php
require_once __DIR__ . '/../config/db.php';

$date = $_GET['date'] ?? date('Y-m-d');
$staffs = [];
try {
  $pdo = db();
  $staffs = $pdo->query("SELECT id, name FROM staffs WHERE is_active = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $staffs = [
    ['id' => 1, 'name' => 'KIHO'],
    ['id' => 2, 'name' => 'YUINA'],
  ];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Calendar Pro | ON;ME</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=12">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand">
      <small>ON;ME OS</small>
      <strong>Salon</strong>
    </div>
    <nav>
      <a href="./index.php">Dashboard</a>
      <a class="active" href="./calendar-pro.php">Calendar Pro</a>
      <a href="./reservations.php">Reservations</a>
      <a href="./customers.php">Customers</a>
      <a href="./menus.php">Menus</a>
      <a href="./staff.php">Staff</a>
      <a href="./line-settings.php">LINE</a>
    </nav>
  </aside>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">GOOGLE CALENDAR STYLE</p>
        <h1>Calendar Pro</h1>
      </div>
      <div class="pro-actions">
        <button type="button" id="prevDay">←</button>
        <input type="date" id="calendarDate" value="<?= htmlspecialchars($date) ?>">
        <button type="button" id="nextDay">→</button>
        <button type="button" class="primary" id="addAvailability">＋受付時間</button>
      </div>
    </header>

    <section class="pro-kpi">
      <article><span>Reservations</span><strong id="kpiReservations">0</strong></article>
      <article><span>Available blocks</span><strong id="kpiAvailable">0</strong></article>
      <article><span>Staff</span><strong><?= count($staffs) ?></strong></article>
      <article><span>Selected date</span><strong id="kpiDate"><?= htmlspecialchars(date('m/d', strtotime($date))) ?></strong></article>
    </section>

    <section class="pro-calendar-card">
      <div class="pro-calendar" id="proCalendar"
        data-date="<?= htmlspecialchars($date) ?>"
        data-staff='<?= htmlspecialchars(json_encode($staffs), ENT_QUOTES) ?>'>
        <div class="pro-time-axis">
          <div class="axis-head">TIME</div>
          <?php for($h=8; $h<=22; $h++): ?>
            <div class="axis-row"><?= sprintf('%02d:00', $h) ?></div>
            <div class="axis-row half"><?= sprintf('%02d:30', $h) ?></div>
          <?php endfor; ?>
        </div>

        <?php foreach($staffs as $staff): ?>
          <div class="pro-staff-col" data-staff-id="<?= (int)$staff['id'] ?>">
            <div class="pro-staff-head"><?= htmlspecialchars($staff['name']) ?></div>
            <div class="pro-grid">
              <?php for($i=0; $i<30; $i++): ?>
                <div class="pro-cell" data-index="<?= $i ?>"></div>
              <?php endfor; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="mobile-pro-list" id="mobileProList"></section>
  </main>

  <div class="pro-modal" id="availabilityModal" aria-hidden="true">
    <div class="pro-modal-card">
      <button class="modal-close" id="closeModal">×</button>
      <p class="eyebrow">AVAILABILITY</p>
      <h2>受付可能時間</h2>
      <form id="availabilityForm">
        <input type="hidden" name="id" id="modalId">
        <label>スタッフ
          <select name="staff_id" id="modalStaff">
            <?php foreach($staffs as $staff): ?>
              <option value="<?= (int)$staff['id'] ?>"><?= htmlspecialchars($staff['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>日付
          <input type="date" name="date" id="modalDate" value="<?= htmlspecialchars($date) ?>">
        </label>
        <div class="form-grid">
          <label>開始<input type="time" name="start_time" id="modalStart" value="10:00" step="1800"></label>
          <label>終了<input type="time" name="end_time" id="modalEnd" value="18:00" step="1800"></label>
        </div>
        <button class="primary full" type="submit">保存</button>
      </form>
    </div>
  </div>

  <script src="../assets/js/admin-calendar-pro.js?v=12"></script>
</body>
</html>
