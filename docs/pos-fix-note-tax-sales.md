# POS Fix Note + Tax Sales

## 修正内容
- `note` カラムが無い場合でも自動追加
- `customer_id/status/updated_at` など不足カラムも自動追加
- 会計登録時の Unknown column 'note' エラーを解消
- 純売上（税抜）を表示
- 総売上（税込）を表示
- 消費税額を表示
- VOID会計は税抜/税込売上から除外

## 確認URL
/admin/pos-pro.php
