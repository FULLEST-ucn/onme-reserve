<?php
require dirname(__DIR__) . '/config/bootstrap.php';
$config = app_config();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    if ($code === $config['owner_code']) {
        $_SESSION['staff'] = ['id'=>'owner','name'=>'OWNER','role'=>'owner'];
        redirect('/admin/');
    }
    if (isset($config['staff_codes'][$code])) {
        $_SESSION['staff'] = $config['staff_codes'][$code];
        redirect('/admin/');
    }
    $error = '従業員番号が正しくありません。';
}
?>
<!doctype html><html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ON;ME Admin Login</title><link rel="stylesheet" href="/assets/css/admin.css"></head>
<body class="login-page"><form method="post" class="login-card"><p>STAFF LOGIN</p><h1>ON;ME OS</h1><?php if($error): ?><div class="error"><?=h($error)?></div><?php endif; ?><input name="code" placeholder="従業員番号" inputmode="numeric" autofocus><button>ログイン</button><small>OWNER:9999 / KIHO:1001 / YUINA:1002</small></form></body></html>
