<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
<<<<<<< HEAD

$date = $_GET['date'] ?? date('Y-m-d');
$prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
$staffs=[]; $reservations=[]; $availability=[]; $error='';

function minutes_from_midnight(string $datetime): int { $ts=strtotime($datetime); return ((int)date('H',$ts)*60)+(int)date('i',$ts); }
function event_style(string $start,string $end): string {
  $dayStart=8*60; $slotHeight=72; $startMin=minutes_from_midnight($start); $endMin=minutes_from_midnight($end);
  $top=max(0,(($startMin-$dayStart)/30)*$slotHeight); $height=max(34,(($endMin-$startMin)/30)*$slotHeight-8);
  return "top:{$top}px;height:{$height}px;";
}

try {
  $pdo=db();
  $staffs=$pdo->query("SELECT * FROM staffs WHERE is_active=1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

  $stmt=$pdo->prepare("SELECT r.*,c.name customer_name,s.name staff_name,m.name menu_name,m.price FROM reservations r LEFT JOIN customers c ON c.id=r.customer_id LEFT JOIN staffs s ON s.id=r.staff_id LEFT JOIN menus m ON m.id=r.menu_id WHERE DATE(r.start_datetime)=? AND r.status IN ('reserved','confirmed','completed') ORDER BY r.start_datetime ASC");
  $stmt->execute([$date]); $reservations=$stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt=$pdo->prepare("SELECT a.*,s.name staff_name FROM availability a LEFT JOIN staffs s ON s.id=a.staff_id WHERE DATE(a.start_datetime)=? AND COALESCE(a.is_active,1)=1 ORDER BY a.start_datetime ASC");
  $stmt->execute([$date]); $availability=$stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) { $error=$e->getMessage(); }

$hours=[]; for($h=8;$h<=21;$h++){ $hours[]=sprintf('%02d:00',$h); $hours[]=sprintf('%02d:30',$h); }
=======
$date = $_GET['date'] ?? date('Y-m-d');
>>>>>>> 77d34561990733cc5fd0dc12c5c67a39e3e638ab
?>
<!doctype html>
<html lang="ja">
<head>
<<<<<<< HEAD
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Calendar Master | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=75">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=75">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=75">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=75">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=4">
  <link rel="stylesheet" href="../assets/css/calendar-action-sheet.css?v=1">
</head>
<body class="pro-body">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="pro-main">
  <header class="pro-top calendar-top-tight">
    <div><p class="eyebrow">APPLE / GOOGLE STYLE</p><h1>Calendar Master</h1><p class="drag-help">空白タップで新規空き枠 / 予定タップで操作 / 長押しで移動</p></div>
    <div class="calendar-actions">
      <a class="pro-button" href="./calendar-master.php?date=<?= htmlspecialchars($prevDate) ?>">←</a>
      <form method="get" class="calendar-date-form"><input type="date" name="date" value="<?= htmlspecialchars($date) ?>"><button>表示</button></form>
      <a class="pro-button" href="./calendar-master.php?date=<?= htmlspecialchars($nextDate) ?>">→</a>
      <a class="pro-button primary" href="./availability.php?date=<?= htmlspecialchars($date) ?>">＋空き枠追加</a>
    </div>
  </header>
  <?php if($error): ?><section class="os-panel"><?= htmlspecialchars($error) ?></section><?php endif; ?>
  <section class="calendar-kpi">
    <article><span>Reservations</span><strong><?= count($reservations) ?></strong></article>
    <article><span>Available blocks</span><strong><?= count($availability) ?></strong></article>
    <article><span>Staff</span><strong><?= count($staffs) ?></strong></article>
    <article><span>Selected date</span><strong><?= date('m/d', strtotime($date)) ?></strong></article>
  </section>
  <section class="calendar-master-panel">
    <div class="calendar-table" id="calendarTable" data-date="<?= htmlspecialchars($date) ?>" style="--staff-count: <?= max(1,count($staffs)) ?>;">
      <div class="calendar-head"><div>TIME</div><?php foreach($staffs as $staff): ?><div><?= htmlspecialchars($staff['name']) ?></div><?php endforeach; ?></div>
      <div class="calendar-body">
        <div class="time-column"><?php foreach($hours as $time): ?><div class="time-cell"><?= htmlspecialchars($time) ?></div><?php endforeach; ?></div>
        <?php foreach($staffs as $staff): ?>
          <div class="staff-column" data-staff-id="<?= (int)$staff['id'] ?>" data-staff-name="<?= htmlspecialchars($staff['name']) ?>">
            <?php foreach($hours as $time): ?><div class="time-grid-line"></div><?php endforeach; ?>
            <?php foreach($availability as $a): if((int)$a['staff_id'] === (int)$staff['id']): ?>
              <article class="calendar-event event-available draggable-event editable-event" data-type="availability" data-id="<?= (int)$a['id'] ?>" data-date="<?= htmlspecialchars(date('Y-m-d',strtotime($a['start_datetime']))) ?>" data-start="<?= htmlspecialchars(date('H:i',strtotime($a['start_datetime']))) ?>" data-end="<?= htmlspecialchars(date('H:i',strtotime($a['end_datetime']))) ?>" data-note="<?= htmlspecialchars($a['note'] ?? '') ?>" data-capacity="<?= (int)($a['capacity'] ?? 1) ?>" style="<?= htmlspecialchars(event_style($a['start_datetime'],$a['end_datetime'])) ?>">
                <strong>空き枠</strong><span><?= htmlspecialchars(date('H:i',strtotime($a['start_datetime']))) ?> - <?= htmlspecialchars(date('H:i',strtotime($a['end_datetime']))) ?></span><?php if(!empty($a['note'])): ?><small><?= htmlspecialchars($a['note']) ?></small><?php endif; ?>
              </article>
            <?php endif; endforeach; ?>
            <?php foreach($reservations as $r): if((int)$r['staff_id'] === (int)$staff['id']): $end=!empty($r['end_datetime'])?$r['end_datetime']:date('Y-m-d H:i:s',strtotime($r['start_datetime'].' +90 minutes')); $statusClass=($r['status']??'')==='completed'?'event-completed':'event-reservation'; ?>
              <article class="calendar-event <?= $statusClass ?> draggable-event editable-event" data-type="reservation" data-id="<?= (int)$r['id'] ?>" data-date="<?= htmlspecialchars(date('Y-m-d',strtotime($r['start_datetime']))) ?>" data-start="<?= htmlspecialchars(date('H:i',strtotime($r['start_datetime']))) ?>" data-end="<?= htmlspecialchars(date('H:i',strtotime($end))) ?>" data-note="" data-capacity="1" style="<?= htmlspecialchars(event_style($r['start_datetime'],$end)) ?>">
                <strong><?= htmlspecialchars($r['customer_name'] ?? 'お客様') ?></strong><span><?= htmlspecialchars(date('H:i',strtotime($r['start_datetime']))) ?> / <?= htmlspecialchars($r['menu_name'] ?? '-') ?></span>
              </article>
            <?php endif; endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>

<div class="action-sheet-backdrop" id="actionSheet" hidden>
  <div class="action-sheet">
    <div class="sheet-title"><strong id="sheetTitle">操作</strong><span id="sheetSubTitle"></span></div>
    <button type="button" id="sheetEdit">編集</button>
    <button type="button" id="sheetCreate">この時間に空き枠追加</button>
    <button type="button" id="sheetDuplicate">複製</button>
    <button type="button" class="danger" id="sheetDelete">削除</button>
    <button type="button" class="cancel" id="sheetCancel">キャンセル</button>
  </div>
</div>

<div class="calendar-modal-backdrop" id="calendarEditModal" hidden>
  <div class="calendar-modal">
    <div class="calendar-modal-head"><div><p class="eyebrow" id="modalEyebrow">EDIT SCHEDULE</p><h2 id="modalTitle">空き枠編集</h2></div><button type="button" class="modal-close" id="modalClose">×</button></div>
    <form id="calendarEditForm" class="calendar-edit-form">
      <input type="hidden" id="editMode" value="edit"><input type="hidden" id="editType" value="availability"><input type="hidden" id="editId"><input type="hidden" id="editStaffId">
      <label>日付<input type="date" id="editDate" required></label>
      <div class="calendar-edit-grid"><label>開始<input type="time" id="editStart" required></label><label>終了<input type="time" id="editEnd" required></label></div>
      <label id="capacityLabel">受付数<input type="number" id="editCapacity" min="1" value="1"></label>
      <label id="noteLabel">メモ<input id="editNote" placeholder="例）通常受付 / 特別枠"></label>
      <button type="submit" id="modalSubmit">保存する</button>
    </form>
  </div>
</div>

<script src="../assets/js/calendar-action-sheet.js?v=1"></script>
=======
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Calendar Master | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=40">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=40">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=40">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>Cal</strong></div>
    <nav>
      <a href="./dashboard-v2.php">Dashboard V2</a>
      <a class="active" href="./calendar-master.php">Calendar Master</a>
      <a href="./calendar-pro.php">Calendar Pro</a>
      <a href="./calendar-week.php">Week</a>
    </nav>
  </aside>
  <main class="pro-main">
    <header class="pro-top">
      <div><p class="eyebrow">APPLE / GOOGLE STYLE</p><h1>Calendar Master</h1></div>
      <div class="pro-actions">
        <a class="pro-button primary" href="./calendar-pro.php?date=<?= htmlspecialchars($date) ?>">Day</a>
        <a class="pro-button" href="./calendar-week.php?date=<?= htmlspecialchars($date) ?>">Week</a>
      </div>
    </header>
    <section class="suite40-calendar-shell">
      <iframe src="./calendar-pro.php?date=<?= htmlspecialchars($date) ?>" loading="lazy"></iframe>
    </section>
  </main>
>>>>>>> 77d34561990733cc5fd0dc12c5c67a39e3e638ab
</body>
</html>
