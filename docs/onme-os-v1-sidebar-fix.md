# ON;ME OS v1 Common Sidebar Fix

## 修正内容
- `admin/partials/sidebar.php` を追加
- 左メニューを全ページ共通化
- 「過去売上」を左メニューに追加
- 「レジ締め」を左メニューに追加
- include warning を解消
- 既存ページが `include __DIR__ . '/partials/sidebar.php';` を読んでも壊れないように修正

## 上書き場所
プロジェクト直下にそのまま上書きしてください。

## 確認URL
/admin/dashboard-v2.php
/admin/pos-pro.php
/admin/sales-history.php
/admin/register-close.php
/admin/carte-master.php
