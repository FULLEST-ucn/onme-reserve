<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/store.php';

function line_store_token(): string {
  $store = current_store();
  return trim($store['line_channel_token'] ?? '');
}

function line_send_text(string $to, string $message, string $eventType = 'manual', ?int $customerId = null, ?int $reservationId = null): bool {
  $token = line_store_token();
  $ok = false;
  $response = '';

  if ($token && $to) {
    $payload = [
      'to' => $to,
      'messages' => [['type' => 'text', 'text' => $message]],
    ];

    $ch = curl_init('https://api.line.me/v2/bot/message/push');
    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
      ],
      CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 12,
    ]);
    $response = (string)curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $ok = $status >= 200 && $status < 300;
  }

  try {
    $pdo = db();
    $stmt = $pdo->prepare("
      INSERT INTO notification_logs
        (store_id, customer_id, reservation_id, channel, event_type, recipient, message, status, response, created_at)
      VALUES (?, ?, ?, 'line', ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([current_store_id(), $customerId, $reservationId, $eventType, $to, $message, $ok ? 'sent' : 'skipped', $response]);
  } catch(Throwable $e) {}

  return $ok;
}

function line_reservation_message(array $r): string {
  return "ご予約ありがとうございます✨\n\n"
    . "【日時】{$r['date']} {$r['start_time']}〜{$r['end_time']}\n"
    . "【担当】{$r['staff_name']}\n"
    . "【メニュー】{$r['menu_name']}\n\n"
    . "ご来店を心よりお待ちしております💅";
}
