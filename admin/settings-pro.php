<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();

require_once __DIR__ . '/../config/store.php';
$store = current_store();
$saved = false;
$error = '';

try {
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
      UPDATE stores
      SET name=?, phone=?, address=?, line_liff_id=?, line_channel_token=?,
          google_calendar_id=?, stripe_public_key=?, stripe_secret_key=?, updated_at=NOW()
      WHERE id=?
    ");
    $stmt->execute([
      trim($_POST['name'] ?? ''),
      trim($_POST['phone'] ?? ''),
      trim($_POST['address'] ?? ''),
      trim($_POST['line_liff_id'] ?? ''),
      trim($_POST['line_channel_token'] ?? ''),
      trim($_POST['google_calendar_id'] ?? ''),
      trim($_POST['stripe_public_key'] ?? ''),
      trim($_POST['stripe_secret_key'] ?? ''),
      $store['id']
    ]);
    $saved = true;
    $store = current_store();
  }
} catch(Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Settings Pro | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=25">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=25">
  <link rel="stylesheet" href="../assets/css/suite-pro.css?v=25">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>Settings</strong></div>
    <nav>
      <a href="./owner-dashboard.php">Owner</a>
      <a href="./line-automation.php">LINE</a>
      <a href="./stripe-pro.php">Stripe</a>
      <a href="./google-calendar-pro.php">Google</a>
      <a class="active" href="./settings-pro.php">Settings</a>
    </nav>
  </aside>
  <main class="pro-main">
    <header class="pro-top"><div><p class="eyebrow">SAAS SETTINGS</p><h1>Settings Pro</h1></div></header>
    <?php if($saved): ?><section class="os-panel success">設定を保存しました。</section><?php endif; ?>
    <?php if($error): ?><section class="os-panel"><?= htmlspecialchars($error) ?></section><?php endif; ?>

    <section class="os-panel">
      <p class="eyebrow">STORE</p>
      <h2>店舗・連携設定</h2>
      <form method="post" class="os-form">
        <label>店舗名<input name="name" value="<?= htmlspecialchars($store['name'] ?? '') ?>"></label>
        <label>電話番号<input name="phone" value="<?= htmlspecialchars($store['phone'] ?? '') ?>"></label>
        <label>住所<input name="address" value="<?= htmlspecialchars($store['address'] ?? '') ?>"></label>
        <label>LIFF ID<input name="line_liff_id" value="<?= htmlspecialchars($store['line_liff_id'] ?? '') ?>"></label>
        <label>LINE Channel Token<textarea name="line_channel_token" rows="4"><?= htmlspecialchars($store['line_channel_token'] ?? '') ?></textarea></label>
        <label>Google Calendar ID<input name="google_calendar_id" value="<?= htmlspecialchars($store['google_calendar_id'] ?? '') ?>"></label>
        <label>Stripe Public Key<input name="stripe_public_key" value="<?= htmlspecialchars($store['stripe_public_key'] ?? '') ?>"></label>
        <label>Stripe Secret Key<input name="stripe_secret_key" value="<?= htmlspecialchars($store['stripe_secret_key'] ?? '') ?>"></label>
        <button>保存する</button>
      </form>
    </section>
  </main>
</body>
</html>
