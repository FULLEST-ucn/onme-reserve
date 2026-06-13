# Sprint5 Booking

## 追加内容
- reserve.phpのブッキング防止を強化
- 受付可能時間外の予約を拒否
- 同時予約送信対策としてトランザクション内でFOR UPDATE
- 顧客の作成・更新
- 予約完了ページを追加

## 注意
StarServer側のMySQLがFOR UPDATEに対応するにはInnoDBテーブルが必要です。

## 次Sprint
- 管理画面の予約一覧
- 予約ステータス変更
- LINE LIFF userId取得
