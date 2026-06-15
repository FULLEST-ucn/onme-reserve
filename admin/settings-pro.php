<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();

$error = '';
$saved = false;

try {
  $pdo = db();

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS app_settings (
      id INT AUTO_INCREMENT PRIMARY KEY,
      setting_key VARCHAR(100) NOT NULL UNIQUE,
      setting_value TEXT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] ?? [] as $key => $value) {
      $stmt = $pdo->prepare("
        INSERT INTO app_settings (setting_key, setting_value, created_at, updated_at)
        VALUES (?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
      ");
      $stmt->execute([$key, trim((string)$value)]);
    }
    $saved = true;
  }

  $rows = $pdo->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

  $settings = [
    'salon_name' => $rows['salon_name'] ?? 'ON;ME',
    'store_name' => $rows['store_name'] ?? 'ON;ME Salon',
    'open_time' => $rows['open_time'] ?? '10:00',
    'close_time' => $rows['close_time'] ?? '19:00',
    'reservation_interval' => $rows['reservation_interval'] ?? '30',
    'default_capacity' => $rows['default_capacity'] ?? '1',
  ];
} catch (Throwable $e) {
  $error = $e->getMessage();
  $settings = [
    'salon_name' => 'ON;ME',
    'store_name' => 'ON;ME Salon',
    'open_time' => '10:00',
    'close_time' => '19:00',
    'reservation_interval' => '30',
    'default_capacity' => '1',
  ];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Settings | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=77">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=77">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=77">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=77">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=6">
  <link rel="stylesheet" href="../assets/css/settings-unified.css?v=1">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">SYSTEM CONFIG</p>
        <h1>Settings</h1>
        <p class="settings-lead">サロン基本情報・予約設定を管理できます。</p>
      </div>
      <div class="pro-actions">
        <a class="pro-button primary" href="./dashboard-v2.php">Dashboard</a>
      </div>
    </header>

    <?php if ($saved): ?><section class="os-panel success">設定を保存しました。</section><?php endif; ?>
    <?php if ($error): ?><section class="os-panel"><?= htmlspecialchars($error) ?></section><?php endif; ?>

    <section class="settings-layout">
      <article class="os-panel">
        <p class="eyebrow">BASIC</p>
        <h2>基本設定</h2>

        <form method="post" class="settings-form">
          <label>
            サロン名
            <input name="settings[salon_name]" value="<?= htmlspecialchars($settings['salon_name']) ?>">
          </label>

          <label>
            店舗名
            <input name="settings[store_name]" value="<?= htmlspecialchars($settings['store_name']) ?>">
          </label>

          <div class="settings-grid">
            <label>
              営業開始
              <input type="time" name="settings[open_time]" value="<?= htmlspecialchars($settings['open_time']) ?>">
            </label>

            <label>
              営業終了
              <input type="time" name="settings[close_time]" value="<?= htmlspecialchars($settings['close_time']) ?>">
            </label>
          </div>

          <div class="settings-grid">
            <label>
              予約間隔（分）
              <input type="number" name="settings[reservation_interval]" value="<?= htmlspecialchars($settings['reservation_interval']) ?>">
            </label>

            <label>
              デフォルト受付数
              <input type="number" name="settings[default_capacity]" value="<?= htmlspecialchars($settings['default_capacity']) ?>">
            </label>
          </div>

          <button type="submit">保存する</button>
        </form>
      </article>

      <article class="os-panel settings-guide">
        <p class="eyebrow">CURRENT</p>
        <h2>現在の設定</h2>

        <div class="settings-list">
          <article><span>サロン名</span><strong><?= htmlspecialchars($settings['salon_name']) ?></strong></article>
          <article><span>店舗名</span><strong><?= htmlspecialchars($settings['store_name']) ?></strong></article>
          <article><span>営業時間</span><strong><?= htmlspecialchars($settings['open_time']) ?> - <?= htmlspecialchars($settings['close_time']) ?></strong></article>
          <article><span>予約間隔</span><strong><?= htmlspecialchars($settings['reservation_interval']) ?>分</strong></article>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
