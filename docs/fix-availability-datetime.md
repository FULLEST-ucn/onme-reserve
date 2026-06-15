# Fix Availability Datetime

## 原因
availabilityテーブルは start_datetime / end_datetime が必須です。
前回の追加処理では date / start_time / end_time だけを入れていたためエラーになっていました。

## 修正内容
- 空き枠追加時に start_datetime / end_datetime へ値を保存
- date / start_time / end_time にも同時保存
- Calendar Masterも start_datetime / end_datetime 基準で表示

## 確認URL
/admin/availability.php
/admin/calendar-master.php
