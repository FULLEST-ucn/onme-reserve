<?php
require dirname(__DIR__) . '/config/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$staff = $_GET['staff'] ?? '';
$minutes = max(30, (int)($_GET['minutes'] ?? 90));

$availability = storage_json('availability', [
    ['id'=>'a1','staff'=>'kiho','date'=>date('Y-m-d', strtotime('+1 day')),'start'=>'13:00','end'=>'17:00'],
    ['id'=>'a2','staff'=>'yuina','date'=>date('Y-m-d', strtotime('+1 day')),'start'=>'10:00','end'=>'15:00'],
]);
$reservations = storage_json('reservations', []);

function tmin(string $time): int {
    [$h,$m] = array_map('intval', explode(':', $time));
    return $h*60+$m;
}
function fmt(int $min): string {
    return sprintf('%02d:%02d', intdiv($min,60), $min%60);
}
function overlap(int $s1,int $e1,int $s2,int $e2): bool { return $s1 < $e2 && $s2 < $e1; }

$slots=[];
foreach($availability as $a){
    if(($a['staff'] ?? '') !== $staff) continue;
    $start=tmin($a['start']); $end=tmin($a['end']);
    for($s=$start; $s+$minutes <= $end; $s+=30){
        $e=$s+$minutes; $blocked=false;
        foreach($reservations as $r){
            if(($r['staff']['id'] ?? $r['staff_id'] ?? '') !== $staff) continue;
            if(($r['slot']['date'] ?? $r['date'] ?? '') !== $a['date']) continue;
            $rs=tmin($r['slot']['start'] ?? $r['start'] ?? '00:00');
            $re=tmin($r['slot']['end'] ?? $r['end'] ?? '00:00');
            if(overlap($s,$e,$rs,$re)){ $blocked=true; break; }
        }
        if(!$blocked) $slots[]=['date'=>$a['date'],'start'=>fmt($s),'end'=>fmt($e),'staff'=>$staff];
    }
}
echo json_encode(['ok'=>true,'slots'=>$slots], JSON_UNESCAPED_UNICODE);
