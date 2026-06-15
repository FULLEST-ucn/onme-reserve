<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();

$error = '';
$saved = false;
$deleted = false;

try {
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
      $name = trim($_POST['name'] ?? '');
      $employeeNo = trim($_POST['employee_no'] ?? '');
      $role = trim($_POST['role'] ?? 'staff');
      $loginId = strtolower(trim($_POST['login_id'] ?? $name));

      if ($name === '') {
        throw new Exception('スタッフ名を入力してください。');
      }

      if ($employeeNo === '') {
        $employeeNo = (string)random_int(1000, 9999);
      }

      $stmt = $pdo->prepare("
        INSERT INTO staffs
          (name, employee_no, login_id, password_hash, role, is_active, store_id, created_at, updated_at)
        VALUES
          (?, ?, ?, ?, ?, 1, 1, NOW(), NOW())
      ");
      $stmt->execute([
        $name,
        $employeeNo,
        $loginId,
        '$2y$12$/.9RZibuEMSQe8BFS/i.k.iYAIMdWCf0aVqakScHiQ1iJOnIQ65JO',
        $role
      ]);
      $saved = true;
    }

    if ($action === 'update' && isset($_POST['staff'])) {
      foreach ($_POST['staff'] as $id => $staff) {
        $stmt = $pdo->prepare("
          UPDATE staffs
          SET
            name = ?,
            employee_no = ?,
            login_id = ?,
            role = ?,
            updated_at = NOW()
          WHERE id = ?
        ");
        $stmt->execute([
          trim($staff['name'] ?? ''),
          trim($staff['employee_no'] ?? ''),
          strtolower(trim($staff['login_id'] ?? '')),
          trim($staff['role'] ?? 'staff'),
          (int)$id
        ]);
      }
      $saved = true;
    }

    if ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE staffs SET is_active = 0, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        $deleted = true;
      }
    }
  }

  $staffs = $pdo->query("
    SELECT *
    FROM staffs
    WHERE is_active = 1
    ORDER BY id ASC
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
  $staffs = [];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Staff | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=70">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=70">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=70">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=70">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=3">
  <link rel="stylesheet" href="../assets/css/staff-pro.css?v=1">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">STAFF MANAGEMENT</p>
        <h1>Staff</h1>
        <p class="staff-lead">スタッフ追加・ログインID・権限を管理できます。</p>
      </div>
      <div class="pro-actions">
        <a class="pro-button primary" href="./staff-shifts.php">Shift</a>
      </div>
    </header>

    <?php if ($saved): ?>
      <section class="os-panel success">保存しました。</section>
    <?php endif; ?>

    <?php if ($deleted): ?>
      <section class="os-panel success">スタッフを非表示にしました。</section>
    <?php endif; ?>

    <?php if ($error): ?>
      <section class="os-panel"><?= htmlspecialchars($error) ?></section>
    <?php endif; ?>

    <section class="staff-layout">
      <article class="os-panel">
        <p class="eyebrow">CREATE</p>
        <h2>スタッフ追加</h2>

        <form method="post" class="staff-form">
          <input type="hidden" name="action" value="create">

          <label>
            スタッフ名
            <input name="name" placeholder="例）KIHO" required>
          </label>

          <label>
            従業員番号
            <input name="employee_no" placeholder="例）1001">
          </label>

          <label>
            Login ID
            <input name="login_id" placeholder="例）kiho">
          </label>

          <label>
            権限
            <select name="role">
              <option value="staff">Staff</option>
              <option value="manager">Manager</option>
              <option value="owner">Owner</option>
            </select>
          </label>

          <button type="submit">追加する</button>

          <p class="staff-note">初期パスワードは 1234 です。</p>
        </form>
      </article>

      <article class="os-panel">
        <div class="staff-panel-head">
          <div>
            <p class="eyebrow">LIST</p>
            <h2>スタッフ一覧</h2>
          </div>
        </div>

        <form method="post">
          <input type="hidden" name="action" value="update">

          <div class="staff-table-head">
            <span>名前</span>
            <span>従業員番号</span>
            <span>Login ID</span>
            <span>権限</span>
            <span>操作</span>
          </div>

          <div class="staff-list">
            <?php foreach ($staffs as $staff): ?>
              <article class="staff-row">
                <div>
                  <small>名前</small>
                  <input name="staff[<?= (int)$staff['id'] ?>][name]" value="<?= htmlspecialchars($staff['name'] ?? '') ?>">
                </div>

                <div>
                  <small>従業員番号</small>
                  <input name="staff[<?= (int)$staff['id'] ?>][employee_no]" value="<?= htmlspecialchars($staff['employee_no'] ?? '') ?>">
                </div>

                <div>
                  <small>Login ID</small>
                  <input name="staff[<?= (int)$staff['id'] ?>][login_id]" value="<?= htmlspecialchars($staff['login_id'] ?? '') ?>">
                </div>

                <div>
                  <small>権限</small>
                  <select name="staff[<?= (int)$staff['id'] ?>][role]">
                    <?php foreach (['staff'=>'Staff','manager'=>'Manager','owner'=>'Owner'] as $value => $label): ?>
                      <option value="<?= $value ?>" <?= ($staff['role'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="staff-actions">
                  <small>操作</small>
                  <button
                    type="submit"
                    form="deleteStaff<?= (int)$staff['id'] ?>"
                    onclick="return confirm('このスタッフを非表示にしますか？');"
                  >削除</button>
                </div>
              </article>
            <?php endforeach; ?>

            <?php if (!$staffs): ?>
              <p class="muted-text">スタッフがいません。</p>
            <?php endif; ?>
          </div>

          <button class="save-staff-button" type="submit">スタッフ情報を保存</button>
        </form>

        <?php foreach ($staffs as $staff): ?>
          <form id="deleteStaff<?= (int)$staff['id'] ?>" method="post" hidden>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$staff['id'] ?>">
          </form>
        <?php endforeach; ?>
      </article>
    </section>
  </main>
</body>
</html>
