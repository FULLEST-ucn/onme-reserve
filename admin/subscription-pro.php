<?php
require_once __DIR__ . '/../config/auth.php';
<<<<<<< HEAD
require_login();

$error = '';
$saved = false;

try {
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $planName = trim($_POST['plan_name'] ?? '');
    $amount = (int)($_POST['amount'] ?? 0);
    $billingCycle = trim($_POST['billing_cycle'] ?? 'monthly');
    $startedAt = $_POST['started_at'] ?? date('Y-m-d');

    if ($customerId <= 0 || $planName === '') {
      throw new Exception('顧客とプラン名を入力してください。');
    }

    $stmt = $pdo->prepare("
      INSERT INTO customer_subscriptions
        (customer_id, plan_name, amount, billing_cycle, status, started_at, created_at)
      VALUES
        (?, ?, ?, ?, 'active', ?, NOW())
    ");
    $stmt->execute([$customerId, $planName, $amount, $billingCycle, $startedAt]);
    $saved = true;
  }

  $customers = $pdo->query("
    SELECT id, name, phone
    FROM customers
    ORDER BY id DESC
    LIMIT 200
  ")->fetchAll(PDO::FETCH_ASSOC);

  $subscriptions = $pdo->query("
    SELECT cs.*, c.name AS customer_name, c.phone
    FROM customer_subscriptions cs
    LEFT JOIN customers c ON c.id = cs.customer_id
    ORDER BY cs.id DESC
    LIMIT 80
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
  $customers = [];
  $subscriptions = [];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Subscription | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=76">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=76">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=76">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=76">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=5">
  <link rel="stylesheet" href="../assets/css/subscription-consent-unified.css?v=1">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">MEMBERSHIP</p>
        <h1>Subscription</h1>
        <p class="page-lead">月額プラン・回数券・会員契約を管理できます。</p>
      </div>
      <div class="pro-actions">
        <a class="pro-button primary" href="./carte-master.php">Customers</a>
      </div>
    </header>

    <?php if ($saved): ?><section class="os-panel success">サブスクを登録しました。</section><?php endif; ?>
    <?php if ($error): ?><section class="os-panel"><?= htmlspecialchars($error) ?></section><?php endif; ?>

    <section class="sc-layout">
      <article class="os-panel">
        <p class="eyebrow">CREATE</p>
        <h2>サブスク登録</h2>

        <form method="post" class="sc-form">
          <label>
            顧客
            <select name="customer_id" required>
              <option value="">選択してください</option>
              <?php foreach ($customers as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name'] ?? '-') ?> / <?= htmlspecialchars($c['phone'] ?? '-') ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label>
            プラン名
            <input name="plan_name" placeholder="例）月額ネイルケアプラン" required>
          </label>

          <label>
            金額
            <input type="number" name="amount" value="9800">
          </label>

          <label>
            請求サイクル
            <select name="billing_cycle">
              <option value="monthly">月額</option>
              <option value="ticket">回数券</option>
              <option value="one_time">単発</option>
            </select>
          </label>

          <label>
            開始日
            <input type="date" name="started_at" value="<?= date('Y-m-d') ?>">
          </label>

          <button type="submit">登録する</button>
        </form>
      </article>

      <article class="os-panel">
        <p class="eyebrow">LIST</p>
        <h2>契約一覧</h2>

        <div class="sc-list">
          <?php if (!$subscriptions): ?><p class="muted-text">契約データがありません。</p><?php endif; ?>

          <?php foreach ($subscriptions as $s): ?>
            <article class="sc-card">
              <div>
                <strong><?= htmlspecialchars($s['plan_name'] ?? '-') ?></strong>
                <span><?= htmlspecialchars($s['customer_name'] ?? '-') ?> / <?= htmlspecialchars($s['status'] ?? '-') ?></span>
              </div>
              <em>¥<?= number_format((int)($s['amount'] ?? 0)) ?></em>
            </article>
          <?php endforeach; ?>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
=======
require_owner();
$saved=false;
try{
  $pdo=db();
  if($_SERVER['REQUEST_METHOD']==='POST'){
    $stmt=$pdo->prepare("INSERT INTO customer_subscriptions (customer_id,plan_name,amount,billing_cycle,status,started_at,created_at) VALUES (?,?,?,?,?,CURDATE(),NOW())");
    $stmt->execute([(int)$_POST['customer_id'],trim($_POST['plan_name']),(int)$_POST['amount'],$_POST['billing_cycle'],'active']);
    $saved=true;
  }
  $customers=$pdo->query("SELECT id,name FROM customers ORDER BY updated_at DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
  $subs=$pdo->query("SELECT s.*, c.name customer_name FROM customer_subscriptions s LEFT JOIN customers c ON c.id=s.customer_id ORDER BY s.created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $e){$error=$e->getMessage();$customers=[];$subs=[];}
?>
<!doctype html><html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Subscription</title>
<link rel="stylesheet" href="../assets/css/admin-pro.css?v=40"><link rel="stylesheet" href="../assets/css/os-pro.css?v=40"><link rel="stylesheet" href="../assets/css/suite40.css?v=40"></head>
<body class="pro-body"><aside class="pro-sidebar"><div class="brand"><small>ON;ME OS</small><strong>Sub</strong></div><nav><a href="./dashboard-v2.php">Dashboard</a><a class="active" href="./subscription-pro.php">Subscription</a></nav></aside>
<main class="pro-main"><header class="pro-top"><div><p class="eyebrow">MEMBERSHIP</p><h1>Subscription</h1></div></header>
<?php if($saved): ?><section class="os-panel success">登録しました。</section><?php endif; ?>
<section class="os-two"><section class="os-panel"><h2>会員プラン登録</h2><form method="post" class="os-form"><label>顧客<select name="customer_id"><?php foreach($customers as $c): ?><option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></label><label>プラン名<input name="plan_name" value="Monthly Nail Plan"></label><label>金額<input type="number" name="amount" value="9800"></label><label>周期<select name="billing_cycle"><option value="monthly">月額</option><option value="ticket">回数券</option></select></label><button>登録</button></form></section>
<section class="os-panel"><h2>会員一覧</h2><div class="os-list"><?php foreach($subs as $s): ?><article class="os-list-item"><strong><?= htmlspecialchars($s['customer_name']??'-') ?> / <?= htmlspecialchars($s['plan_name']) ?></strong><span>¥<?= number_format((int)$s['amount']) ?> / <?= htmlspecialchars($s['billing_cycle']) ?></span></article><?php endforeach; ?></div></section></section>
</main></body></html>
>>>>>>> 77d34561990733cc5fd0dc12c5c67a39e3e638ab
