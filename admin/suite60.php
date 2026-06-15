<?php require_once __DIR__ . '/../config/auth.php'; require_owner(); ?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Suite60 | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=61">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=61">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=61">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=61">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=1">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="pro-main">
    <header class="pro-top"><div><p class="eyebrow">FULL STACK BEAUTY OS</p><h1>Suite60</h1></div></header>
    <section class="s60-grid three">
      <a class="s60-link-card" href="./owner-bi.php"><small>41</small><strong>Owner BI</strong><span>経営分析</span></a>
      <a class="s60-link-card" href="./ai-executive.php"><small>42</small><strong>AI Executive</strong><span>経営AI</span></a>
      <a class="s60-link-card" href="./customer-360.php"><small>43</small><strong>Customer 360</strong><span>LTV/履歴</span></a>
      <a class="s60-link-card" href="./line-segment-pro.php"><small>44</small><strong>LINE Segment</strong><span>配信強化</span></a>
      <a class="s60-link-card" href="./inventory-pro.php"><small>45</small><strong>Inventory</strong><span>在庫管理</span></a>
      <a class="s60-link-card" href="./pwa-pro.php"><small>46</small><strong>PWA</strong><span>アプリ化</span></a>
      <a class="s60-link-card" href="./staff-shifts.php"><small>47</small><strong>Shift</strong><span>シフト</span></a>
      <a class="s60-link-card" href="./subscription-pro.php"><small>48</small><strong>Subscription</strong><span>会員/回数券</span></a>
      <a class="s60-link-card" href="./consent-pro.php"><small>49</small><strong>Consent</strong><span>電子同意書</span></a>
    </section>
  </main>
</body>
</html>
