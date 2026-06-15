<?php
require_once __DIR__ . '/../config/auth.php';
require_owner();
<<<<<<< HEAD

$saved = false;
$deleted = false;
$error = '';

try {
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
      $deleteId = (int)($_POST['delete_id'] ?? 0);

      if ($deleteId > 0) {
        // 予約履歴との整合性を守るため物理削除ではなく非表示にします
        $stmt = $pdo->prepare("UPDATE menus SET is_active = 0, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$deleteId]);
        $deleted = true;
      }
    }

    if ($action === 'save' && isset($_POST['menu']) && is_array($_POST['menu'])) {
      foreach ($_POST['menu'] as $id => $menu) {
        $stmt = $pdo->prepare("
          UPDATE menus
          SET
            name = ?,
            duration_minutes = ?,
            price = ?,
            description = ?,
            sort_order = ?,
            updated_at = NOW()
          WHERE id = ?
        ");
        $stmt->execute([
          trim($menu['name'] ?? ''),
          (int)($menu['duration_minutes'] ?? 0),
          (int)($menu['price'] ?? 0),
          trim($menu['description'] ?? ''),
          (int)($menu['sort_order'] ?? 0),
          (int)$id
        ]);
      }
      $saved = true;
    }
  }

  $menus = $pdo->query("
    SELECT *
    FROM menus
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $error = $e->getMessage();
  $menus = [];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Menu Master | ON;ME OS</title>
  <link rel="stylesheet" href="../assets/css/admin-pro.css?v=64">
  <link rel="stylesheet" href="../assets/css/os-pro.css?v=64">
  <link rel="stylesheet" href="../assets/css/suite40.css?v=64">
  <link rel="stylesheet" href="../assets/css/suite60.css?v=64">
  <link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=2">
  <link rel="stylesheet" href="../assets/css/menu-master-readable.css?v=2">
</head>
<body class="pro-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="pro-main">
    <header class="pro-top">
      <div>
        <p class="eyebrow">MENU DESIGN</p>
        <h1>Menu Master</h1>
        <p class="menu-lead">表示順・メニュー名・時間・料金・説明文をまとめて管理できます。</p>
      </div>
      <div class="pro-actions">
        <a class="pro-button" href="./menus.php">メニュー追加</a>
      </div>
    </header>

    <?php if ($saved): ?>
      <section class="os-panel success">メニュー情報を保存しました。</section>
    <?php endif; ?>

    <?php if ($deleted): ?>
      <section class="os-panel success">メニューを削除しました。</section>
    <?php endif; ?>

    <?php if ($error): ?>
      <section class="os-panel"><?= htmlspecialchars($error) ?></section>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="action" value="save">

      <section class="menu-master-panel">
        <div class="menu-master-head">
          <div>
            <p class="eyebrow">MENU LIST</p>
            <h2>登録メニュー</h2>
          </div>
          <button type="submit" class="save-button">保存する</button>
        </div>

        <div class="menu-table-header">
          <span>表示順</span>
          <span>メニュー名</span>
          <span>時間</span>
          <span>料金</span>
          <span>説明文</span>
          <span>操作</span>
        </div>

        <div class="menu-master-list">
          <?php if (!$menus): ?>
            <p class="muted-text">登録メニューがありません。</p>
          <?php endif; ?>

          <?php foreach ($menus as $menu): ?>
            <article class="menu-master-row">
              <div class="order-field">
                <small>表示順</small>
                <input type="number" name="menu[<?= (int)$menu['id'] ?>][sort_order]" value="<?= (int)($menu['sort_order'] ?? 0) ?>">
              </div>

              <div class="name-field">
                <small>メニュー名</small>
                <input name="menu[<?= (int)$menu['id'] ?>][name]" value="<?= htmlspecialchars($menu['name'] ?? '') ?>">
              </div>

              <div>
                <small>時間</small>
                <input type="number" name="menu[<?= (int)$menu['id'] ?>][duration_minutes]" value="<?= (int)($menu['duration_minutes'] ?? 0) ?>">
              </div>

              <div>
                <small>料金</small>
                <input type="number" name="menu[<?= (int)$menu['id'] ?>][price]" value="<?= (int)($menu['price'] ?? 0) ?>">
              </div>

              <div class="description-field">
                <small>説明文</small>
                <input name="menu[<?= (int)$menu['id'] ?>][description]" value="<?= htmlspecialchars($menu['description'] ?? '') ?>" placeholder="例）シンプルなワンカラーメニュー">
              </div>

              <div class="delete-field">
                <small>操作</small>
                <button
                  type="submit"
                  class="delete-menu-button"
                  name="delete_id"
                  value="<?= (int)$menu['id'] ?>"
                  formaction="./menu-master.php"
                  formmethod="post"
                  onclick="return confirm('このメニューを削除しますか？予約履歴は残り、メニュー一覧から非表示になります。');"
                >
                  削除
                </button>
                <input type="hidden" name="action_delete_<?= (int)$menu['id'] ?>" value="delete">
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    </form>

    <form id="deleteForm" method="post" hidden>
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="delete_id" id="deleteId">
    </form>
  </main>

  <script>
    document.querySelectorAll('.delete-menu-button').forEach((button) => {
      button.addEventListener('click', function(event) {
        event.preventDefault();
        if (!confirm('このメニューを削除しますか？予約履歴は残り、メニュー一覧から非表示になります。')) return;
        document.getElementById('deleteId').value = this.value;
        document.getElementById('deleteForm').submit();
      });
    });
  </script>
</body>
</html>
=======
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
>>>>>>> 77d34561990733cc5fd0dc12c5c67a39e3e638ab
