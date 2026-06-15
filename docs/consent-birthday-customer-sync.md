# Consent Birthday Calendar + Customer Sync

## 修正内容
- お誕生日を `type=date` に変更
- 同意書保存と同時に customers に自動連動
- customer_id がある場合は既存顧客を更新
- customer_id がない場合は電話番号/名前で既存顧客を検索
- 見つからなければ顧客名簿に新規追加
- 保存後にCustomer360へ遷移できるリンクを表示

## 確認URL
/admin/consent-sign.php
/admin/consent-pro.php
