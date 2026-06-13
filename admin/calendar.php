<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$staffs = [
  ['id' => 1, 'name' => 'KIHO', 'employee_no' => '1001'],
  ['id' => 2, 'name' => 'YUINA', 'employee_no' => '1002'],
];

$date = $_GET['date'] ?? date('Y-m-d');
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>ON;ME Calendar</title>
  <link rel="stylesheet" href="../assets/css/admin.css?v=3">
</head>
<body class="admin-body">
  <header class="admin-header">
    <div>
      <p class="eyebrow">ON;ME RESERVE</p>
      <h1>Calendar</h1>
    </div>
    <nav class="admin-nav">
      <a href="./index.php">Dashboard</a>
      <a class="active" href="./calendar.php">Calendar</a>
      <a href="./availability.php">受付時間</a>
      <a href="./logout.php">Logout</a>
    </nav>
  </header>

  <main class="admin-shell">
    <section class="panel calendar-panel">
      <div class="panel-head">
        <div>
          <p class="eyebrow">STAFF SCHEDULE</p>
          <h2>受付可能時間・予約管理</h2>
        </div>
        <div class="date-tools">
          <button type="button" id="prevDay">前日</button>
          <input type="date" id="calendarDate" value="<?= htmlspecialchars($date, ENT_QUOTES) ?>">
          <button type="button" id="nextDay">翌日</button>
        </div>
      </div>

      <div class="calendar-toolbar">
        <button type="button" class="primary" id="addAvailability">＋受付可能時間</button>
        <span>PC：ドラッグで移動 / 下端で長さ変更</span>
      </div>

      <div class="desktop-calendar" id="desktopCalendar"
        data-date="<?= htmlspecialchars($date, ENT_QUOTES) ?>"
        data-staff='<?= htmlspecialchars(json_encode($staffs), ENT_QUOTES) ?>'>
        <div class="time-column">
          <div class="corner">Time</div>
          <?php for($h=9; $h<=22; $h++): ?>
            <div class="time-row"><?= sprintf('%02d:00', $h) ?></div>
            <div class="time-row half"><?= sprintf('%02d:30', $h) ?></div>
          <?php endfor; ?>
        </div>

        <?php foreach($staffs as $staff): ?>
          <div class="staff-column" data-staff-id="<?= $staff['id'] ?>">
            <div class="staff-head"><?= htmlspecialchars($staff['name']) ?></div>
            <div class="slot-grid">
              <?php for($i=0; $i<28; $i++): ?>
                <div class="grid-cell" data-index="<?= $i ?>"></div>
              <?php endfor; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="mobile-timeline" id="mobileTimeline"
        data-date="<?= htmlspecialchars($date, ENT_QUOTES) ?>"
        data-staff='<?= htmlspecialchars(json_encode($staffs), ENT_QUOTES) ?>'>
        <?php foreach($staffs as $staff): ?>
          <section class="mobile-staff-card" data-staff-id="<?= $staff['id'] ?>">
            <div class="mobile-staff-head">
              <h3><?= htmlspecialchars($staff['name']) ?></h3>
              <button type="button" class="mini-add" data-staff-id="<?= $staff['id'] ?>">＋追加</button>
            </div>
            <div class="mobile-items" id="mobileStaff<?= $staff['id'] ?>"></div>
          </section>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <div class="modal" id="availabilityModal" aria-hidden="true">
    <div class="modal-card">
      <button class="modal-close" id="closeModal">×</button>
      <p class="eyebrow">AVAILABILITY</p>
      <h2>受付可能時間を追加</h2>
      <form id="availabilityForm">
        <label>
          スタッフ
          <select name="staff_id" id="modalStaff">
            <?php foreach($staffs as $staff): ?>
              <option value="<?= $staff['id'] ?>"><?= htmlspecialchars($staff['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          日付
          <input type="date" name="date" id="modalDate" value="<?= htmlspecialchars($date, ENT_QUOTES) ?>">
        </label>
        <div class="form-grid">
          <label>
            開始
            <input type="time" name="start_time" id="modalStart" value="10:00" step="1800">
          </label>
          <label>
            終了
            <input type="time" name="end_time" id="modalEnd" value="18:00" step="1800">
          </label>
        </div>
        <button type="submit" class="primary full">保存</button>
      </form>
    </div>
  </div>

  <script src="../assets/js/admin-calendar.js?v=3"></script>
</body>
</html>
