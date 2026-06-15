# Login Password Fix

## 原因
以前入っていた password_hash が `1234` と一致していませんでした。

## 修正内容
- admin/login.php を修正
- login_id / employee_no / name のいずれでもログイン可能
- password_hash を `1234` 用の正しいハッシュに更新するSQLを追加

## 必要SQL
database/migrations/2026_06_14_fix_login_password.sql

## ログイン
- ID: owner / kiho / yuina
- Password: 1234
