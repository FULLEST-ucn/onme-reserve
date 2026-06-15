<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$error = '';
$saved = false;
$month = $_GET['month'] ?? date('Y-m');
$detailDate = $_GET['date'] ?? '';
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
if ($detailDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $detailDate)) $detailDate = '';

$start = $month . '-01';
$end = date('Y-m-t', strtotime($start));

function ensure_sales_history_schema(PDO $pdo): void {
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

  $cols = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM pos_sales")->fetchAll(PDO::FETCH_ASSOC));
  $add = [
    'reservation_id' => "ALTER TABLE pos_sales ADD COLUMN reservation_id INT NULL AFTER id",
    'customer_id' => "ALTER TABLE pos_sales ADD COLUMN customer_id INT NULL AFTER reservation_id",
    'staff_id' => "ALTER TABLE pos_sales ADD COLUMN staff_id INT NULL AFTER customer_id",
    'subtotal' => "ALTER TABLE pos_sales ADD COLUMN subtotal INT NOT NULL DEFAULT 0",
    'discount' => "ALTER TABLE pos_sales ADD COLUMN discount INT NOT NULL DEFAULT 0",
    'total' => "ALTER TABLE pos_sales ADD COLUMN total INT NOT NULL DEFAULT 0",
    'payment_method' => "ALTER TABLE pos_sales ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'cash'",
    'deposit_amount' => "ALTER TABLE pos_sales ADD COLUMN deposit_amount INT NOT NULL DEFAULT 0",
    'change_amount' => "ALTER TABLE pos_sales ADD COLUMN change_amount INT NOT NULL DEFAULT 0",
    'note' => "ALTER TABLE pos_sales ADD COLUMN note TEXT NULL",
    'status' => "ALTER TABLE pos_sales ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'paid'",
    'created_at' => "ALTER TABLE pos_sales ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
    'updated_at' => "ALTER TABLE pos_sales ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
  ];
  foreach ($add as $col => $sql) {
    if (!in_array($col, $cols, true)) $pdo->exec($sql);
  }

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
}

function yen(int $v): string {
  $sign = $v < 0 ? '-' : '';
  return $sign . '¥' . number_format(abs($v));
}
function net_of(int $gross): int { return (int)floor($gross / 1.1); }
function pay_label(string $m): string {
  return match($m) {
    'cash' => '現金',
    'card' => 'クレジット',
    'qr', 'paypay' => 'QR決済',
    default => 'その他',
  };
}

try {
  $pdo = db();
  ensure_sales_history_schema($pdo);

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_sale') {
      $saleId = (int)($_POST['sale_id'] ?? 0);
      $customerId = (int)($_POST['customer_id'] ?? 0);
      $staffId = (int)($_POST['staff_id'] ?? 0);
      $subtotal = (int)($_POST['subtotal'] ?? 0);
      $discount = (int)($_POST['discount'] ?? 0);
      $refundAmount = (int)($_POST['refund_amount'] ?? 0);
      $payment = trim($_POST['payment_method'] ?? 'cash');
      $deposit = (int)($_POST['deposit_amount'] ?? 0);
      $note = trim($_POST['note'] ?? '');
      $status = trim($_POST['status'] ?? 'paid');

      if ($saleId <= 0) throw new Exception('会計IDが不正です。');

      // VOIDは全額売上除外。部分返金は返金額分を売上から差し引いた金額で保存します。
      if ($status === 'void') {
        $total = 0;
        if ($note === '' || mb_strpos($note, 'VOID') === false) {
          $note = trim($note . "
VOID/全額返金扱い");
        }
      } else {
        $total = max(0, $subtotal - $discount - max(0, $refundAmount));
        if ($refundAmount > 0) {
          $note = trim($note . "
一部返金：¥" . number_format($refundAmount));
        }
      }

      $change = $payment === 'cash' ? max(0, $deposit - $total) : 0;

      $stmt = $pdo->prepare("
        UPDATE pos_sales
        SET customer_id = ?, staff_id = ?, subtotal = ?, discount = ?, total = ?, payment_method = ?,
            deposit_amount = ?, change_amount = ?, note = ?, status = ?, updated_at = NOW()
        WHERE id = ?
      ");
      $stmt->execute([$customerId ?: null, $staffId ?: null, $subtotal, $discount, $total, $payment, $deposit, $change, $note, $status, $saleId]);
      $saved = true;
      $detailDate = $_POST['detail_date'] ?? $detailDate;
      if ($detailDate) $month = substr($detailDate, 0, 7);
      $start = $month . '-01';
      $end = date('Y-m-t', strtotime($start));
    }

    if ($action === 'reopen_register') {
      $date = $_POST['detail_date'] ?? '';
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new Exception('営業日が不正です。');
      $stmt = $pdo->prepare("
        UPDATE register_sessions
        SET status='open', closed_at=NULL, reopened_at=NOW(), updated_at=NOW()
        WHERE business_date=?
      ");
      $stmt->execute([$date]);
      $saved = true;
      $detailDate = $date;
      $month = substr($date, 0, 7);
      $start = $month . '-01';
      $end = date('Y-m-t', strtotime($start));
    }
  }

  $customers = $pdo->query("SELECT id, name, phone FROM customers WHERE COALESCE(is_active,1)=1 ORDER BY id DESC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
  $staffs = $pdo->query("SELECT id, name FROM staffs WHERE COALESCE(is_active,1)=1 ORDER BY id ASC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total),0) gross, COUNT(*) cnt, COUNT(DISTINCT customer_id) customers
    FROM pos_sales
    WHERE DATE(created_at) BETWEEN ? AND ? AND COALESCE(status,'paid') <> 'void'
  ");
  $stmt->execute([$start, $end]);
  $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['gross'=>0,'cnt'=>0,'customers'=>0];
  $monthGross = (int)$summary['gross'];
  $monthNet = net_of($monthGross);
  $monthTax = $monthGross - $monthNet;
  $monthCount = (int)$summary['cnt'];
  $monthCustomers = (int)$summary['customers'];
  $monthAvg = $monthCount ? (int)floor($monthNet / $monthCount) : 0;

  $stmt = $pdo->prepare("
    SELECT DATE(created_at) sales_date, COALESCE(SUM(total),0) gross, COUNT(*) cnt, COUNT(DISTINCT customer_id) customers
    FROM pos_sales
    WHERE DATE(created_at) BETWEEN ? AND ? AND COALESCE(status,'paid') <> 'void'
    GROUP BY DATE(created_at)
    ORDER BY sales_date DESC
  ");
  $stmt->execute([$start, $end]);
  $dailyRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT COALESCE(m.name, 'その他/予約なし') name, COALESCE(SUM(ps.total),0) gross, COUNT(ps.id) cnt
    FROM pos_sales ps
    LEFT JOIN reservations r ON r.id = ps.reservation_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(ps.created_at) BETWEEN ? AND ? AND COALESCE(ps.status,'paid') <> 'void'
    GROUP BY m.id, m.name
    ORDER BY gross DESC
    LIMIT 10
  ");
  $stmt->execute([$start, $end]);
  $menuRanking = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT COALESCE(s.name,'未設定') name, COALESCE(SUM(ps.total),0) gross, COUNT(ps.id) cnt
    FROM pos_sales ps
    LEFT JOIN staffs s ON s.id = ps.staff_id
    WHERE DATE(ps.created_at) BETWEEN ? AND ? AND COALESCE(ps.status,'paid') <> 'void'
    GROUP BY ps.staff_id, s.name
    ORDER BY gross DESC
    LIMIT 10
  ");
  $stmt->execute([$start, $end]);
  $staffRanking = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT HOUR(created_at) h, COALESCE(SUM(total),0) gross, COUNT(*) cnt
    FROM pos_sales
    WHERE DATE(created_at) BETWEEN ? AND ? AND COALESCE(status,'paid') <> 'void'
    GROUP BY HOUR(created_at)
    ORDER BY h
  ");
  $stmt->execute([$start, $end]);
  $hourRowsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $timeBuckets = [
    '10:00〜12:00' => ['gross'=>0,'cnt'=>0],
    '12:00〜15:00' => ['gross'=>0,'cnt'=>0],
    '15:00〜18:00' => ['gross'=>0,'cnt'=>0],
    '18:00〜' => ['gross'=>0,'cnt'=>0],
  ];
  foreach ($hourRowsRaw as $hr) {
    $h = (int)$hr['h'];
    $bucket = $h < 12 ? '10:00〜12:00' : ($h < 15 ? '12:00〜15:00' : ($h < 18 ? '15:00〜18:00' : '18:00〜'));
    $timeBuckets[$bucket]['gross'] += (int)$hr['gross'];
    $timeBuckets[$bucket]['cnt'] += (int)$hr['cnt'];
  }

  $stmt = $pdo->prepare("
    SELECT customer_id, COUNT(*) cnt
    FROM pos_sales
    WHERE DATE(created_at) BETWEEN ? AND ? AND COALESCE(status,'paid') <> 'void' AND customer_id IS NOT NULL
    GROUP BY customer_id
  ");
  $stmt->execute([$start, $end]);
  $custPurchaseRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $new = $repeat = $loyal = 0;
  foreach ($custPurchaseRows as $cr) {
    $c = (int)$cr['cnt'];
    if ($c >= 4) $loyal++;
    elseif ($c >= 2) $repeat++;
    else $new++;
  }
  $custTotal = max(1, $new + $repeat + $loyal);

  $detail = null;
  $detailSales = [];
  $detailPayments = ['cash'=>0,'card'=>0,'qr'=>0,'other'=>0];
  $detailStaffs = [];
  $detailMenus = [];
  $detailTime = [];
  if ($detailDate) {
    $stmt = $pdo->prepare("SELECT * FROM register_sessions WHERE business_date=? LIMIT 1");
    $stmt->execute([$detailDate]);
    $detail = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
      SELECT ps.*, c.name customer_name, c.phone customer_phone, s.name staff_name
      FROM pos_sales ps
      LEFT JOIN customers c ON c.id=ps.customer_id
      LEFT JOIN staffs s ON s.id=ps.staff_id
      WHERE DATE(ps.created_at)=?
      ORDER BY ps.id DESC
    ");
    $stmt->execute([$detailDate]);
    $detailSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($detailSales as $s) {
      if (($s['status'] ?? 'paid') === 'void') continue;
      $m = $s['payment_method'] ?? 'other';
      if ($m === 'paypay') $m = 'qr';
      if (!isset($detailPayments[$m])) $m = 'other';
      $detailPayments[$m] += (int)$s['total'];
    }
  }
} catch (Throwable $e) {
  $error = $e->getMessage();
  $customers = $staffs = $dailyRows = $menuRanking = $staffRanking = $detailSales = [];
  $timeBuckets = [];
  $new = $repeat = $loyal = 0; $custTotal = 1;
  $monthGross = $monthNet = $monthTax = $monthCount = $monthCustomers = $monthAvg = 0;
  $detail = null; $detailPayments = ['cash'=>0,'card'=>0,'qr'=>0,'other'=>0];
}

$menuValues = array_values(array_map(fn($r)=>(int)($r['gross'] ?? 0), $menuRanking ?: [['gross'=>1]]));
$staffValues = array_values(array_map(fn($r)=>(int)($r['gross'] ?? 0), $staffRanking ?: [['gross'=>1]]));
$timeValues = array_values(array_map(fn($r)=>(int)($r['gross'] ?? 0), $timeBuckets ?: [['gross'=>1]]));

$maxMenu = max(1, ...$menuValues);
$maxStaff = max(1, ...$staffValues);
$maxTime = max(1, ...$timeValues);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>過去売上 | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=102">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=102">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=102">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=102">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=8">
  <link rel="stylesheet" href="../assets/css/sales-history.css?v=1">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">SALES HISTORY</p>
        <h1>過去売上</h1>
      </div>
      <form method="get" class="sales-filter">
        <input type="month" name="month" value="<?= htmlspecialchars($month) ?>">
        <button>表示</button>
      </form>
    </header>

    <?php if ($saved): ?><section class="os-panel success">保存しました。</section><?php endif; ?>
    <?php if ($error): ?><section class="os-panel sales-error"><?= htmlspecialchars($error) ?></section><?php endif; ?>

    <section class="sales-kpi">
      <article><span>純売上（税抜）</span><strong><?= yen($monthNet) ?></strong><em>税込 <?= yen($monthGross) ?></em></article>
      <article><span>会計数</span><strong><?= number_format($monthCount) ?></strong><em>VOID除外</em></article>
      <article><span>客数</span><strong><?= number_format($monthCustomers) ?></strong><em>顧客連動</em></article>
      <article><span>客単価</span><strong><?= yen($monthAvg) ?></strong><em>税抜</em></article>
      <article><span>消費税</span><strong><?= yen($monthTax) ?></strong><em>10%内税</em></article>
    </section>

    <section class="sales-layout">
      <article class="os-panel">
        <p class="eyebrow">DAILY</p>
        <h2>日別売上</h2>
        <div class="daily-list">
          <?php if (!$dailyRows): ?><p class="muted-text">データがありません。</p><?php endif; ?>
          <?php foreach ($dailyRows as $d): ?>
            <?php $gross=(int)$d['gross']; $net=net_of($gross); $avg=((int)$d['cnt'])?floor($net/(int)$d['cnt']):0; ?>
            <a href="./sales-history.php?month=<?= htmlspecialchars($month) ?>&date=<?= htmlspecialchars($d['sales_date']) ?>#detail">
              <time><?= htmlspecialchars(date('m/d', strtotime($d['sales_date']))) ?></time>
              <strong><?= yen($net) ?></strong>
              <span>税込 <?= yen($gross) ?> / <?= (int)$d['cnt'] ?>件 / 客単価 <?= yen((int)$avg) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="os-panel">
        <p class="eyebrow">MENU TOP</p>
        <h2>メニュー別売上</h2>
        <div class="rank-bars">
          <?php foreach ($menuRanking as $r): $gross=(int)$r['gross']; $net=net_of($gross); ?>
            <article>
              <div><strong><?= htmlspecialchars($r['name']) ?></strong><span><?= (int)$r['cnt'] ?>件 / 税込 <?= yen($gross) ?></span></div>
              <i style="width:<?= max(3, $gross/$maxMenu*100) ?>%"></i>
              <b><?= yen($net) ?></b>
            </article>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="os-panel">
        <p class="eyebrow">STAFF RANKING</p>
        <h2>スタッフランキング</h2>
        <div class="rank-bars">
          <?php foreach ($staffRanking as $i=>$r): $gross=(int)$r['gross']; $net=net_of($gross); ?>
            <article>
              <div><strong><?= $i+1 ?>. <?= htmlspecialchars($r['name']) ?></strong><span><?= (int)$r['cnt'] ?>件 / 税込 <?= yen($gross) ?></span></div>
              <i style="width:<?= max(3, $gross/$maxStaff*100) ?>%"></i>
              <b><?= yen($net) ?></b>
            </article>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="os-panel">
        <p class="eyebrow">TIME ANALYSIS</p>
        <h2>時間帯分析</h2>
        <div class="rank-bars">
          <?php foreach ($timeBuckets as $label=>$r): $gross=(int)$r['gross']; $net=net_of($gross); ?>
            <article>
              <div><strong><?= htmlspecialchars($label) ?></strong><span><?= (int)$r['cnt'] ?>件 / 税込 <?= yen($gross) ?></span></div>
              <i style="width:<?= max(3, $gross/$maxTime*100) ?>%"></i>
              <b><?= yen($net) ?></b>
            </article>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="os-panel">
        <p class="eyebrow">REPEAT</p>
        <h2>リピーター分析</h2>
        <div class="repeat-grid">
          <article><span>新規</span><strong><?= round($new/$custTotal*100) ?>%</strong><em><?= $new ?>名</em></article>
          <article><span>再来</span><strong><?= round($repeat/$custTotal*100) ?>%</strong><em><?= $repeat ?>名</em></article>
          <article><span>固定客</span><strong><?= round($loyal/$custTotal*100) ?>%</strong><em><?= $loyal ?>名</em></article>
        </div>
      </article>
    </section>

    <?php if ($detailDate): ?>
      <section class="os-panel detail-panel" id="detail">
        <div class="detail-head">
          <div>
            <p class="eyebrow">DETAIL</p>
            <h2><?= htmlspecialchars(date('Y/m/d', strtotime($detailDate))) ?> 売上詳細</h2>
          </div>
          <div class="detail-actions">
            <button onclick="window.print()">印刷</button>
            <?php if ($detail && (($detail['status'] ?? '') === 'closed')): ?>
              <form method="post" onsubmit="return confirm('この日のレジ締めを取消しますか？');">
                <input type="hidden" name="action" value="reopen_register">
                <input type="hidden" name="detail_date" value="<?= htmlspecialchars($detailDate) ?>">
                <button class="danger">レジ締め取消</button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <?php
          $dgross = 0; $dcnt=0; $dcustomers=[];
          foreach ($detailSales as $s) {
            if (($s['status'] ?? 'paid') !== 'void') {
              $dgross += (int)$s['total']; $dcnt++;
              if (!empty($s['customer_id'])) $dcustomers[$s['customer_id']] = true;
            }
          }
          $dnet=net_of($dgross); $dtax=$dgross-$dnet; $davg=$dcnt?floor($dnet/$dcnt):0;
        ?>
        <section class="detail-kpi">
          <article><span>純売上</span><strong><?= yen($dnet) ?></strong><em>税込 <?= yen($dgross) ?></em></article>
          <article><span>消費税</span><strong><?= yen($dtax) ?></strong></article>
          <article><span>件数</span><strong><?= $dcnt ?></strong></article>
          <article><span>客単価</span><strong><?= yen((int)$davg) ?></strong></article>
        </section>

        <section class="payment-detail">
          <?php foreach (['cash'=>'現金','card'=>'クレジット','qr'=>'QR決済','other'=>'その他'] as $key=>$label): ?>
            <?php $gross=(int)($detailPayments[$key] ?? 0); ?>
            <article><span><?= $label ?></span><strong><?= yen(net_of($gross)) ?></strong><em>税込 <?= yen($gross) ?></em></article>
          <?php endforeach; ?>
        </section>

        <?php if ($detail): ?>
          <section class="cash-detail">
            <article><span>POS現金残高</span><strong><?= yen((int)($detail['expected_cash'] ?? 0)) ?></strong></article>
            <article><span>実在高</span><strong><?= yen((int)($detail['actual_cash'] ?? 0)) ?></strong></article>
            <article><span>差異</span><strong><?= yen((int)($detail['cash_difference'] ?? 0)) ?></strong></article>
          </section>
        <?php endif; ?>

        <h3>会計履歴</h3>
        <div class="history-sales-list">
          <?php if (!$detailSales): ?>
            <section class="os-panel">この日の会計履歴がありません。</section>
          <?php endif; ?>
          <?php foreach ($detailSales as $s): ?>
            <button
              type="button"
              class="history-sale-row"
              data-sale-id="<?= (int)$s['id'] ?>"
              data-customer-id="<?= (int)($s['customer_id'] ?? 0) ?>"
              data-staff-id="<?= (int)($s['staff_id'] ?? 0) ?>"
              data-subtotal="<?= (int)($s['subtotal'] ?? 0) ?>"
              data-discount="<?= (int)($s['discount'] ?? 0) ?>"
              data-total="<?= (int)($s['total'] ?? 0) ?>"
              data-payment="<?= htmlspecialchars($s['payment_method'] ?? 'cash') ?>"
              data-deposit="<?= (int)($s['deposit_amount'] ?? 0) ?>"
              data-note="<?= htmlspecialchars($s['note'] ?? '') ?>"
              data-status="<?= htmlspecialchars($s['status'] ?? 'paid') ?>"
            >
              <strong><?= (($s['status'] ?? 'paid') === 'void' ? 'VOID ' : '') ?><?= yen((int)$s['total']) ?></strong>
              <span><?= htmlspecialchars($s['customer_name'] ?? '顧客未選択') ?> / <?= htmlspecialchars(pay_label($s['payment_method'] ?? 'other')) ?> / <?= htmlspecialchars(date('H:i', strtotime($s['created_at']))) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <div class="sale-edit-modal" id="saleEditModal" hidden>
    <form method="post" class="sale-edit-box">
      <div class="modal-head">
        <div><p class="eyebrow">EDIT SALE</p><h2>会計修正</h2></div>
        <button type="button" id="closeSaleModal">×</button>
      </div>

      <input type="hidden" name="action" value="update_sale">
      <input type="hidden" name="sale_id" id="editSaleId">
      <input type="hidden" name="detail_date" value="<?= htmlspecialchars($detailDate) ?>">

      <label>顧客
        <select name="customer_id" id="editCustomerId">
          <option value="0">顧客未選択</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars(($c['name'] ?? '-') . (!empty($c['phone']) ? ' / '.$c['phone'] : '')) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>担当者
        <select name="staff_id" id="editStaffId">
          <option value="0">未設定</option>
          <?php foreach ($staffs as $s): ?>
            <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name'] ?? '-') ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>小計（税込）<input type="number" name="subtotal" id="editSubtotal"></label>
      <label>割引<input type="number" name="discount" id="editDiscount"></label>
      <label>一部返金額
        <input type="number" name="refund_amount" id="editRefundAmount" value="0" min="0" placeholder="例）1000">
        <small>入力した金額分だけ各種売上データから差し引かれます。</small>
      </label>
      <label>支払方法
        <select name="payment_method" id="editPayment">
          <option value="cash">現金</option>
          <option value="card">クレジット</option>
          <option value="qr">QR決済</option>
          <option value="other">その他</option>
        </select>
      </label>
      <label>預り金<input type="number" name="deposit_amount" id="editDeposit"></label>
      <label>状態
        <select name="status" id="editStatus">
          <option value="paid">有効</option>
          <option value="void">VOID / 全額返金（売上から完全除外）</option>
        </select>
        <small>VOIDにすると、この会計は日別売上・月間売上・ランキング・客単価から除外されます。</small>
      </label>
      <label>メモ<textarea name="note" id="editNote" rows="4"></textarea></label>

      <div class="refund-help">
        <strong>返金・VOIDの反映</strong>
        <span>VOID：売上データから全額除外</span>
        <span>一部返金：返金額分だけ売上から差し引き</span>
      </div>

      <button type="submit">修正保存</button>
    </form>
  </div>

  <script src="../assets/js/sales-history.js?v=1"></script>
</body>
</html>
