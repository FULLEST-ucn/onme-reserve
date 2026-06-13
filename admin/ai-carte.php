<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$customerId = (int)($_GET['customer_id'] ?? 0);
$summary = '';

try {
  $pdo = db();
  $stmt = $pdo->prepare("
    SELECT * FROM customer_carte_records
    WHERE customer_id=?
    ORDER BY created_at DESC
    LIMIT 5
  ");
  $stmt->execute([$customerId]);
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if ($records) {
    $colors = array_filter(array_column($records, 'color'));
    $materials = array_filter(array_column($records, 'materials'));
    $summary = "前回までの傾向：\n";
    $summary .= "・カラー：" . ($colors ? implode(' / ', array_slice($colors, 0, 3)) : '記録なし') . "\n";
    $summary .= "・商材：" . ($materials ? implode(' / ', array_slice($materials, 0, 3)) : '記録なし') . "\n";
    $summary .= "・次回提案：前回カラーに近い系統、または季節感のあるデザイン提案がおすすめです。";
  } else {
    $summary = "カルテ履歴がないため、初回来店用のヒアリングをおすすめします。";
  }
} catch(Throwable $e) {
  $summary = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AI Carte | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=25">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=25">
</head>
<body class="pro-body">
  <aside class="pro-sidebar">
    <div class="brand"><small>ON;ME OS</small><strong>AI Carte</strong></div>
    <nav><a href="./carte-pro.php">Carte Pro</a><a href="./ai-concierge.php">AI Concierge</a></nav>
  </aside>
  <main class="pro-main">
    <header class="pro-top"><div><p class="eyebrow">AI SUMMARY</p><h1>AI Carte</h1></div></header>
    <section class="os-panel">
      <pre class="ai-result"><?= htmlspecialchars($summary) ?></pre>
    </section>
  </main>
</body>
</html>
