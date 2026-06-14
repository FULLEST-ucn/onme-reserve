<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
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
