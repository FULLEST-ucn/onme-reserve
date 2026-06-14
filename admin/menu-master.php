<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();
$saved=false;
try{
  $pdo=db();
  if($_SERVER['REQUEST_METHOD']==='POST'){
    $stmt=$pdo->prepare("UPDATE menus SET description=?, sort_order=?, category_id=NULL WHERE id=?");
    foreach($_POST['menu']??[] as $id=>$m) $stmt->execute([$m['description']??'', (int)($m['sort_order']??0), (int)$id]);
    $saved=true;
  }
  $menus=$pdo->query("SELECT * FROM menus ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $e){$error=$e->getMessage();$menus=[];}
?>
<!doctype html>
<html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Menu Master</title>
<link rel="stylesheet" href="../assets/css/admin-pro.css?v=40"><link rel="stylesheet" href="../assets/css/os-pro.css?v=40"><link rel="stylesheet" href="../assets/css/suite40.css?v=40"></head>
<body class="pro-body"><aside class="pro-sidebar"><div class="brand"><small>ON;ME OS</small><strong>Menu</strong></div><nav><a href="./dashboard-v2.php">Dashboard</a><a href="./menus.php">Menus</a><a class="active" href="./menu-master.php">Menu Master</a></nav></aside>
<main class="pro-main"><header class="pro-top"><div><p class="eyebrow">MENU DESIGN</p><h1>Menu Master</h1></div></header>
<?php if($saved): ?><section class="os-panel success">保存しました。</section><?php endif; ?>
<section class="os-panel"><form method="post" class="os-form"><div class="os-list">
<?php foreach($menus as $m): ?><article class="suite40-menu-row"><strong><?= htmlspecialchars($m['name']) ?></strong><input type="number" name="menu[<?= (int)$m['id'] ?>][sort_order]" value="<?= (int)($m['sort_order']??0) ?>"><input name="menu[<?= (int)$m['id'] ?>][description]" value="<?= htmlspecialchars($m['description']??'') ?>" placeholder="説明文"></article><?php endforeach; ?>
</div><button>保存</button></form></section></main></body></html>
