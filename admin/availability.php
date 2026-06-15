<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();

$error = '';
$saved = false;
$deleted = false;
$date = $_GET['date'] ?? date('Y-m-d');

try {
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE availability SET is_active = 0, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        $deleted = true;
      }
    }

    if ($action === 'create') {
      $date = $_POST['date'] ?? $date;
      $staffId = (int)($_POST['staff_id'] ?? 0);
      $startTime = $_POST['start_time'] ?? '10:00';
      $endTime = $_POST['end_time'] ?? '19:00';
      $capacity = max(1, (int)($_POST['capacity'] ?? 1));
      $note = trim($_POST['note'] ?? '');

      $startDatetime = $date . ' ' . $startTime . ':00';
      $endDatetime = $date . ' ' . $endTime . ':00';

      if ($staffId <= 0) {
        throw new Exception('スタッフを選択してください。');
      }

      if (strtotime($endDatetime) <= strtotime($startDatetime)) {
        throw new Exception('終了時間は開始時間より後にしてください。');
      }

      $stmt = $pdo->prepare("
        INSERT INTO availability
          (staff_id, start_datetime, end_datetime, note, store_id, date, start_time, end_time, capacity, is_active, created_at, updated_at)
        VALUES
          (?, ?, ?, ?, 1, ?, ?, ?, ?, 1, NOW(), NOW())
      ");
      $stmt->execute([
        $staffId,
        $startDatetime,
        $endDatetime,
        $note,
        $date,
        $startTime . ':00',
        $endTime . ':00',
        $capacity
      ]);

      $saved = true;
    }
  }

  $staffs = $pdo->query("
    SELECT *
    FROM staffs
    WHERE is_active = 1
    ORDER BY id ASC
  ")->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT
      a.*,
      s.name AS staff_name
    FROM availability a
    LEFT JOIN staffs s ON s.id = a.staff_id
    WHERE DATE(a.start_datetime) = ?
      AND COALESCE(a.is_active, 1) = 1
    ORDER BY a.start_datetime ASC
  ");
  $stmt->execute([$date]);
  $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
  $staffs = [];
  $blocks = [];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Availability | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=68">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=68">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=68">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=68">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=3">
  <link rel="stylesheet" href="../assets/css/availability-pro.css?v=2">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">AVAILABLE BLOCKS</p>
        <h1>空き枠追加</h1>
        <p class="availability-lead">スタッフごとの受付可能時間を追加できます。</p>
      </div>
      <div class="pro-actions">
        <a class="pro-button primary" href="./calendar-master.php?date=<?= htmlspecialchars($date) ?>">Calendar Master</a>
      </div>
    </header>

    <?php if ($saved): ?>
      <section class="os-panel success">空き枠を追加しました。</section>
    <?php endif; ?>

    <?php if ($deleted): ?>
      <section class="os-panel success">空き枠を削除しました。</section>
    <?php endif; ?>

    <?php if ($error): ?>
      <section class="os-panel"><?= htmlspecialchars($error) ?></section>
    <?php endif; ?>

    <section class="availability-layout">
      <article class="os-panel">
        <p class="eyebrow">CREATE</p>
        <h2>受付可能時間を追加</h2>

        <form method="post" class="availability-form">
          <input type="hidden" name="action" value="create">

          <label>
            日付
            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" required>
          </label>

          <label>
            スタッフ
            <select name="staff_id" required>
              <option value="">選択してください</option>
              <?php foreach ($staffs as $staff): ?>
                <option value="<?= (int)$staff['id'] ?>"><?= htmlspecialchars($staff['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <div class="availability-form-grid">
            <label>
              開始
              <input type="time" name="start_time" value="10:00" required>
            </label>

            <label>
              終了
              <input type="time" name="end_time" value="19:00" required>
            </label>
          </div>

          <label>
            受付数
            <input type="number" name="capacity" value="1" min="1">
          </label>

          <label>
            メモ
            <input name="note" placeholder="例）通常受付 / 特別枠">
          </label>

          <button type="submit">空き枠を追加する</button>
        </form>
      </article>

      <article class="os-panel">
        <p class="eyebrow">LIST</p>
        <h2><?= date('m/d', strtotime($date)) ?> の空き枠</h2>

        <form method="get" class="date-filter">
          <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
          <button>表示</button>
        </form>

        <div class="availability-list">
          <?php if (!$blocks): ?>
            <p class="muted-text">この日の空き枠はありません。</p>
          <?php endif; ?>

          <?php foreach ($blocks as $block): ?>
            <article class="availability-card">
              <div>
                <strong><?= htmlspecialchars($block['staff_name'] ?? '-') ?></strong>
                <span><?= htmlspecialchars(date('H:i', strtotime($block['start_datetime']))) ?> - <?= htmlspecialchars(date('H:i', strtotime($block['end_datetime']))) ?></span>
                <?php if (!empty($block['note'])): ?>
                  <small><?= htmlspecialchars($block['note']) ?></small>
                <?php endif; ?>
              </div>

              <form method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$block['id'] ?>">
                <button onclick="return confirm('この空き枠を削除しますか？');">削除</button>
              </form>
            </article>
          <?php endforeach; ?>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
