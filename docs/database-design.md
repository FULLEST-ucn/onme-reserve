# ON;ME OS Database Design

## 基本方針

ON;ME OSは「空き枠」ではなく「受付可能時間」を管理します。

例：KIHOが13:00〜17:00で受付可能。
90分予約が15:00〜16:30に入った場合、残りは13:00〜15:00と16:30〜17:00です。
顧客側には、選択メニューの所要時間以上が入る開始時間のみ表示します。

## 主要テーブル

- `staffs`：スタッフ・ログイン情報
- `menus`：メニュー
- `menu_options`：オフあり、長さ出し等
- `customers`：顧客情報
- `availability`：受付可能時間
- `reservations`：予約
- `reservation_options`：予約に紐づくオプション
- `customer_notes`：カルテメモ
- `customer_photos`：施術写真

## 初期ログイン

- OWNER: 9999
- KIHO: 1001
- YUINA: 1002

初期パスワードは全員 `onme2026` です。公開前に必ず変更してください。
