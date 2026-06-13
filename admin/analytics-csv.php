<?php
require_once __DIR__ . '/../config/db.php';

$month = $_GET['month'] ?? date('Y-m');
$start = $month . '-01';
$end = date('Y-m-t', strtotime($start));

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="onme_analytics_' . $month . '.csv"');

echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, ['日付', '予約ID', '顧客名', 'スタッフ', 'メニュー', '金額', '状態']);

try {
  $pdo = db();
  $stmt = $pdo->prepare("
    SELECT r.id, r.start_datetime, r.status,
           c.name AS customer_name,
           s.name AS staff_name,
           m.name AS menu_name,
           m.price
    FROM reservations r
    LEFT JOIN customers c ON c.id = r.customer_id
    LEFT JOIN staffs s ON s.id = r.staff_id
    LEFT JOIN menus m ON m.id = r.menu_id
    WHERE DATE(r.start_datetime) BETWEEN ? AND ?
    ORDER BY r.start_datetime ASC
  ");
  $stmt->execute([$start, $end]);

  while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
      date('Y/m/d H:i', strtotime($r['start_datetime'])),
      $r['id'],
      $r['customer_name'],
      $r['staff_name'],
      $r['menu_name'],
      $r['price'],
      $r['status'],
    ]);
  }
} catch (Throwable $e) {
  fputcsv($out, ['ERROR', $e->getMessage()]);
}
fclose($out);
