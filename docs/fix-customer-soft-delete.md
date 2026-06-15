# Fix Customer Soft Delete

## 原因
customersテーブルに `is_active` が無い場合、削除済み顧客を一覧から除外できず、リロード後に復活していました。

## 修正内容
- customersに `is_active` カラムを自動追加
- 削除時に `is_active = 0`
- Carte Masterでは `is_active = 1` の顧客だけ表示
- SQLマイグレーションも同梱

## 反映方法
通常はZIP上書きだけでOKです。
もし自動追加されない場合は、HeidiSQLで以下を実行してください。

```sql
USE onme_reserve;

ALTER TABLE customers
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;
```

## 確認URL
/admin/carte-master.php
