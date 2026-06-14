<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
$date = $_GET['date'] ?? date('Y-m-d');
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Calendar Master | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=40">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=40">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=40">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>Cal</strong></div>
    <nav>
      <a href="./dashboard-v2.php">Dashboard V2</a>
      <a class="active" href="./calendar-master.php">Calendar Master</a>
      <a href="./calendar-pro.php">Calendar Pro</a>
      <a href="./calendar-week.php">Week</a>
    </nav>
  </aside>
  <main class="pro-main">
    <header class="pro-top">
      <div><p class="eyebrow">APPLE / GOOGLE STYLE</p><h1>Calendar Master</h1></div>
      <div class="pro-actions">
        <a class="pro-button primary" href="./calendar-pro.php?date=<?= htmlspecialchars($date) ?>">Day</a>
        <a class="pro-button" href="./calendar-week.php?date=<?= htmlspecialchars($date) ?>">Week</a>
      </div>
    </header>
    <section class="suite40-calendar-shell">
      <iframe src="./calendar-pro.php?date=<?= htmlspecialchars($date) ?>" loading="lazy"></iframe>
    </section>
  </main>
</body>
</html>
