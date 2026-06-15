<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$customerId = (int)($_GET['id'] ?? 0);
$error = '';
$customer = null;
$customerList = [];
$reservations = [];
$notes = [];
$photos = [];
$points = [];
$subscriptions = [];
$consents = [];
$signatureConsents = [];
$stats = [
  'visits' => 0,
  'ltv' => 0,
  'avg' => 0,
  'last_visit' => null,
  'next_reservation' => null,
  'points' => 0,
];

try {
  $pdo = db();

  if ($customerId <= 0) throw new Exception('顧客IDが不正です。');

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'note') {
      $body = trim($_POST['body'] ?? '');
      if ($body !== '') {
        $pdo->exec("
          CREATE TABLE IF NOT EXISTS customer_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            note TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $stmt = $pdo->prepare("INSERT INTO customer_notes (customer_id, note, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$customerId, $body]);
      }
    }
    if ($action === 'profile') {
      $name = trim($_POST['name'] ?? '');
      $phone = trim($_POST['phone'] ?? '');
      $email = trim($_POST['email'] ?? '');
      $memo = trim($_POST['memo'] ?? '');
      $cols = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_ASSOC));
      $sets=[]; $params=[];
      foreach (['name'=>$name,'phone'=>$phone,'email'=>$email,'memo'=>$memo,'note'=>$memo] as $col=>$val) {
        if (in_array($col,$cols,true)) { $sets[]="`{$col}`=?"; $params[]=$val; }
      }
      if (in_array('updated_at',$cols,true)) $sets[]="updated_at=NOW()";
      if ($sets) {
        $params[]=$customerId;
        $stmt=$pdo->prepare("UPDATE customers SET ".implode(',',$sets)." WHERE id=?");
        $stmt->execute($params);
      }
    }
  }

  $customerList = $pdo->query("SELECT id, name, phone FROM customers ORDER BY id DESC LIMIT 300")->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
  $stmt->execute([$customerId]);
  $customer = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$customer) throw new Exception('顧客が見つかりません。');

  $stmt = $pdo->prepare("
    SELECT r.*, m.name AS menu_name, m.price, s.name AS staff_name
    FROM reservations r
    LEFT JOIN menus m ON m.id = r.menu_id
    LEFT JOIN staffs s ON s.id = r.staff_id
    WHERE r.customer_id = ?
      AND r.status IN ('reserved','confirmed','completed')
    ORDER BY r.start_datetime DESC
    LIMIT 30
  ");
  $stmt->execute([$customerId]);
  $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($reservations as $r) {
    $stats['visits']++;
    $stats['ltv'] += (int)($r['price'] ?? 0);
    if (strtotime($r['start_datetime']) <= time()) {
      if (!$stats['last_visit'] || strtotime($r['start_datetime']) > strtotime($stats['last_visit'])) $stats['last_visit'] = $r['start_datetime'];
    }
    if (strtotime($r['start_datetime']) > time()) {
      if (!$stats['next_reservation'] || strtotime($r['start_datetime']) < strtotime($stats['next_reservation'])) $stats['next_reservation'] = $r['start_datetime'];
    }
  }
  $stats['avg'] = $stats['visits'] ? floor($stats['ltv'] / $stats['visits']) : 0;

  try { $stmt=$pdo->prepare("SELECT * FROM customer_notes WHERE customer_id=? ORDER BY created_at DESC LIMIT 20"); $stmt->execute([$customerId]); $notes=$stmt->fetchAll(PDO::FETCH_ASSOC); } catch(Throwable $e){}
  try { $stmt=$pdo->prepare("SELECT * FROM customer_photos WHERE customer_id=? ORDER BY id DESC LIMIT 12"); $stmt->execute([$customerId]); $photos=$stmt->fetchAll(PDO::FETCH_ASSOC); } catch(Throwable $e){}
  try { $stmt=$pdo->prepare("SELECT * FROM customer_points WHERE customer_id=? ORDER BY created_at DESC LIMIT 20"); $stmt->execute([$customerId]); $points=$stmt->fetchAll(PDO::FETCH_ASSOC); foreach($points as $p) $stats['points']+=(int)($p['points']??0); } catch(Throwable $e){}
  try { $stmt=$pdo->prepare("SELECT * FROM customer_subscriptions WHERE customer_id=? ORDER BY id DESC LIMIT 5"); $stmt->execute([$customerId]); $subscriptions=$stmt->fetchAll(PDO::FETCH_ASSOC); } catch(Throwable $e){}
  try { $stmt=$pdo->prepare("SELECT * FROM consent_forms WHERE customer_id=? ORDER BY id DESC LIMIT 8"); $stmt->execute([$customerId]); $consents=$stmt->fetchAll(PDO::FETCH_ASSOC); } catch(Throwable $e){}
  try { $stmt=$pdo->prepare("SELECT * FROM consent_form_signatures WHERE customer_id=? ORDER BY id DESC LIMIT 8"); $stmt->execute([$customerId]); $signatureConsents=$stmt->fetchAll(PDO::FETCH_ASSOC); } catch(Throwable $e){}

} catch (Throwable $e) {
  $error = $e->getMessage();
}

$rank = 'NORMAL';
if ($stats['ltv'] >= 100000) $rank = 'VIP';
if ($stats['ltv'] >= 200000) $rank = 'ROYAL';

$customerName = trim((string)($customer['name'] ?? 'お客様'));
$displayName = preg_match('/様$/u', $customerName) ? $customerName : $customerName . '様';
$latestSignature = $signatureConsents[0] ?? null;

$aiSummary = [
  '好み' => 'シンプル・上品系を優先',
  '来店周期' => $stats['visits'] >= 2 ? '約3〜4週間周期' : 'データ蓄積中',
  '提案' => '次回は前回デザインの色替え提案がおすすめ',
  '注意' => '会話メモと施術履歴を確認して接客',
];

function checked_labels($json) {
  $map = [
    'skin'=>'皮膚疾患などの申告',
    'medicine'=>'薬品・紫外線・アレルギー',
    'condition'=>'爪・身体状態による施術可否',
    'accident'=>'不足事態への確認',
    'guarantee'=>'保証期間',
    'remove'=>'無理な除去禁止',
    'privacy'=>'個人情報取扱い',
    'understand'=>'内容理解',
  ];
  $arr = json_decode((string)$json, true);
  if (!is_array($arr)) return [];
  $out = [];
  foreach ($arr as $k) $out[] = $map[$k] ?? $k;
  return $out;
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Customer 360 | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=87">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=87">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=87">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=87">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=8">
  <link rel="stylesheet" href="../assets/css/customer360-v2.css?v=1">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <?php if ($error): ?>
      <section class="os-panel"><?= htmlspecialchars($error) ?></section>
    <?php else: ?>
      <header class="customer-hero">
        <div>
          <p class="eyebrow">CUSTOMER BEAUTY RECORD</p>
          <h1><?= htmlspecialchars($displayName) ?></h1>
          <div class="customer-badges">
            <span><?= htmlspecialchars($rank) ?></span>
            <span>来店 <?= number_format($stats['visits']) ?>回</span>
            <span>LTV ¥<?= number_format($stats['ltv']) ?></span>
          </div>
        </div>
        <div class="customer-hero-actions">
          <label class="customer-switcher">
            <span>顧客切替</span>
            <select onchange="if(this.value){ location.href='./customer-360.php?id=' + this.value; }">
              <?php foreach ($customerList as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $customerId ? 'selected' : '' ?>>
                  <?= htmlspecialchars(($c['name'] ?? '-') . (!empty($c['phone']) ? ' / ' . $c['phone'] : '')) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
          <a href="./calendar-master.php" class="pro-button">予約確認</a>
          <a href="./pos-pro.php" class="pro-button primary">会計へ</a>
        </div>
      </header>

      <section class="customer-kpi">
        <article><span>累計売上</span><strong>¥<?= number_format($stats['ltv']) ?></strong></article>
        <article><span>平均単価</span><strong>¥<?= number_format($stats['avg']) ?></strong></article>
        <article><span>ポイント</span><strong><?= number_format($stats['points']) ?>pt</strong></article>
        <article><span>最終来店</span><strong><?= $stats['last_visit'] ? date('m/d', strtotime($stats['last_visit'])) : '-' ?></strong></article>
      </section>

      <section class="customer360-grid">
        <article class="os-panel profile-panel">
          <p class="eyebrow">PROFILE</p>
          <h2>顧客情報</h2>
          <form method="post" class="profile-form">
            <input type="hidden" name="action" value="profile">
            <label>名前<input name="name" value="<?= htmlspecialchars($customer['name'] ?? '') ?>"></label>
            <label>電話<input name="phone" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>"></label>
            <label>メール<input name="email" value="<?= htmlspecialchars($customer['email'] ?? '') ?>"></label>
            <label>メモ<textarea name="memo" rows="4"><?= htmlspecialchars($customer['memo'] ?? $customer['note'] ?? '') ?></textarea></label>
            <button>保存</button>
          </form>
        </article>

        <article class="os-panel ai-panel">
          <p class="eyebrow">AI SUMMARY</p>
          <h2>AIカルテ</h2>
          <div class="ai-list">
            <?php foreach ($aiSummary as $key => $value): ?>
              <div><span><?= htmlspecialchars($key) ?></span><strong><?= htmlspecialchars($value) ?></strong></div>
            <?php endforeach; ?>
          </div>
        </article>

        <article class="os-panel timeline-panel">
          <p class="eyebrow">TIMELINE</p>
          <h2>来店タイムライン</h2>
          <div class="timeline-list">
            <?php if (!$reservations): ?><p class="muted-text">来店履歴がありません。</p><?php endif; ?>
            <?php foreach ($reservations as $r): ?>
              <article class="timeline-card">
                <time><?= htmlspecialchars(date('Y/m/d H:i', strtotime($r['start_datetime']))) ?></time>
                <div>
                  <strong><?= htmlspecialchars($r['menu_name'] ?? 'メニュー未設定') ?></strong>
                  <span>担当：<?= htmlspecialchars($r['staff_name'] ?? '-') ?> / ¥<?= number_format((int)($r['price'] ?? 0)) ?></span>
                  <?php if (!empty($r['note'])): ?><p><?= htmlspecialchars($r['note']) ?></p><?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </article>

        <article class="os-panel note-panel">
          <p class="eyebrow">CONVERSATION</p>
          <h2>会話メモ</h2>
          <form method="post" class="note-form">
            <input type="hidden" name="action" value="note">
            <textarea name="body" rows="4" placeholder="例）旅行予定、好きな色、注意事項など"></textarea>
            <button>メモ追加</button>
          </form>
          <div class="note-list">
            <?php if (!$notes): ?><p class="muted-text">会話メモがありません。</p><?php endif; ?>
            <?php foreach ($notes as $n): ?>
              <article><strong><?= htmlspecialchars(date('Y/m/d', strtotime($n['created_at']))) ?></strong><p><?= nl2br(htmlspecialchars($n['note'] ?? $n['body'] ?? '')) ?></p></article>
            <?php endforeach; ?>
          </div>
        </article>

        <article class="os-panel photo-panel">
          <p class="eyebrow">PHOTOS</p>
          <h2>施術写真</h2>
          <div class="photo-grid">
            <?php if (!$photos): ?>
              <?php for ($i = 0; $i < 8; $i++): ?><div class="photo-placeholder">PHOTO</div><?php endfor; ?>
            <?php endif; ?>
            <?php foreach ($photos as $p): ?>
              <?php $src = $p['file_path'] ?? $p['path'] ?? $p['url'] ?? ''; ?>
              <?php if ($src): ?><img src="<?= htmlspecialchars($src) ?>" alt="photo"><?php endif; ?>
            <?php endforeach; ?>
          </div>
        </article>

        <article class="os-panel side-panel">
          <p class="eyebrow">NEXT ACTION</p>
          <h2>AI提案</h2>
          <div class="action-list">
            <article><span>次回来店予測</span><strong><?= $stats['next_reservation'] ? date('m/d H:i', strtotime($stats['next_reservation'])) : '3〜4週間後' ?></strong></article>
            <article><span>おすすめ</span><strong>前回デザインの色替え</strong></article>
            <article><span>物販提案</span><strong>ネイルオイル</strong></article>
            <article><span>LINE</span><strong>来店後サンクス配信</strong></article>
          </div>
        </article>

        <article class="os-panel mini-panel">
          <p class="eyebrow">SUBSCRIPTION</p>
          <h2>サブスク</h2>
          <div class="mini-list">
            <?php if (!$subscriptions): ?><p class="muted-text">契約なし</p><?php endif; ?>
            <?php foreach ($subscriptions as $s): ?>
              <article><strong><?= htmlspecialchars($s['plan_name'] ?? '-') ?></strong><span>¥<?= number_format((int)($s['amount'] ?? 0)) ?> / <?= htmlspecialchars($s['status'] ?? '-') ?></span></article>
            <?php endforeach; ?>
          </div>
        </article>

        <article class="os-panel mini-panel consent-mini-panel">
          <p class="eyebrow">CONSENT</p>
          <h2>同意書</h2>
          <?php if (!$latestSignature): ?>
            <div class="consent-empty">
              <strong>未同意</strong>
              <span>署名済み同意書がありません。</span>
              <a href="./consent-sign.php?customer_id=<?= (int)$customerId ?>">同意書を作成</a>
            </div>
          <?php else: ?>
            <div class="consent-status-card">
              <strong>同意済み</strong>
              <span><?= htmlspecialchars(date('Y/m/d H:i', strtotime($latestSignature['signed_at'] ?? $latestSignature['created_at']))) ?></span>
              <button type="button" id="openConsentModal">同意書を見る</button>
            </div>
          <?php endif; ?>
        </article>
      </section>

      <?php if ($latestSignature): ?>
        <div class="consent-modal-backdrop" id="consentModal" hidden>
          <div class="consent-modal">
            <div class="consent-modal-head">
              <div>
                <p class="eyebrow">SIGNED CONSENT</p>
                <h2>Nail Chart</h2>
              </div>
              <button type="button" id="closeConsentModal">×</button>
            </div>

            <div class="signed-consent-paper">
              <section class="signed-profile">
                <article><span>お名前</span><strong><?= htmlspecialchars($latestSignature['customer_name'] ?? $customer['name'] ?? '-') ?></strong></article>
                <article><span>お誕生日</span><strong><?= htmlspecialchars($latestSignature['birthday'] ?? '-') ?></strong></article>
                <article><span>職業</span><strong><?= htmlspecialchars($latestSignature['job'] ?? '-') ?></strong></article>
                <article><span>電話番号</span><strong><?= htmlspecialchars($latestSignature['phone'] ?? '-') ?></strong></article>
                <article class="wide"><span>ご住所</span><strong><?= htmlspecialchars($latestSignature['address'] ?? '-') ?></strong></article>
                <article><span>爪の形</span><strong><?= htmlspecialchars($latestSignature['nail_shape'] ?? '-') ?></strong></article>
                <article><span>署名日時</span><strong><?= htmlspecialchars(date('Y/m/d H:i', strtotime($latestSignature['signed_at'] ?? $latestSignature['created_at']))) ?></strong></article>
              </section>

              <section class="signed-checks">
                <h3>確認済み項目</h3>
                <?php foreach (checked_labels($latestSignature['checked_json'] ?? '') as $label): ?>
                  <span>✓ <?= htmlspecialchars($label) ?></span>
                <?php endforeach; ?>
              </section>

              <section class="signed-signature">
                <h3>ご署名</h3>
                <img src="<?= htmlspecialchars($latestSignature['signature_data'] ?? '') ?>" alt="signature">
              </section>
            </div>

            <div class="consent-modal-actions">
              <button type="button" onclick="window.print()">印刷</button>
              <a href="./consent-sign.php?customer_id=<?= (int)$customerId ?>">新しい同意書を作成</a>
            </div>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </main>

  <script src="../assets/js/customer360-consent.js?v=1"></script>
</body>
</html>
