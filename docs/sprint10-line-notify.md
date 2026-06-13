# Sprint10 LINE Notify

## 追加内容
- LINE Messaging API設定ファイル
- 予約完了時のお客様LINE通知
- 店舗側LINE通知
- LINE設定画面
- Webhook受信ログ雛形

## 管理画面
/admin/line-settings.php

## 注意
config/line.php にはアクセストークンが入るため、本来はGit管理しない運用が望ましいです。
本番では .gitignore に config/line.php を追加することを推奨します。

## 次Sprint
- LINE WebhookとAI予約
- 前日リマインド
- キャンセル通知
