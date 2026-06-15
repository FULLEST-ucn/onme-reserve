# Fix Calendar Availability Column

## 修正内容
- Calendar Masterの `a.work_date` 固定参照を廃止
- availabilityテーブルの実際のカラム名を自動判定
  - work_date / date / available_date / day
  - start_time / start / from_time
  - end_time / end / to_time
  - staff_id / staff
- スタッフ数に合わせてカレンダー列幅を自動調整

## 確認URL
/admin/calendar-master.php
