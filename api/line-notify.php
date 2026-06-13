<?php
require_once __DIR__ . '/../config/db.php';

function line_config(): array {
  $path = __DIR__ . '/../config/line.php';
  if (file_exists($path)) {
    return require $path;
  }
  return [
    'channel_access_token' => '',
    'admin_user_ids' => [],
  ];
}

function line_push_message(string $to, string $text): bool {
  $config = line_config();
  $token = $config['channel_access_token'] ?? '';

  if (!$token || !$to || !$text) {
    return false;
  }

  $payload = [
    'to' => $to,
    'messages' => [
      [
        'type' => 'text',
        'text' => $text,
      ],
    ],
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

  $response = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  return $status >= 200 && $status < 300;
}

function line_notify_admins(string $text): void {
  $config = line_config();
  $adminUserIds = $config['admin_user_ids'] ?? [];

  foreach ($adminUserIds as $userId) {
    line_push_message($userId, $text);
  }
}

function format_reservation_message(array $reservation): string {
  $date = $reservation['date'] ?? '';
  $start = $reservation['start_time'] ?? '';
  $end = $reservation['end_time'] ?? '';
  $customer = $reservation['customer_name'] ?? '';
  $staff = $reservation['staff_name'] ?? '';
  $menu = $reservation['menu_name'] ?? '';
  $payment = $reservation['payment_method'] ?? '';

  return "ご予約ありがとうございます✨\n\n"
    . "【日時】{$date} {$start}〜{$end}\n"
    . "【担当】{$staff}\n"
    . "【メニュー】{$menu}\n"
    . "【お名前】{$customer} 様\n"
    . "【お支払い】{$payment}\n\n"
    . "ご来店を心よりお待ちしております。";
}

function format_admin_reservation_message(array $reservation): string {
  $date = $reservation['date'] ?? '';
  $start = $reservation['start_time'] ?? '';
  $end = $reservation['end_time'] ?? '';
  $customer = $reservation['customer_name'] ?? '';
  $phone = $reservation['phone'] ?? '';
  $staff = $reservation['staff_name'] ?? '';
  $menu = $reservation['menu_name'] ?? '';

  return "新規予約が入りました💅\n\n"
    . "【日時】{$date} {$start}〜{$end}\n"
    . "【担当】{$staff}\n"
    . "【メニュー】{$menu}\n"
    . "【お名前】{$customer} 様\n"
    . "【電話】{$phone}";
}
