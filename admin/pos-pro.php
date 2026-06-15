<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$error = '';
$saved = false;
$voided = false;

function ensure_pos_sales_schema(PDO $pdo): void {
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
    if (!in_array($col, $cols, true)) {
      $pdo->exec($sql);
    }
  }
}

try {
  $pdo = db();
  ensure_pos_sales_schema($pdo);

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
      $customerId = (int)($_POST['customer_id'] ?? 0);
      $staffId = (int)($_POST['staff_id'] ?? ($_SESSION['staff_id'] ?? $_SESSION['user_id'] ?? 0));
      $subtotal = (int)($_POST['subtotal'] ?? 0);
      $discount = (int)($_POST['discount'] ?? 0);
      $payment = trim($_POST['payment_method'] ?? 'cash');
      $deposit = (int)($_POST['deposit_amount'] ?? 0);
      $note = trim($_POST['note'] ?? '');
      $total = max(0, $subtotal - $discount);

      if ($total <= 0) {
        throw new Exception('メニューを選択してください。');
      }
      if ($payment === 'cash' && $deposit < $total) {
        throw new Exception('預り金が合計金額より少ないため、会計登録できません。');
      }

      $change = $payment === 'cash' ? max(0, $deposit - $total) : 0;

      $stmt = $pdo->prepare("
        INSERT INTO pos_sales
          (reservation_id, customer_id, staff_id, subtotal, discount, total, payment_method, deposit_amount, change_amount, note, status, created_at, updated_at)
        VALUES
          (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', NOW(), NOW())
      ");
      $stmt->execute([
        $customerId ?: null,
        $staffId ?: null,
        $subtotal,
        $discount,
        $total,
        $payment,
        $deposit,
        $change,
        $note
      ]);
      $saved = true;
    }

    if ($action === 'void') {
      $saleId = (int)($_POST['sale_id'] ?? 0);
      if ($saleId <= 0) throw new Exception('会計IDが不正です。');

      $stmt = $pdo->prepare("UPDATE pos_sales SET status = 'void', updated_at = NOW() WHERE id = ?");
      $stmt->execute([$saleId]);
      $voided = true;
    }
  }

  $menus = $pdo->query("
    SELECT id, name, price, duration_minutes
    FROM menus
    WHERE COALESCE(is_active, 1) = 1
    ORDER BY COALESCE(sort_order, id), id
    LIMIT 300
  ")->fetchAll(PDO::FETCH_ASSOC);

  $customers = $pdo->query("
    SELECT id, name, phone
    FROM customers
    WHERE COALESCE(is_active, 1) = 1
    ORDER BY id DESC
    LIMIT 300
  ")->fetchAll(PDO::FETCH_ASSOC);

  $staffs = $pdo->query("
    SELECT id, name
    FROM staffs
    WHERE COALESCE(is_active, 1) = 1
    ORDER BY id ASC
    LIMIT 100
  ")->fetchAll(PDO::FETCH_ASSOC);

  $sales = $pdo->query("
    SELECT
      ps.*,
      c.name AS customer_name,
      c.phone AS customer_phone,
      s.name AS staff_name
    FROM pos_sales ps
    LEFT JOIN customers c ON c.id = ps.customer_id
    LEFT JOIN staffs s ON s.id = ps.staff_id
    WHERE DATE(ps.created_at) = CURDATE()
    ORDER BY ps.id DESC
  ")->fetchAll(PDO::FETCH_ASSOC);

  $todayCount = 0;
  $grossSales = 0;
  foreach ($sales as $s) {
    if (($s['status'] ?? 'paid') !== 'void') {
      $todayCount++;
      $grossSales += (int)($s['total'] ?? 0);
    }
  }
  $netSales = (int)floor($grossSales / 1.1);
  $taxAmount = $grossSales - $netSales;

} catch (Throwable $e) {
  $error = $e->getMessage();

  try { $menus = db()->query("SELECT id, name, price FROM menus WHERE COALESCE(is_active, 1) = 1 ORDER BY COALESCE(sort_order, id), id LIMIT 300")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e2) { $menus = []; }
  try { $customers = db()->query("SELECT id, name, phone FROM customers WHERE COALESCE(is_active, 1) = 1 ORDER BY id DESC LIMIT 300")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e2) { $customers = []; }
  try { $staffs = db()->query("SELECT id, name FROM staffs WHERE COALESCE(is_active, 1) = 1 ORDER BY id ASC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e2) { $staffs = []; }
  try { $sales = db()->query("SELECT ps.*, c.name AS customer_name, c.phone AS customer_phone, s.name AS staff_name FROM pos_sales ps LEFT JOIN customers c ON c.id = ps.customer_id LEFT JOIN staffs s ON s.id = ps.staff_id WHERE DATE(ps.created_at) = CURDATE() ORDER BY ps.id DESC")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e2) { $sales = []; }

  $todayCount = 0;
  $grossSales = 0;
  foreach ($sales as $s) {
    if (($s['status'] ?? 'paid') !== 'void') {
      $todayCount++;
      $grossSales += (int)($s['total'] ?? 0);
    }
  }
  $netSales = (int)floor($grossSales / 1.1);
  $taxAmount = $grossSales - $netSales;
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>POS | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=101">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=101">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=101">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=101">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=8">
  <link rel="stylesheet" href="../assets/css/pos-register-ui.css?v=4">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">CHECKOUT</p>
        <h1>POS</h1>
      </div>
      <div class="pro-actions pos-top-actions">
        <a class="pro-button primary" href="./dashboard-v2.php">Dashboard</a>
        <a class="pro-button danger register-close-button" href="./register-close.php">レジ締め</a>
      </div>
    </header>

    <?php if ($saved): ?><section class="os-panel success">会計を登録しました。</section><?php endif; ?>
    <?php if ($voided): ?><section class="os-panel success">会計をVOIDしました。</section><?php endif; ?>
    <?php if ($error): ?><section class="os-panel pos-error"><?= htmlspecialchars($error) ?></section><?php endif; ?>

    <section class="pos-kpi">
      <article><span>本日会計</span><strong><?= number_format($todayCount) ?></strong></article>
      <article><span>純売上（税抜）</span><strong>¥<?= number_format($netSales) ?></strong></article>
      <article><span>総売上（税込）</span><strong>¥<?= number_format($grossSales) ?></strong></article>
      <article><span>消費税</span><strong>¥<?= number_format($taxAmount) ?></strong></article>
    </section>

    <form method="post" class="register-grid" id="posRegisterForm">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="subtotal" id="subtotalInput" value="0">
      <input type="hidden" name="discount" id="discountInput" value="0">
      <input type="hidden" name="deposit_amount" id="depositInputHidden" value="0">
      <input type="hidden" name="note" id="noteHidden" value="">

      <section class="register-panel menu-panel">
        <div class="panel-title">
          <p class="eyebrow">MENU</p>
          <h2>メニュー選択</h2>
        </div>

        <div class="reservation-row">
          <label>
            顧客
            <select name="customer_id" id="customerSelect">
              <option value="0">顧客未選択</option>
              <?php foreach ($customers as $c): ?>
                <option value="<?= (int)$c['id'] ?>">
                  <?= htmlspecialchars(($c['name'] ?? '-') . (!empty($c['phone']) ? ' / ' . $c['phone'] : '')) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label>
            担当者
            <select name="staff_id" id="staffSelect">
              <option value="0">選択してください</option>
              <?php foreach ($staffs as $s): ?>
                <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name'] ?? '-') ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>

        <div class="custom-item-box">
          <p class="eyebrow">CUSTOM</p>
          <h3>その他</h3>
          <div class="custom-item-row">
            <input type="text" id="customItemName" placeholder="例）長さ出し追加 / オプション">
            <input type="number" id="customItemPrice" placeholder="金額">
            <button type="button" id="addCustomItem">追加</button>
          </div>
        </div>

        <div class="menu-list" id="menuList">
          <?php if (!$menus): ?>
            <p class="muted-text">メニューがありません。</p>
          <?php endif; ?>

          <?php foreach ($menus as $m): ?>
            <button type="button" class="menu-item" data-id="<?= (int)$m['id'] ?>" data-name="<?= htmlspecialchars($m['name'] ?? '') ?>" data-price="<?= (int)($m['price'] ?? 0) ?>">
              <span><?= htmlspecialchars($m['name'] ?? '-') ?></span>
              <strong>¥<?= number_format((int)($m['price'] ?? 0)) ?></strong>
            </button>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="register-panel center-panel">
        <div class="panel-title">
          <p class="eyebrow">PAYMENT</p>
          <h2>割引・支払方法</h2>
        </div>

        <label class="input-label">
          割引
          <input type="number" id="discountDisplay" value="0" min="0">
        </label>

        <div class="discount-buttons">
          <button type="button" data-discount="0">割引なし</button>
          <button type="button" data-discount="500">¥500</button>
          <button type="button" data-discount="1000">¥1,000</button>
          <button type="button" data-discount="2000">¥2,000</button>
        </div>

        <div class="payment-methods">
          <p>支払方法</p>
          <label><input type="radio" name="payment_method" value="cash" checked><span>現金</span></label>
          <label><input type="radio" name="payment_method" value="card"><span>クレジット</span></label>
          <label><input type="radio" name="payment_method" value="qr"><span>QR決済</span></label>
          <label><input type="radio" name="payment_method" value="other"><span>その他</span></label>
        </div>

        <label class="input-label">
          メモ
          <textarea id="noteTextarea" rows="5"></textarea>
        </label>
      </section>

      <section class="register-panel total-panel">
        <div class="panel-title">
          <p class="eyebrow">TOTAL</p>
          <h2>会計</h2>
        </div>

        <div class="cart-list" id="cartList">
          <p class="muted-text">左のメニューをタップしてください。</p>
        </div>

        <div class="total-box">
          <div><span>小計</span><strong id="subtotalText">¥0</strong></div>
          <div><span>割引計</span><strong id="discountText">¥0</strong></div>
          <div class="grand"><span>合計</span><strong id="totalText">¥0</strong></div>
          <div><span>税抜</span><strong id="netText">¥0</strong></div>
        </div>

        <label class="input-label deposit-area" id="depositArea">
          預り金
          <input type="number" id="depositDisplay" value="0" min="0">
          <small id="depositError" class="deposit-error" hidden>預り金が不足しています。</small>
        </label>

        <div class="change-box">
          <span>おつり</span>
          <strong id="changeText">¥0</strong>
        </div>

        <button type="submit" class="checkout-button" id="checkoutButton">会計登録</button>
      </section>
    </form>

    <section class="register-panel history-panel">
      <div class="panel-title">
        <p class="eyebrow">TODAY SALES</p>
        <h2>本日会計</h2>
      </div>

      <div class="sales-list">
        <?php if (!$sales): ?><p class="muted-text">本日の会計はありません。</p><?php endif; ?>

        <?php foreach ($sales as $s): ?>
          <?php
            $isVoid = ($s['status'] ?? 'paid') === 'void';
            $saleGross = (int)($s['total'] ?? 0);
            $saleNet = (int)floor($saleGross / 1.1);
          ?>
          <article class="sale-card <?= $isVoid ? 'is-void' : '' ?>">
            <div>
              <strong><?= $isVoid ? 'VOID ' : '' ?>¥<?= number_format($saleGross) ?> / <?= htmlspecialchars($s['payment_method'] ?? 'cash') ?></strong>
              <span>税抜 ¥<?= number_format($saleNet) ?> / 税込 ¥<?= number_format($saleGross) ?></span>
              <span><?= htmlspecialchars($s['customer_name'] ?? '顧客未選択') ?> <?= !empty($s['customer_phone']) ? ' / ' . htmlspecialchars($s['customer_phone']) : '' ?></span>
              <em><?= htmlspecialchars(date('H:i', strtotime($s['created_at']))) ?> <?= !empty($s['staff_name']) ? ' / ' . htmlspecialchars($s['staff_name']) : '' ?></em>
            </div>

            <?php if (!$isVoid): ?>
              <form method="post" onsubmit="return confirm('この会計をVOIDしますか？\n売上集計から除外されます。');">
                <input type="hidden" name="action" value="void">
                <input type="hidden" name="sale_id" value="<?= (int)$s['id'] ?>">
                <button type="submit">VOID</button>
              </form>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <div class="change-toast" id="changeToast" hidden>
    <span>おつり</span>
    <strong id="changeToastAmount">¥0</strong>
  </div>

  <script src="../assets/js/pos-register-ui.js?v=3"></script>
</body>
</html>
