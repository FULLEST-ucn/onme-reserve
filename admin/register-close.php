<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$error = '';
$opened = false;
$closed = false;
$reopened = false;

$businessDate = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $businessDate)) {
  $businessDate = date('Y-m-d');
}

function ensure_register_schema(PDO $pdo): void {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS pos_sales (
      id INT AUTO_INCREMENT PRIMARY KEY,
      reservation_id INT NULL,
      customer_id INT NULL,
      staff_id INT NULL,
      subtotal INT NOT NULL DEFAULT 0,
      discount INT NOT NULL DEFAULT 0,
      total INT NOT NULL DEFAULT 0,
      payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
      deposit_amount INT NOT NULL DEFAULT 0,
      change_amount INT NOT NULL DEFAULT 0,
      note TEXT NULL,
      status VARCHAR(50) NOT NULL DEFAULT 'paid',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_pos_sales_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS register_sessions (
      id INT AUTO_INCREMENT PRIMARY KEY,
      business_date DATE NOT NULL,
      opened_by INT NULL,
      closed_by INT NULL,
      opening_cash INT NOT NULL DEFAULT 0,
      expected_cash INT NOT NULL DEFAULT 0,
      actual_cash INT NULL,
      cash_difference INT NULL,
      cash_10000 INT NOT NULL DEFAULT 0,
      cash_5000 INT NOT NULL DEFAULT 0,
      cash_1000 INT NOT NULL DEFAULT 0,
      cash_500 INT NOT NULL DEFAULT 0,
      cash_100 INT NOT NULL DEFAULT 0,
      cash_50 INT NOT NULL DEFAULT 0,
      cash_10 INT NOT NULL DEFAULT 0,
      cash_5 INT NOT NULL DEFAULT 0,
      cash_1 INT NOT NULL DEFAULT 0,
      gross_sales INT NOT NULL DEFAULT 0,
      net_sales INT NOT NULL DEFAULT 0,
      tax_amount INT NOT NULL DEFAULT 0,
      payment_cash INT NOT NULL DEFAULT 0,
      payment_card INT NOT NULL DEFAULT 0,
      payment_qr INT NOT NULL DEFAULT 0,
      payment_other INT NOT NULL DEFAULT 0,
      checkout_count INT NOT NULL DEFAULT 0,
      customer_count INT NOT NULL DEFAULT 0,
      average_spend INT NOT NULL DEFAULT 0,
      status VARCHAR(50) NOT NULL DEFAULT 'open',
      difference_reason VARCHAR(100) NULL,
      memo TEXT NULL,
      reopened_at DATETIME NULL,
      opened_at DATETIME NULL,
      closed_at DATETIME NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uniq_register_business_date (business_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");

  $cols = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM register_sessions")->fetchAll(PDO::FETCH_ASSOC));
  $add = [
    'cash_10000' => "ALTER TABLE register_sessions ADD COLUMN cash_10000 INT NOT NULL DEFAULT 0 AFTER cash_difference",
    'cash_5000' => "ALTER TABLE register_sessions ADD COLUMN cash_5000 INT NOT NULL DEFAULT 0 AFTER cash_10000",
    'cash_1000' => "ALTER TABLE register_sessions ADD COLUMN cash_1000 INT NOT NULL DEFAULT 0 AFTER cash_5000",
    'cash_500' => "ALTER TABLE register_sessions ADD COLUMN cash_500 INT NOT NULL DEFAULT 0 AFTER cash_1000",
    'cash_100' => "ALTER TABLE register_sessions ADD COLUMN cash_100 INT NOT NULL DEFAULT 0 AFTER cash_500",
    'cash_50' => "ALTER TABLE register_sessions ADD COLUMN cash_50 INT NOT NULL DEFAULT 0 AFTER cash_100",
    'cash_10' => "ALTER TABLE register_sessions ADD COLUMN cash_10 INT NOT NULL DEFAULT 0 AFTER cash_50",
    'cash_5' => "ALTER TABLE register_sessions ADD COLUMN cash_5 INT NOT NULL DEFAULT 0 AFTER cash_10",
    'cash_1' => "ALTER TABLE register_sessions ADD COLUMN cash_1 INT NOT NULL DEFAULT 0 AFTER cash_5",
    'reopened_at' => "ALTER TABLE register_sessions ADD COLUMN reopened_at DATETIME NULL AFTER memo",
  ];
  foreach ($add as $col => $sql) {
    if (!in_array($col, $cols, true)) {
      $pdo->exec($sql);
    }
  }
}

function payment_label(string $method): string {
  return match($method) {
    'cash' => '現金',
    'card' => 'クレジット',
    'qr' => 'QR決済',
    'paypay' => 'QR決済',
    default => 'その他',
  };
}

function yen(int $v): string {
  $sign = $v < 0 ? '-' : '';
  return $sign . '¥' . number_format(abs($v));
}

try {
  $pdo = db();
  ensure_register_schema($pdo);

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'open') {
      $openingCash = (int)($_POST['opening_cash'] ?? 0);
      $stmt = $pdo->prepare("
        INSERT INTO register_sessions
          (business_date, opened_by, opening_cash, expected_cash, status, opened_at, created_at, updated_at)
        VALUES
          (?, ?, ?, ?, 'open', NOW(), NOW(), NOW())
        ON DUPLICATE KEY UPDATE
          opened_by = VALUES(opened_by),
          opening_cash = VALUES(opening_cash),
          expected_cash = VALUES(expected_cash),
          status = 'open',
          actual_cash = NULL,
          cash_difference = NULL,
          closed_at = NULL,
          updated_at = NOW()
      ");
      $stmt->execute([
        $businessDate,
        (int)($_SESSION['staff_id'] ?? $_SESSION['user_id'] ?? 0) ?: null,
        $openingCash,
        $openingCash
      ]);
      $opened = true;
    }

    if ($action === 'reopen') {
      $stmt = $pdo->prepare("
        UPDATE register_sessions
        SET status = 'open',
            actual_cash = NULL,
            cash_difference = NULL,
            difference_reason = NULL,
            memo = CONCAT(COALESCE(memo,''), '\n【締め解除】', DATE_FORMAT(NOW(), '%Y/%m/%d %H:%i')),
            reopened_at = NOW(),
            closed_at = NULL,
            updated_at = NOW()
        WHERE business_date = ?
      ");
      $stmt->execute([$businessDate]);
      $reopened = true;
    }

    if ($action === 'close') {
      $denoms = [
        10000 => (int)($_POST['cash_10000'] ?? 0),
        5000  => (int)($_POST['cash_5000'] ?? 0),
        1000  => (int)($_POST['cash_1000'] ?? 0),
        500   => (int)($_POST['cash_500'] ?? 0),
        100   => (int)($_POST['cash_100'] ?? 0),
        50    => (int)($_POST['cash_50'] ?? 0),
        10    => (int)($_POST['cash_10'] ?? 0),
        5     => (int)($_POST['cash_5'] ?? 0),
        1     => (int)($_POST['cash_1'] ?? 0),
      ];
      $actualCash = 0;
      foreach ($denoms as $unit => $qty) {
        $actualCash += $unit * max(0, $qty);
      }

      $reason = trim($_POST['difference_reason'] ?? '');
      $memo = trim($_POST['memo'] ?? '');

      $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total),0) AS gross, COUNT(*) AS checkout_count, COUNT(DISTINCT customer_id) AS customer_count
        FROM pos_sales
        WHERE DATE(created_at) = ? AND COALESCE(status,'paid') <> 'void'
      ");
      $stmt->execute([$businessDate]);
      $sum = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

      $stmt = $pdo->prepare("
        SELECT payment_method, COALESCE(SUM(total),0) AS amount
        FROM pos_sales
        WHERE DATE(created_at) = ? AND COALESCE(status,'paid') <> 'void'
        GROUP BY payment_method
      ");
      $stmt->execute([$businessDate]);
      $payments = ['cash'=>0,'card'=>0,'qr'=>0,'other'=>0];
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $m = $p['payment_method'] ?? 'other';
        if ($m === 'paypay') $m = 'qr';
        if (!isset($payments[$m])) $m = 'other';
        $payments[$m] += (int)$p['amount'];
      }

      $gross = (int)($sum['gross'] ?? 0);
      $net = (int)floor($gross / 1.1);
      $tax = $gross - $net;
      $count = (int)($sum['checkout_count'] ?? 0);
      $customerCount = (int)($sum['customer_count'] ?? 0);
      $avg = $count ? (int)floor($net / $count) : 0;

      $stmt = $pdo->prepare("SELECT opening_cash FROM register_sessions WHERE business_date = ? LIMIT 1");
      $stmt->execute([$businessDate]);
      $openingCash = (int)($stmt->fetchColumn() ?: 0);
      $expectedCash = $openingCash + $payments['cash'];
      $diff = $actualCash - $expectedCash;

      $stmt = $pdo->prepare("
        INSERT INTO register_sessions
          (business_date, opened_by, closed_by, opening_cash, expected_cash, actual_cash, cash_difference,
           cash_10000, cash_5000, cash_1000, cash_500, cash_100, cash_50, cash_10, cash_5, cash_1,
           gross_sales, net_sales, tax_amount, payment_cash, payment_card, payment_qr, payment_other,
           checkout_count, customer_count, average_spend, status, difference_reason, memo, opened_at, closed_at, created_at, updated_at)
        VALUES
          (?, ?, ?, ?, ?, ?, ?,
           ?, ?, ?, ?, ?, ?, ?, ?, ?,
           ?, ?, ?, ?, ?, ?, ?,
           ?, ?, ?, 'closed', ?, ?, COALESCE((SELECT x.opened_at FROM (SELECT opened_at FROM register_sessions WHERE business_date = ?) x), NOW()), NOW(), NOW(), NOW())
        ON DUPLICATE KEY UPDATE
          closed_by = VALUES(closed_by),
          expected_cash = VALUES(expected_cash),
          actual_cash = VALUES(actual_cash),
          cash_difference = VALUES(cash_difference),
          cash_10000 = VALUES(cash_10000),
          cash_5000 = VALUES(cash_5000),
          cash_1000 = VALUES(cash_1000),
          cash_500 = VALUES(cash_500),
          cash_100 = VALUES(cash_100),
          cash_50 = VALUES(cash_50),
          cash_10 = VALUES(cash_10),
          cash_5 = VALUES(cash_5),
          cash_1 = VALUES(cash_1),
          gross_sales = VALUES(gross_sales),
          net_sales = VALUES(net_sales),
          tax_amount = VALUES(tax_amount),
          payment_cash = VALUES(payment_cash),
          payment_card = VALUES(payment_card),
          payment_qr = VALUES(payment_qr),
          payment_other = VALUES(payment_other),
          checkout_count = VALUES(checkout_count),
          customer_count = VALUES(customer_count),
          average_spend = VALUES(average_spend),
          status = 'closed',
          difference_reason = VALUES(difference_reason),
          memo = VALUES(memo),
          closed_at = NOW(),
          updated_at = NOW()
      ");
      $stmt->execute([
        $businessDate,
        (int)($_SESSION['staff_id'] ?? $_SESSION['user_id'] ?? 0) ?: null,
        (int)($_SESSION['staff_id'] ?? $_SESSION['user_id'] ?? 0) ?: null,
        $openingCash,
        $expectedCash,
        $actualCash,
        $diff,
        $denoms[10000], $denoms[5000], $denoms[1000], $denoms[500], $denoms[100], $denoms[50], $denoms[10], $denoms[5], $denoms[1],
        $gross, $net, $tax,
        $payments['cash'], $payments['card'], $payments['qr'], $payments['other'],
        $count, $customerCount, $avg,
        $reason, $memo, $businessDate
      ]);
      $closed = true;
    }
  }

  $stmt = $pdo->prepare("SELECT * FROM register_sessions WHERE business_date = ? LIMIT 1");
  $stmt->execute([$businessDate]);
  $session = $stmt->fetch(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total),0) AS gross, COUNT(*) AS checkout_count, COUNT(DISTINCT customer_id) AS customer_count
    FROM pos_sales
    WHERE DATE(created_at) = ? AND COALESCE(status,'paid') <> 'void'
  ");
  $stmt->execute([$businessDate]);
  $sum = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

  $stmt = $pdo->prepare("
    SELECT payment_method, COALESCE(SUM(total),0) AS amount, COUNT(*) AS count
    FROM pos_sales
    WHERE DATE(created_at) = ? AND COALESCE(status,'paid') <> 'void'
    GROUP BY payment_method
  ");
  $stmt->execute([$businessDate]);
  $paymentRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $payments = ['cash'=>0,'card'=>0,'qr'=>0,'other'=>0];
  $paymentCounts = ['cash'=>0,'card'=>0,'qr'=>0,'other'=>0];
  foreach ($paymentRows as $p) {
    $m = $p['payment_method'] ?? 'other';
    if ($m === 'paypay') $m = 'qr';
    if (!isset($payments[$m])) $m = 'other';
    $payments[$m] += (int)$p['amount'];
    $paymentCounts[$m] += (int)$p['count'];
  }

  $gross = (int)($sum['gross'] ?? 0);
  $net = (int)floor($gross / 1.1);
  $tax = $gross - $net;
  $count = (int)($sum['checkout_count'] ?? 0);
  $customerCount = (int)($sum['customer_count'] ?? 0);
  $avg = $count ? (int)floor($net / $count) : 0;

  $openingCash = (int)($session['opening_cash'] ?? 0);
  $expectedCash = $openingCash + $payments['cash'];
  $status = $session['status'] ?? 'not_open';

  $stmt = $pdo->prepare("
    SELECT ps.*, c.name AS customer_name, s.name AS staff_name
    FROM pos_sales ps
    LEFT JOIN customers c ON c.id = ps.customer_id
    LEFT JOIN staffs s ON s.id = ps.staff_id
    WHERE DATE(ps.created_at) = ?
    ORDER BY ps.id DESC
  ");
  $stmt->execute([$businessDate]);
  $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
  $error = $e->getMessage();
  $session = null;
  $payments = ['cash'=>0,'card'=>0,'qr'=>0,'other'=>0];
  $paymentCounts = ['cash'=>0,'card'=>0,'qr'=>0,'other'=>0];
  $gross = $net = $tax = $count = $customerCount = $avg = $openingCash = $expectedCash = 0;
  $status = 'not_open';
  $sales = [];
}

$isClosed = $status === 'closed';
$denomValues = [
  'cash_10000' => ['value' => 10000, 'label' => '10,000円札'],
  'cash_5000' => ['value' => 5000, 'label' => '5,000円札'],
  'cash_1000' => ['value' => 1000, 'label' => '1,000円札'],
  'cash_500' => ['value' => 500, 'label' => '500円玉'],
  'cash_100' => ['value' => 100, 'label' => '100円玉'],
  'cash_50' => ['value' => 50, 'label' => '50円玉'],
  'cash_10' => ['value' => 10, 'label' => '10円玉'],
  'cash_5' => ['value' => 5, 'label' => '5円玉'],
  'cash_1' => ['value' => 1, 'label' => '1円玉'],
];
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>レジ締め | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=100">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=100">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=100">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=100">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=8">
  <link rel="stylesheet" href="../assets/css/register-close.css?v=3">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">REGISTER CLOSE</p>
        <h1>レジ締め</h1>
      </div>
      <form method="get" class="date-form">
        <input type="date" name="date" value="<?= htmlspecialchars($businessDate) ?>">
        <button>表示</button>
      </form>
    </header>

    <?php if ($opened): ?><section class="os-panel success">営業開始しました。</section><?php endif; ?>
    <?php if ($closed): ?><section class="os-panel success">レジ締めが完了しました。</section><?php endif; ?>
    <?php if ($reopened): ?><section class="os-panel success">締め済み状態を解除しました。</section><?php endif; ?>
    <?php if ($error): ?><section class="os-panel register-error"><?= htmlspecialchars($error) ?></section><?php endif; ?>

    <section class="register-status <?= $isClosed ? 'is-closed' : '' ?>">
      <div><span>営業日</span><strong><?= htmlspecialchars(date('Y/m/d', strtotime($businessDate))) ?></strong></div>
      <div><span>状態</span><strong><?= $isClosed ? '締め済み' : ($status === 'open' ? '営業中' : '未開始') ?></strong></div>
      <div><span>開始釣銭</span><strong><?= yen($openingCash) ?></strong></div>
      <div><span>理論現金</span><strong><?= yen($expectedCash) ?></strong></div>
    </section>

    <?php if ($isClosed): ?>
      <section class="os-panel reopen-panel">
        <div>
          <p class="eyebrow">REOPEN</p>
          <h2>締め解除</h2>
          <p>誤ってレジ締めした場合、営業中状態に戻して再度レジ締めできます。</p>
        </div>
        <form method="post" onsubmit="return confirm('締め済み状態を解除しますか？\n再度レジ締めが必要になります。');">
          <input type="hidden" name="action" value="reopen">
          <button>締め解除する</button>
        </form>
      </section>
    <?php endif; ?>

    <?php if ($status === 'not_open'): ?>
      <section class="os-panel open-panel">
        <p class="eyebrow">OPEN</p>
        <h2>営業開始</h2>
        <form method="post" class="open-form">
          <input type="hidden" name="action" value="open">
          <label>
            開始釣銭
            <input type="number" name="opening_cash" value="30000" min="0">
          </label>
          <button>営業開始</button>
        </form>
      </section>
    <?php endif; ?>

    <section class="register-kpi">
      <article><span>純売上（税抜）</span><strong><?= yen($net) ?></strong><em>税込 <?= yen($gross) ?></em></article>
      <article><span>消費税</span><strong><?= yen($tax) ?></strong><em>10%内税</em></article>
      <article><span>会計件数</span><strong><?= number_format($count) ?></strong><em>VOID除外</em></article>
      <article><span>客数</span><strong><?= number_format($customerCount) ?></strong><em>顧客連動ベース</em></article>
      <article><span>客単価</span><strong><?= yen($avg) ?></strong><em>税抜</em></article>
    </section>

    <section class="register-layout">
      <article class="os-panel payment-panel">
        <p class="eyebrow">PAYMENT</p>
        <h2>支払方法別</h2>

        <div class="payment-list">
          <?php foreach (['cash'=>'現金','card'=>'クレジット','qr'=>'QR決済','other'=>'その他'] as $key => $label): ?>
            <?php $amount = (int)($payments[$key] ?? 0); $netAmount = (int)floor($amount / 1.1); ?>
            <article>
              <div>
                <strong><?= htmlspecialchars($label) ?></strong>
                <span><?= number_format((int)($paymentCounts[$key] ?? 0)) ?>件</span>
              </div>
              <div>
                <b><?= yen($netAmount) ?></b>
                <em>税込 <?= yen($amount) ?></em>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="os-panel close-panel">
        <p class="eyebrow">CASH COUNT</p>
        <h2>キャッシュカウント</h2>

        <?php if ($isClosed): ?>
          <div class="closed-summary">
            <article><span>実残高</span><strong><?= yen((int)($session['actual_cash'] ?? 0)) ?></strong></article>
            <article><span>差異</span><strong class="<?= ((int)($session['cash_difference'] ?? 0)) === 0 ? '' : 'diff-bad' ?>"><?= yen((int)($session['cash_difference'] ?? 0)) ?></strong></article>
            <article><span>理由</span><strong><?= htmlspecialchars($session['difference_reason'] ?? '-') ?></strong></article>
            <article><span>メモ</span><strong><?= nl2br(htmlspecialchars($session['memo'] ?? '-')) ?></strong></article>
          </div>
          <div class="denom-readonly">
            <?php foreach ($denomValues as $name => $meta): ?>
              <?php $value = (int)$meta['value']; ?>
              <article>
                <span><?= htmlspecialchars($meta['label']) ?></span>
                <strong><?= number_format((int)($session[$name] ?? 0)) ?>枚</strong>
                <em><?= yen($value * (int)($session[$name] ?? 0)) ?></em>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <form method="post" class="close-form" id="closeForm">
            <input type="hidden" name="action" value="close">
            <input type="hidden" id="expectedCashValue" value="<?= (int)$expectedCash ?>">

            <div class="denomination-list">
              <?php foreach ($denomValues as $name => $meta): ?>
                <label class="denom-row">
                  <span><?= htmlspecialchars($meta['label']) ?></span>
                  <div class="denom-control">
                    <button type="button" class="denom-minus">−</button>
                    <input type="number" name="<?= htmlspecialchars($name) ?>" class="denom-input" data-value="<?= (int)$meta['value'] ?>" value="0" min="0">
                    <button type="button" class="denom-plus">＋</button>
                  </div>
                  <em class="denom-total">¥0</em>
                </label>
              <?php endforeach; ?>
            </div>

            <div class="cash-total-box">
              <article><span>実残高</span><strong id="actualCashText">¥0</strong></article>
              <article><span>理論現金</span><strong><?= yen($expectedCash) ?></strong></article>
              <article><span>差異</span><strong id="cashDiffText">¥0</strong></article>
            </div>

            <label>
              差異理由
              <select name="difference_reason">
                <option value="">差異なし</option>
                <option value="釣銭ミス">釣銭ミス</option>
                <option value="返金">返金</option>
                <option value="会計修正">会計修正</option>
                <option value="その他">その他</option>
              </select>
            </label>

            <label>
              メモ
              <textarea name="memo" rows="4" placeholder="差異理由や共有事項"></textarea>
            </label>

            <button type="submit" onclick="return confirm('本日のレジ締めを完了しますか？');">レジ締めする</button>
          </form>
        <?php endif; ?>
      </article>

      <article class="os-panel sales-panel">
        <p class="eyebrow">TODAY SALES</p>
        <h2>会計履歴</h2>

        <div class="sales-list">
          <?php if (!$sales): ?><p class="muted-text">会計データがありません。</p><?php endif; ?>

          <?php foreach ($sales as $s): ?>
            <?php $isVoid = ($s['status'] ?? 'paid') === 'void'; ?>
            <article class="<?= $isVoid ? 'is-void' : '' ?>">
              <div>
                <strong><?= $isVoid ? 'VOID ' : '' ?><?= yen((int)$s['total']) ?></strong>
                <span><?= htmlspecialchars(payment_label($s['payment_method'] ?? 'other')) ?> / <?= htmlspecialchars($s['customer_name'] ?? '顧客未選択') ?></span>
              </div>
              <time><?= htmlspecialchars(date('H:i', strtotime($s['created_at']))) ?></time>
            </article>
          <?php endforeach; ?>
        </div>
      </article>
    </section>
  </main>

  <script src="../assets/js/register-close.js?v=3"></script>
</body>
</html>
