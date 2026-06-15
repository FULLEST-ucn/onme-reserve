<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$error = '';
$saved = false;

try {
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $memo = trim($_POST['memo'] ?? '');

    if ($name === '') {
      throw new Exception('お名前を入力してください。');
    }

    $columns = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_ASSOC));

    $insert = [];
    $values = [];
    $params = [];

    foreach ([
      'name' => $name,
      'phone' => $phone,
      'email' => $email,
      'memo' => $memo,
      'note' => $memo,
      'created_at' => '__NOW__',
      'updated_at' => '__NOW__',
    ] as $col => $value) {
      if (in_array($col, $columns, true)) {
        $insert[] = "`{$col}`";
        if ($value === '__NOW__') {
          $values[] = "NOW()";
        } else {
          $values[] = "?";
          $params[] = $value;
        }
      }
    }

    $sql = "INSERT INTO customers (" . implode(',', $insert) . ") VALUES (" . implode(',', $values) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $saved = true;
  }
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>顧客情報追加 | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=71">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=71">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=71">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=71">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=4">
  <link rel="stylesheet" href="../assets/css/customer-create.css?v=1">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">CUSTOMER CREATE</p>
        <h1>顧客情報追加</h1>
        <p class="customer-create-lead">新規顧客の基本情報を登録できます。</p>
      </div>
      <div class="pro-actions">
        <a class="pro-button primary" href="./carte-master.php">顧客一覧</a>
      </div>
    </header>

    <?php if ($saved): ?>
      <section class="os-panel success">顧客情報を追加しました。</section>
    <?php endif; ?>

    <?php if ($error): ?>
      <section class="os-panel"><?= htmlspecialchars($error) ?></section>
    <?php endif; ?>

    <section class="customer-create-layout">
      <article class="os-panel">
        <p class="eyebrow">PROFILE</p>
        <h2>基本情報</h2>

        <form method="post" class="customer-create-form">
          <label>
            お名前
            <input name="name" required placeholder="例）山田 花子">
          </label>

          <label>
            電話番号
            <input name="phone" placeholder="例）09012345678">
          </label>

          <label>
            メール
            <input name="email" type="email" placeholder="例）sample@example.com">
          </label>

          <label>
            メモ
            <textarea name="memo" rows="6" placeholder="好み・注意事項・来店経緯など"></textarea>
          </label>

          <button type="submit">追加する</button>
        </form>
      </article>

      <article class="os-panel customer-create-guide">
        <p class="eyebrow">NEXT ACTION</p>
        <h2>登録後にできること</h2>
        <div class="guide-list">
          <span>カルテ作成</span>
          <span>予約登録</span>
          <span>来店履歴管理</span>
          <span>LINE配信対象化</span>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
