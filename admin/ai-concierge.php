<?php require_once __DIR__ . '/../config/auth.php'; require_login(); ?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AI Concierge | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=20">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=20">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>AI</strong></div>
    <nav>
      <a href="./staff-dashboard.php">Dashboard</a>
      <a class="active" href="./ai-concierge.php">AI Concierge</a>
      <a href="./calendar-pro.php">Calendar</a>
    </nav>
  </aside>
  <main class="pro-main">
    <header class="pro-top"><div><p class="eyebrow">AI RESERVATION ASSISTANT</p><h1>AI Concierge</h1></div></header>
    <section class="os-panel">
      <p class="eyebrow">TEST CHAT</p>
      <h2>空き時間提案</h2>
      <form id="aiForm" class="os-form">
        <label>問い合わせ内容<textarea id="message" rows="5">来週土曜、120分メニュー空いてますか？</textarea></label>
        <button>提案を作成</button>
      </form>
      <pre class="ai-result" id="aiResult"></pre>
    </section>
  </main>
<script>
document.getElementById('aiForm').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData();
  fd.append('message', document.getElementById('message').value);
  const res = await fetch('../api/ai-concierge.php', {method:'POST', body:fd});
  const data = await res.json();
  document.getElementById('aiResult').textContent = data.reply || data.error || 'エラー';
});
</script>
</body>
</html>
