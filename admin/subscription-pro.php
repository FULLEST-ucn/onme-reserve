<?php
require_once __DIR__ . '/../config/auth.php';
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
