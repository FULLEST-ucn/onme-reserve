<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$error = '';
$saved = false;

function ensure_google_sync_schema(PDO $pdo): void {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS google_sync_settings (
      id INT AUTO_INCREMENT PRIMARY KEY,
      store_id INT NOT NULL DEFAULT 1,
      calendar_id VARCHAR(255) NULL,
      sync_enabled TINYINT(1) NOT NULL DEFAULT 0,
      sync_reservations TINYINT(1) NOT NULL DEFAULT 1,
      sync_staff_shifts TINYINT(1) NOT NULL DEFAULT 0,
      last_synced_at DATETIME NULL,
      memo TEXT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uniq_google_sync_store (store_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");
}

try {
  $pdo = db();
  ensure_google_sync_schema($pdo);

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $calendarId = trim($_POST['calendar_id'] ?? '');
    $syncEnabled = isset($_POST['sync_enabled']) ? 1 : 0;
    $syncReservations = isset($_POST['sync_reservations']) ? 1 : 0;
    $syncStaffShifts = isset($_POST['sync_staff_shifts']) ? 1 : 0;
    $memo = trim($_POST['memo'] ?? '');

    $stmt = $pdo->prepare("
      INSERT INTO google_sync_settings
        (store_id, calendar_id, sync_enabled, sync_reservations, sync_staff_shifts, memo, created_at, updated_at)
      VALUES
        (1, ?, ?, ?, ?, ?, NOW(), NOW())
      ON DUPLICATE KEY UPDATE
        calendar_id = VALUES(calendar_id),
        sync_enabled = VALUES(sync_enabled),
        sync_reservations = VALUES(sync_reservations),
        sync_staff_shifts = VALUES(sync_staff_shifts),
        memo = VALUES(memo),
        updated_at = NOW()
    ");
    $stmt->execute([$calendarId, $syncEnabled, $syncReservations, $syncStaffShifts, $memo]);
    $saved = true;
  }

  $stmt = $pdo->query("SELECT * FROM google_sync_settings WHERE store_id = 1 LIMIT 1");
  $settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'calendar_id' => '',
    'sync_enabled' => 0,
    'sync_reservations' => 1,
    'sync_staff_shifts' => 0,
    'last_synced_at' => null,
    'memo' => '',
  ];

} catch (Throwable $e) {
  $error = $e->getMessage();
  $settings = [
    'calendar_id' => '',
    'sync_enabled' => 0,
    'sync_reservations' => 1,
    'sync_staff_shifts' => 0,
    'last_synced_at' => null,
    'memo' => '',
  ];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Google Sync | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=103">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=103">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=103">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=103">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=8">
  <link rel="stylesheet" href="../assets/css/google-sync.css?v=1">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">SYSTEM</p>
        <h1>Google Sync</h1>
        <p class="gs-lead">Googleカレンダー連携の設定画面です。</p>
      </div>
      <div class="pro-actions">
        <a class="pro-button primary" href="./dashboard-v2.php">Dashboard</a>
      </div>
    </header>

    <?php if ($saved): ?><section class="os-panel success">保存しました。</section><?php endif; ?>
    <?php if ($error): ?><section class="os-panel gs-error"><?= htmlspecialchars($error) ?></section><?php endif; ?>

    <section class="gs-kpi">
      <article>
        <span>連携状態</span>
        <strong><?= !empty($settings['sync_enabled']) ? 'ON' : 'OFF' ?></strong>
      </article>
      <article>
        <span>予約同期</span>
        <strong><?= !empty($settings['sync_reservations']) ? 'ON' : 'OFF' ?></strong>
      </article>
      <article>
        <span>シフト同期</span>
        <strong><?= !empty($settings['sync_staff_shifts']) ? 'ON' : 'OFF' ?></strong>
      </article>
      <article>
        <span>最終同期</span>
        <strong><?= !empty($settings['last_synced_at']) ? htmlspecialchars(date('m/d H:i', strtotime($settings['last_synced_at']))) : '-' ?></strong>
      </article>
    </section>

    <section class="gs-layout">
      <form method="post" class="os-panel gs-form">
        <p class="eyebrow">SETTINGS</p>
        <h2>同期設定</h2>

        <label>
          Google Calendar ID
          <input type="text" name="calendar_id" value="<?= htmlspecialchars($settings['calendar_id'] ?? '') ?>" placeholder="example@gmail.com">
        </label>

        <label class="gs-check">
          <input type="checkbox" name="sync_enabled" <?= !empty($settings['sync_enabled']) ? 'checked' : '' ?>>
          <span>Google Syncを有効にする</span>
        </label>

        <label class="gs-check">
          <input type="checkbox" name="sync_reservations" <?= !empty($settings['sync_reservations']) ? 'checked' : '' ?>>
          <span>予約をGoogleカレンダーに同期する</span>
        </label>

        <label class="gs-check">
          <input type="checkbox" name="sync_staff_shifts" <?= !empty($settings['sync_staff_shifts']) ? 'checked' : '' ?>>
          <span>スタッフシフトを同期する</span>
        </label>

        <label>
          メモ
          <textarea name="memo" rows="5" placeholder="連携メモ"><?= htmlspecialchars($settings['memo'] ?? '') ?></textarea>
        </label>

        <button type="submit">保存</button>
      </form>

      <section class="os-panel gs-guide">
        <p class="eyebrow">NEXT STEP</p>
        <h2>連携の使い方</h2>

        <div class="guide-list">
          <article>
            <strong>1. Calendar IDを入力</strong>
            <span>GoogleカレンダーのIDまたはGmailアドレスを登録します。</span>
          </article>
          <article>
            <strong>2. 同期対象を選択</strong>
            <span>予約、スタッフシフトなど同期したい項目を選択します。</span>
          </article>
          <article>
            <strong>3. API連携は次フェーズ</strong>
            <span>現在は設定保存画面です。APIキー連携後、自動同期に拡張できます。</span>
          </article>
        </div>
      </section>
    </section>
  </main>
</body>
</html>
