<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
<<<<<<< HEAD

$error = '';
$saved = false;

function ensure_consent_schema(PDO $pdo): void {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS consent_forms (
      id INT AUTO_INCREMENT PRIMARY KEY,
      customer_id INT NULL,
      title VARCHAR(255) NOT NULL,
      body TEXT NOT NULL,
      signature_data LONGTEXT NULL,
      signed_at DATETIME NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_consent_forms_customer_id (customer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");

  $cols = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM consent_forms")->fetchAll(PDO::FETCH_ASSOC));
  $add = [
    'customer_id' => "ALTER TABLE consent_forms ADD COLUMN customer_id INT NULL AFTER id",
    'title' => "ALTER TABLE consent_forms ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT '同意書' AFTER customer_id",
    'body' => "ALTER TABLE consent_forms ADD COLUMN body TEXT NULL AFTER title",
    'signature_data' => "ALTER TABLE consent_forms ADD COLUMN signature_data LONGTEXT NULL AFTER body",
    'signed_at' => "ALTER TABLE consent_forms ADD COLUMN signed_at DATETIME NULL AFTER signature_data",
    'created_at' => "ALTER TABLE consent_forms ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
    'updated_at' => "ALTER TABLE consent_forms ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
  ];
  foreach ($add as $col => $sql) {
    if (!in_array($col, $cols, true)) $pdo->exec($sql);
  }
}

try {
  $pdo = db();
  ensure_consent_schema($pdo);

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_template') {
      $title = trim($_POST['title'] ?? '');
      $body = trim($_POST['body'] ?? '');
      if ($title === '') throw new Exception('同意書タイトルを入力してください。');
      if ($body === '') throw new Exception('同意書本文を入力してください。');

      $stmt = $pdo->prepare("
        INSERT INTO consent_forms (customer_id, title, body, created_at, updated_at)
        VALUES (NULL, ?, ?, NOW(), NOW())
      ");
      $stmt->execute([$title, $body]);
      $saved = true;
    }
  }

  $customers = [];
  try {
    $customers = $pdo->query("
      SELECT id, name, phone
      FROM customers
      WHERE COALESCE(is_active, 1) = 1
      ORDER BY id DESC
      LIMIT 300
    ")->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {}

  $forms = $pdo->query("
    SELECT
      cf.*,
      c.name AS customer_name,
      c.phone AS customer_phone
    FROM consent_forms cf
    LEFT JOIN customers c ON c.id = cf.customer_id
    ORDER BY cf.id DESC
    LIMIT 300
  ")->fetchAll(PDO::FETCH_ASSOC);

  $signedCount = 0;
  $unsignedCount = 0;
  foreach ($forms as $f) {
    if (!empty($f['signature_data']) || !empty($f['signed_at'])) $signedCount++;
    else $unsignedCount++;
  }

} catch (Throwable $e) {
  $error = $e->getMessage();
  $forms = [];
  $customers = [];
  $signedCount = 0;
  $unsignedCount = 0;
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>同意書 | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=104">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=104">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=104">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=104">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=8">
  <link rel="stylesheet" href="../assets/css/consent-pro-view.css?v=1">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">CONSENT</p>
        <h1>同意書</h1>
      </div>
      <div class="pro-actions">
        <a class="pro-button primary" href="./carte-master.php">お客様名簿</a>
      </div>
    </header>

    <?php if ($saved): ?><section class="os-panel success">保存しました。</section><?php endif; ?>
    <?php if ($error): ?><section class="os-panel consent-error"><?= htmlspecialchars($error) ?></section><?php endif; ?>

    <section class="consent-kpi">
      <article><span>同意書数</span><strong><?= number_format(count($forms)) ?></strong></article>
      <article><span>署名済み</span><strong><?= number_format($signedCount) ?></strong></article>
      <article><span>未署名</span><strong><?= number_format($unsignedCount) ?></strong></article>
    </section>

    <section class="consent-layout">
      <form method="post" class="os-panel consent-form">
        <input type="hidden" name="action" value="create_template">
        <p class="eyebrow">CREATE</p>
        <h2>同意書作成</h2>

        <label>
          タイトル
          <input type="text" name="title" placeholder="例）ネイル施術同意書">
        </label>

        <label>
          本文
          <textarea name="body" rows="14" placeholder="同意書の内容を入力してください。"></textarea>
        </label>

        <button type="submit">同意書を保存</button>
      </form>

      <section class="os-panel consent-list-panel">
        <p class="eyebrow">LIST</p>
        <h2>同意書一覧</h2>

        <div class="consent-list">
          <?php if (!$forms): ?>
            <p class="muted-text">同意書がありません。</p>
          <?php endif; ?>

          <?php foreach ($forms as $f): ?>
            <?php
              $isSigned = !empty($f['signature_data']) || !empty($f['signed_at']);
              $body = (string)($f['body'] ?? '');
            ?>
            <button
              type="button"
              class="consent-row"
              data-title="<?= htmlspecialchars($f['title'] ?? '同意書') ?>"
              data-customer="<?= htmlspecialchars($f['customer_name'] ?? 'テンプレート') ?>"
              data-phone="<?= htmlspecialchars($f['customer_phone'] ?? '') ?>"
              data-body="<?= htmlspecialchars($body) ?>"
              data-signed-at="<?= htmlspecialchars(!empty($f['signed_at']) ? date('Y/m/d H:i', strtotime($f['signed_at'])) : '') ?>"
              data-signature="<?= htmlspecialchars($f['signature_data'] ?? '') ?>"
              data-created="<?= htmlspecialchars(!empty($f['created_at']) ? date('Y/m/d H:i', strtotime($f['created_at'])) : '') ?>"
            >
              <div>
                <strong><?= htmlspecialchars($f['title'] ?? '同意書') ?></strong>
                <span>
                  <?= htmlspecialchars($f['customer_name'] ?? 'テンプレート') ?>
                  <?= !empty($f['customer_phone']) ? ' / ' . htmlspecialchars($f['customer_phone']) : '' ?>
                </span>
              </div>
              <em class="<?= $isSigned ? 'signed' : '' ?>">
                <?= $isSigned ? '署名済み' : '未署名' ?>
              </em>
            </button>
          <?php endforeach; ?>
        </div>
      </section>
    </section>
  </main>

  <div class="consent-modal" id="consentModal" hidden>
    <section class="consent-modal-box">
      <div class="consent-modal-head">
        <div>
          <p class="eyebrow">DETAIL</p>
          <h2 id="modalTitle">同意書</h2>
          <span id="modalMeta"></span>
        </div>
        <button type="button" id="closeConsentModal">×</button>
      </div>

      <article class="consent-body" id="modalBody"></article>

      <section class="signature-preview" id="signaturePreview" hidden>
        <p>署名</p>
        <img id="modalSignature" alt="署名">
      </section>

      <div class="modal-foot">
        <span id="modalDate"></span>
        <button type="button" onclick="window.print()">印刷</button>
      </div>
    </section>
  </div>

  <script src="../assets/js/consent-pro-view.js?v=1"></script>
</body>
</html>
=======
$saved=false;
try{
  $pdo=db();
  if($_SERVER['REQUEST_METHOD']==='POST'){
    $stmt=$pdo->prepare("INSERT INTO consent_forms (customer_id,title,body,created_at) VALUES (?,?,?,NOW())");
    $stmt->execute([(int)$_POST['customer_id'], trim($_POST['title']), trim($_POST['body'])]);
    $saved=true;
  }
  $customers=$pdo->query("SELECT id,name,phone FROM customers ORDER BY updated_at DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
  $forms=$pdo->query("SELECT f.*, c.name customer_name FROM consent_forms f LEFT JOIN customers c ON c.id=f.customer_id ORDER BY f.created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $e){$error=$e->getMessage();$customers=[];$forms=[];}
?>
<!doctype html><html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Consent Pro</title>
<link rel="stylesheet" href="../assets/css/admin-pro.css?v=40"><link rel="stylesheet" href="../assets/css/os-pro.css?v=40"><link rel="stylesheet" href="../assets/css/suite40.css?v=40"></head>
<body class="pro-body"><aside class="pro-sidebar"><div class="brand"><small>ON;ME OS</small><strong>Consent</strong></div><nav><a href="./dashboard-v2.php">Dashboard</a><a class="active" href="./consent-pro.php">Consent</a></nav></aside>
<main class="pro-main"><header class="pro-top"><div><p class="eyebrow">DIGITAL SIGN</p><h1>Consent Pro</h1></div></header>
<?php if($saved): ?><section class="os-panel success">同意書を作成しました。</section><?php endif; ?>
<section class="os-two"><section class="os-panel"><h2>同意書作成</h2><form method="post" class="os-form"><label>顧客<select name="customer_id"><?php foreach($customers as $c): ?><option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></label><label>タイトル<input name="title" value="施術同意書"></label><label>本文<textarea name="body" rows="8">施術に関する注意事項を確認し、同意します。</textarea></label><button>作成</button></form></section>
<section class="os-panel"><h2>作成済み</h2><div class="os-list"><?php foreach($forms as $f): ?><article class="os-list-item"><strong><?= htmlspecialchars($f['title']) ?></strong><span><?= htmlspecialchars($f['customer_name']??'-') ?> / <?= date('Y/m/d',strtotime($f['created_at'])) ?></span></article><?php endforeach; ?></div></section></section>
</main></body></html>
>>>>>>> 77d34561990733cc5fd0dc12c5c67a39e3e638ab
