<?php
require_once __DIR__ . '/../config/db.php';

try {
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $employeeNo = trim($_POST['employee_no'] ?? '');
    $role = $_POST['role'] ?? 'staff';

    if ($name && $employeeNo) {
      $stmt = $pdo->prepare("
        INSERT INTO staffs (employee_no, name, role, is_active, created_at, updated_at)
        VALUES (?, ?, ?, 1, NOW(), NOW())
      ");
      $stmt->execute([$employeeNo, $name, $role]);
    }

    header('Location: ./staff.php');
    exit;
  }

  $staffs = $pdo->query("SELECT * FROM staffs ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
  $staffs = [];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>スタッフ管理 | ON;ME</title>
  <link rel="stylesheet" href="../assets/css/admin.css?v=7">
</head>
<body class="admin-body">
<header class="admin-header">
  <div><p class="eyebrow">ON;ME OS</p><h1>Staff</h1></div>
  <nav class="admin-nav">
    <a href="./index.php">Dashboard</a>
    <a href="./calendar.php">Calendar</a>
    <a href="./reservations.php">予約</a>
    <a href="./customers.php">顧客</a>
    <a href="./menus.php">メニュー</a>
    <a class="active" href="./staff.php">スタッフ</a>
  </nav>
</header>

<main class="admin-shell split-layout">
  <?php if (!empty($error)): ?><section class="panel notice"><?= htmlspecialchars($error) ?></section><?php endif; ?>

  <section class="panel">
    <div class="panel-head"><div><p class="eyebrow">CREATE</p><h2>スタッフ追加</h2></div></div>
    <form method="post" class="admin-form">
      <label>スタッフ名<input type="text" name="name" required placeholder="KIHO"></label>
      <label>従業員番号<input type="text" name="employee_no" required placeholder="1001"></label>
      <label>権限
        <select name="role">
          <option value="staff">スタッフ</option>
          <option value="owner">オーナー</option>
        </select>
      </label>
      <button class="primary full" type="submit">追加する</button>
    </form>
  </section>

  <section class="panel">
    <div class="panel-head"><div><p class="eyebrow">LIST</p><h2>スタッフ一覧</h2></div></div>
    <div class="menu-list">
      <?php foreach($staffs as $s): ?>
        <article class="menu-row">
          <strong><?= htmlspecialchars($s['name']) ?></strong>
          <span>No.<?= htmlspecialchars($s['employee_no']) ?> / <?= htmlspecialchars($s['role'] ?? 'staff') ?></span>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</main>
</body>
</html>
