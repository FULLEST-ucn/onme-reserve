<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();

$date = $_GET['date'] ?? date('Y-m-d');
$saved = false;
try {
  $pdo = db();
  if ($_SERVER['REQUEST_METHOD']==='POST') {
    foreach($_POST['shift'] ?? [] as $staffId=>$s) {
      $stmt=$pdo->prepare("
        INSERT INTO staff_shifts (staff_id, work_date, start_time, end_time, status, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE start_time=VALUES(start_time), end_time=VALUES(end_time), status=VALUES(status)
      ");
      $stmt->execute([(int)$staffId, $_POST['date'], $s['start'], $s['end'], $s['status']]);
    }
    $saved=true;
    $date=$_POST['date'];
  }
  $staffs=$pdo->query("SELECT * FROM staffs WHERE is_active=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
  $stmt=$pdo->prepare("SELECT * FROM staff_shifts WHERE work_date=?");
  $stmt->execute([$date]);
  $shifts=[];
  foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $s) $shifts[$s['staff_id']]=$s;
} catch(Throwable $e){$error=$e->getMessage(); $staffs=[]; $shifts=[];}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Staff Shifts | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=40"><link rel="stylesheet" href="../assets/css/os-pro.css?v=40"><link rel="stylesheet" href="../assets/css/suite40.css?v=40">
</head>
<body class="pro-body">
  <aside class="pro-sidebar"><div class="brand"><small>ON;ME OS</small><strong>Shift</strong></div><nav><a href="./dashboard-v2.php">Dashboard</a><a class="active" href="./staff-shifts.php">Shift</a><a href="./staff.php">Staff</a></nav></aside>
  <main class="pro-main">
    <header class="pro-top"><div><p class="eyebrow">STAFF SCHEDULE</p><h1>Shift</h1></div></header>
    <?php if($saved): ?><section class="os-panel success">保存しました。</section><?php endif; ?>
    <section class="os-panel">
      <form method="post" class="os-form">
        <label>日付<input type="date" name="date" value="<?= htmlspecialchars($date) ?>"></label>
        <div class="suite40-table">
          <?php foreach($staffs as $st): $s=$shifts[$st['id']]??[]; ?>
          <div class="suite40-shift-row">
            <strong><?= htmlspecialchars($st['name']) ?></strong>
            <input type="time" name="shift[<?= (int)$st['id'] ?>][start]" value="<?= htmlspecialchars(substr($s['start_time']??'10:00',0,5)) ?>">
            <input type="time" name="shift[<?= (int)$st['id'] ?>][end]" value="<?= htmlspecialchars(substr($s['end_time']??'19:00',0,5)) ?>">
            <select name="shift[<?= (int)$st['id'] ?>][status]"><option value="work">出勤</option><option value="off" <?= ($s['status']??'')==='off'?'selected':'' ?>>休み</option></select>
          </div>
          <?php endforeach; ?>
        </div>
        <button>保存</button>
      </form>
    </section>
  </main>
</body>
</html>
