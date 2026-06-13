<?php
require dirname(__DIR__) . '/config/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
if(!is_array($input)){ echo json_encode(['ok'=>false,'message'=>'Invalid request']); exit; }

$reservations = storage_json('reservations', []);
$record = [
    'id' => 'r_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)),
    'created_at' => date('c'),
    'status' => 'requested',
    'name' => $input['name'] ?? '',
    'phone' => $input['phone'] ?? '',
    'menu' => $input['menu'] ?? null,
    'staff' => $input['staff'] ?? null,
    'slot' => $input['slot'] ?? null,
    'line_profile' => $input['lineProfile'] ?? null,
];
$reservations[] = $record;
save_json('reservations', $reservations);

echo json_encode(['ok'=>true,'reservation'=>$record], JSON_UNESCAPED_UNICODE);
