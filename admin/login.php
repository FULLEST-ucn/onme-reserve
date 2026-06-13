<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $loginId = trim($_POST['login_id'] ?? '');
  $password = $_POST['password'] ?? '';

  try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM staffs WHERE login_id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$loginId]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($staff && password_verify($password, $staff['password_hash'] ?? '')) {
      $_SESSION['auth_user'] = [
        'id' => (int)$staff['id'],
        'name' => $staff['name'],
        'role' => $staff['role'] ?: 'staff',
      ];
      header('Location: ./staff-dashboard.php');
      exit;
    }
    $error = 'ログイン情報が違います。';
  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=20">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=20">
</head>
<body class="os-login-body">
  <main class="os-login-card">
    <p class="eyebrow">ON;ME OS</p>
    <h1>Login</h1>
    <?php if ($error): ?><p class="os-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post" class="os-form">
      <label>Login ID<input name="login_id" required placeholder="kiho"></label>
      <label>Password<input name="password" type="password" required placeholder="1234"></label>
      <button type="submit">Login</button>
    </form>
    <p class="muted-text">初期パスワードは 1234 です。</p>
  </main>
</body>
</html>
