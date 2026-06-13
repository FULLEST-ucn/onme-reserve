<?php
$configPath = __DIR__ . '/../config/line.php';
$config = file_exists($configPath) ? require $configPath : [
  'channel_access_token' => '',
  'admin_user_ids' => [],
];

$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = trim($_POST['channel_access_token'] ?? '');
  $adminIdsRaw = trim($_POST['admin_user_ids'] ?? '');
  $adminIds = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $adminIdsRaw))));

  $content = "<?php\nreturn " . var_export([
    'channel_access_token' => $token,
    'admin_user_ids' => $adminIds,
  ], true) . ";\n";

  file_put_contents($configPath, $content);
  $config = require $configPath;
  $saved = true;
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>LINE設定 | ON;ME</title>
  <link rel="stylesheet" href="../assets/css/admin.css?v=10">
</head>
<body class="admin-body">
<header class="admin-header">
  <div><p class="eyebrow">ON;ME OS</p><h1>LINE</h1></div>
  <nav class="admin-nav">
    <a href="./index.php">Dashboard</a>
    <a href="./calendar.php">Calendar</a>
    <a href="./reservations.php">予約</a>
    <a class="active" href="./line-settings.php">LINE設定</a>
  </nav>
</header>

<main class="admin-shell">
  <?php if ($saved): ?>
    <section class="panel notice">LINE設定を保存しました。</section>
  <?php endif; ?>

  <section class="panel">
    <div class="panel-head">
      <div>
        <p class="eyebrow">MESSAGING API</p>
        <h2>LINE通知設定</h2>
      </div>
    </div>

    <form method="post" class="admin-form">
      <label>チャネルアクセストークン
        <textarea name="channel_access_token" rows="4" placeholder="LINE DevelopersのMessaging APIから取得"><?= htmlspecialchars($config['channel_access_token'] ?? '') ?></textarea>
      </label>

      <label>店舗通知を受けるLINE UserID（1行に1つ）
        <textarea name="admin_user_ids" rows="8" placeholder="Uxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"><?= htmlspecialchars(implode("\n", $config['admin_user_ids'] ?? [])) ?></textarea>
      </label>

      <button class="primary full" type="submit">保存する</button>
    </form>
  </section>
</main>
</body>
</html>
