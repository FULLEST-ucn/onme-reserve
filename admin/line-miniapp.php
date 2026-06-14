<?php require_once __DIR__ . '/../config/auth.php'; require_owner(); ?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LINE Mini App | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=40">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=40">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=40">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>LINE</strong></div>
    <nav>
      <a href="./dashboard-v2.php">Dashboard V2</a>
      <a class="active" href="./line-miniapp.php">LINE Mini App</a>
      <a href="./line-automation.php">LINE Automation</a>
      <a href="./settings-pro.php">Settings</a>
    </nav>
  </aside>
  <main class="pro-main">
    <header class="pro-top"><div><p class="eyebrow">CUSTOMER APP BUILDER</p><h1>LINE Mini App</h1></div></header>
    <section class="suite40-phone-layout">
      <article class="suite40-phone">
        <div class="phone-screen">
          <h2>ON;ME</h2>
          <a>予約する</a>
          <a>マイページ</a>
          <a>来店履歴</a>
          <a>ポイント</a>
          <a>クーポン</a>
        </div>
      </article>
      <article class="os-panel">
        <p class="eyebrow">APP MENU</p>
        <h2>LINE内メニュー</h2>
        <div class="suite-check-list">
          <span>予約導線</span>
          <span>マイページ</span>
          <span>予約変更/キャンセル</span>
          <span>ポイント/クーポン</span>
          <span>来店履歴</span>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
