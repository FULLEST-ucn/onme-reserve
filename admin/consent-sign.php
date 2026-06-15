<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$customerId = (int)($_GET['customer_id'] ?? $_GET['id'] ?? 0);
$error = '';
$saved = false;
$customer = null;
$latestConsent = null;

$posted = [
  'customer_name' => '',
  'birthday' => '',
  'job' => '',
  'phone' => '',
  'address' => '',
  'nail_shape' => '',
  'checked' => [],
  'signature_data' => '',
];

$invalidFields = [];
$invalidChecks = [];

$checkItems = [
  'skin' => '皮膚疾患などをお持ちの場合は、事前にお申し出ください。',
  'medicine' => '薬品、紫外線、アレルギー等がある場合には必ずお申し出ください。',
  'condition' => '爪、お身体の状態によっては施術をお断りする場合がございます。',
  'accident' => '上記以外でも施術中の万が一の不足事態が起こっても一切の責任を負いかねます。',
  'guarantee' => '施術後の保証期間はハンド、フットネイル共に同じとし、ジェルネイルは施術日を含め5日以内、装飾品が取れた場合にもお直しさせていただきます。',
  'remove' => 'ご自身で無理矢理除去されますと自爪を痛める原因となりますので、そのような行為はおやめください。',
  'privacy' => 'お預かりします氏名、住所、電話番号等の個人情報は適正に管理し、ご了承なく第三者への開示・提供はいたしません。',
  'understand' => '上記の内容について理解しました。',
];

$nailShapes = ['オーバル','ラウンド','ポイント','スクエアオフ','相談'];

try {
  $pdo = db();

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS consent_form_signatures (
      id INT AUTO_INCREMENT PRIMARY KEY,
      customer_id INT NOT NULL,
      customer_name VARCHAR(255) NULL,
      birthday DATE NULL,
      job VARCHAR(255) NULL,
      phone VARCHAR(100) NULL,
      address TEXT NULL,
      nail_shape VARCHAR(50) NULL,
      checked_json TEXT NULL,
      signature_data LONGTEXT NULL,
      signed_at DATETIME NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");

  if ($customerId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = [
      'customer_name' => trim($_POST['customer_name'] ?? ''),
      'birthday' => trim($_POST['birthday'] ?? ''),
      'job' => trim($_POST['job'] ?? ''),
      'phone' => trim($_POST['phone'] ?? ''),
      'address' => trim($_POST['address'] ?? ''),
      'nail_shape' => trim($_POST['nail_shape'] ?? ''),
      'checked' => $_POST['checked'] ?? [],
      'signature_data' => $_POST['signature_data'] ?? '',
    ];

    if ($posted['customer_name'] === '') {
      $invalidFields[] = 'customer_name';
    }

    if ($posted['birthday'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $posted['birthday'])) {
      $invalidFields[] = 'birthday';
    }

    foreach (array_keys($checkItems) as $key) {
      if (!in_array($key, $posted['checked'], true)) {
        $invalidChecks[] = $key;
      }
    }

    if (strlen($posted['signature_data']) < 100) {
      $invalidFields[] = 'signature_data';
    }

    if ($invalidFields || $invalidChecks) {
      $messages = [];
      if (in_array('customer_name', $invalidFields, true)) $messages[] = 'お名前';
      if (in_array('birthday', $invalidFields, true)) $messages[] = 'お誕生日';
      if ($invalidChecks) $messages[] = '未チェック項目';
      if (in_array('signature_data', $invalidFields, true)) $messages[] = '署名';
      throw new Exception('入力漏れがあります：' . implode(' / ', $messages));
    }

    $customerName = $posted['customer_name'];
    $birthday = $posted['birthday'];
    $job = $posted['job'];
    $phone = $posted['phone'];
    $address = $posted['address'];
    $nailShape = $posted['nail_shape'];
    $checked = $posted['checked'];
    $signatureData = $posted['signature_data'];

    $customerCols = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_ASSOC));

    if ($customerId <= 0) {
      $found = null;

      if ($phone !== '' && in_array('phone', $customerCols, true)) {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE phone = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$phone]);
        $found = $stmt->fetch(PDO::FETCH_ASSOC);
      }

      if (!$found && in_array('name', $customerCols, true)) {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE name = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$customerName]);
        $found = $stmt->fetch(PDO::FETCH_ASSOC);
      }

      if ($found) {
        $customerId = (int)$found['id'];
      }
    }

    if ($customerId > 0) {
      $sets = [];
      $params = [];

      foreach ([
        'name' => $customerName,
        'phone' => $phone,
        'birthday' => $birthday,
        'birthdate' => $birthday,
        'job' => $job,
        'occupation' => $job,
        'address' => $address,
      ] as $col => $val) {
        if (in_array($col, $customerCols, true) && $val !== '') {
          $sets[] = "`{$col}` = ?";
          $params[] = $val;
        }
      }

      if (in_array('updated_at', $customerCols, true)) {
        $sets[] = "updated_at = NOW()";
      }

      if ($sets) {
        $params[] = $customerId;
        $stmt = $pdo->prepare("UPDATE customers SET " . implode(',', $sets) . " WHERE id = ?");
        $stmt->execute($params);
      }
    } else {
      $insert = [];
      $values = [];
      $params = [];

      foreach ([
        'name' => $customerName,
        'phone' => $phone,
        'birthday' => $birthday,
        'birthdate' => $birthday,
        'job' => $job,
        'occupation' => $job,
        'address' => $address,
        'created_at' => '__NOW__',
        'updated_at' => '__NOW__',
      ] as $col => $val) {
        if (in_array($col, $customerCols, true) && ($val !== '' || $val === '__NOW__')) {
          $insert[] = "`{$col}`";
          if ($val === '__NOW__') {
            $values[] = "NOW()";
          } else {
            $values[] = "?";
            $params[] = $val;
          }
        }
      }

      if (!$insert || !in_array('name', $customerCols, true)) {
        throw new Exception('customersテーブルにname列がないため、顧客連動できません。');
      }

      $stmt = $pdo->prepare("INSERT INTO customers (" . implode(',', $insert) . ") VALUES (" . implode(',', $values) . ")");
      $stmt->execute($params);
      $customerId = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("
      INSERT INTO consent_form_signatures
        (customer_id, customer_name, birthday, job, phone, address, nail_shape, checked_json, signature_data, signed_at, created_at)
      VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
      $customerId,
      $customerName,
      $birthday ?: null,
      $job,
      $phone,
      $address,
      $nailShape,
      json_encode(array_values($checked), JSON_UNESCAPED_UNICODE),
      $signatureData
    ]);

    try {
      $body = "Nail Chart ご利用規約に同意済み\n署名日：" . date('Y-m-d H:i:s');
      $stmt = $pdo->prepare("
        INSERT INTO consent_forms
          (customer_id, title, body, signature_data, signed_at, created_at)
        VALUES
          (?, 'Nail Chart ご利用規約', ?, ?, NOW(), NOW())
      ");
      $stmt->execute([$customerId, $body, $signatureData]);
    } catch (Throwable $e) {}

    $saved = true;

    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
  }

  if ($customerId > 0) {
    $stmt = $pdo->prepare("
      SELECT *
      FROM consent_form_signatures
      WHERE customer_id = ?
      ORDER BY id DESC
      LIMIT 1
    ");
    $stmt->execute([$customerId]);
    $latestConsent = $stmt->fetch(PDO::FETCH_ASSOC);
  }
} catch (Throwable $e) {
  $error = $e->getMessage();
}

$defaultName = $_SERVER['REQUEST_METHOD'] === 'POST' ? $posted['customer_name'] : ($customer['name'] ?? '');
$defaultPhone = $_SERVER['REQUEST_METHOD'] === 'POST' ? $posted['phone'] : ($customer['phone'] ?? '');
$defaultBirthday = $_SERVER['REQUEST_METHOD'] === 'POST' ? $posted['birthday'] : ($customer['birthday'] ?? $customer['birthdate'] ?? '');
$defaultBirthday = preg_match('/^\d{4}-\d{2}-\d{2}/', (string)$defaultBirthday) ? substr((string)$defaultBirthday, 0, 10) : '';
$defaultJob = $_SERVER['REQUEST_METHOD'] === 'POST' ? $posted['job'] : '';
$defaultAddress = $_SERVER['REQUEST_METHOD'] === 'POST' ? $posted['address'] : '';
$defaultShape = $_SERVER['REQUEST_METHOD'] === 'POST' ? $posted['nail_shape'] : '';
$defaultChecked = $_SERVER['REQUEST_METHOD'] === 'POST' ? $posted['checked'] : [];
$defaultSignature = $_SERVER['REQUEST_METHOD'] === 'POST' ? $posted['signature_data'] : '';

function is_invalid_field(string $field, array $invalidFields): string {
  return in_array($field, $invalidFields, true) ? ' is-invalid' : '';
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nail Consent | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=86">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=86">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=86">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=86">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=8">
  <link rel="stylesheet" href="../assets/css/consent-sign.css?v=3">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main consent-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">DIGITAL CONSENT</p>
        <h1>Nail Chart</h1>
        <p class="consent-lead">入力漏れがあっても内容は保持し、未入力箇所だけ強調表示します。</p>
      </div>
      <div class="pro-actions">
        <a class="pro-button" href="./consent-pro.php">Consent Pro</a>
        <?php if ($customerId): ?><a class="pro-button primary" href="./customer-360.php?id=<?= (int)$customerId ?>">Customer 360</a><?php endif; ?>
      </div>
    </header>

    <?php if ($saved): ?>
      <section class="os-panel success">
        同意書を保存し、顧客名簿へ連動しました。
        <?php if ($customerId): ?>
          <a href="./customer-360.php?id=<?= (int)$customerId ?>" style="color:#fff;text-decoration:underline;">Customer360で確認</a>
        <?php endif; ?>
      </section>
    <?php endif; ?>
    <?php if ($error): ?><section class="os-panel consent-error"><?= htmlspecialchars($error) ?></section><?php endif; ?>

    <form method="post" class="consent-sheet" id="consentForm" novalidate>
      <section class="consent-paper">
        <div class="nail-title">
          <span>Nail</span>
          <span>Chart</span>
        </div>

        <div class="consent-profile-grid">
          <label class="<?= is_invalid_field('customer_name', $invalidFields) ?>">お名前<input name="customer_name" value="<?= htmlspecialchars($defaultName) ?>" required></label>
          <label class="<?= is_invalid_field('birthday', $invalidFields) ?>">お誕生日<input type="date" name="birthday" value="<?= htmlspecialchars($defaultBirthday) ?>"></label>
          <label>職業<input name="job" value="<?= htmlspecialchars($defaultJob) ?>"></label>
          <label>電話番号<input name="phone" value="<?= htmlspecialchars($defaultPhone) ?>"></label>
        </div>

        <div class="shape-area">
          <p>ご希望の爪の形</p>
          <div class="shape-list">
            <?php foreach ($nailShapes as $shape): ?>
              <label>
                <input type="radio" name="nail_shape" value="<?= htmlspecialchars($shape) ?>" <?= $defaultShape === $shape ? 'checked' : '' ?>>
                <span><?= htmlspecialchars($shape) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <label class="address-field">ご住所<input name="address" value="<?= htmlspecialchars($defaultAddress) ?>"></label>

        <h2>ご利用規約</h2>
        <p class="intro">お客様に安心して施術を受けていただくために以下の規約を定めております。ご署名のご協力をお願い致します。</p>

        <h3>施術前の注意点について</h3>
        <div class="check-list">
          <?php foreach ($checkItems as $key => $text): ?>
            <label class="check-row <?= in_array($key, $invalidChecks, true) ? 'is-invalid-check' : '' ?>">
              <input type="checkbox" name="checked[]" value="<?= htmlspecialchars($key) ?>" <?= in_array($key, $defaultChecked, true) ? 'checked' : '' ?>>
              <span class="fake-check"></span>
              <em><?= htmlspecialchars($text) ?></em>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="signature-section <?= is_invalid_field('signature_data', $invalidFields) ?>">
          <h3>ご署名</h3>
          <p>下記の枠内にApple Pencilまたは指でご署名ください。</p>
          <canvas id="signaturePad" width="900" height="260" data-existing-signature="<?= htmlspecialchars($defaultSignature) ?>"></canvas>
          <input type="hidden" name="signature_data" id="signatureData" value="<?= htmlspecialchars($defaultSignature) ?>">
          <div class="signature-actions">
            <button type="button" id="clearSignature">署名をクリア</button>
          </div>
        </div>

        <button class="save-consent-button" type="submit">同意書を保存して顧客名簿へ連動する</button>
      </section>

      <aside class="consent-side os-panel">
        <p class="eyebrow">STATUS</p>
        <h2>確認状況</h2>
        <div class="status-list">
          <article><span>チェック</span><strong id="checkedCount">0 / <?= count($checkItems) ?></strong></article>
          <article><span>署名</span><strong id="signatureStatus"><?= $defaultSignature ? '署名済み' : '未署名' ?></strong></article>
          <article><span>顧客連動</span><strong><?= $customerId ? '既存/連動中' : '新規作成' ?></strong></article>
          <article><span>保存</span><strong><?= $latestConsent ? date('Y/m/d', strtotime($latestConsent['created_at'])) : '未保存' ?></strong></article>
        </div>
      </aside>
    </form>
  </main>

  <script src="../assets/js/consent-sign.js?v=3"></script>
</body>
</html>
