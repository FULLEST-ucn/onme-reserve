# Unified Admin Sidebar

## 内容
管理画面の左メニューを共通化します。

## 追加ファイル
- admin/partials/sidebar.php
- assets/css/unified-sidebar.css

## 使い方
各管理画面PHPで、既存の `<aside class="pro-sidebar">...</aside>` を削除し、以下に置き換えます。

```php
<?php include __DIR__ . '/partials/sidebar.php'; ?>
```

head内に以下を追加します。

```html
<link rel="stylesheet" href="../assets/css/unified-sidebar.css?v=1">
```

## 効果
- Dashboard / Calendar / Carte / LINE / POS / AI などで左メニューが統一される
- 現在ページだけ白背景でアクティブ表示
- 今後メニュー追加時は partials/sidebar.php だけ編集すればOK
