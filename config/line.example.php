<?php
/**
 * LINE Messaging API settings
 *
 * 使い方：
 * 1. このファイルを line.php にコピー
 * 2. LINE Developers の Messaging API チャネルアクセストークンを設定
 * 3. スターサーバーへアップロード
 */
return [
  'channel_access_token' => 'YOUR_LINE_CHANNEL_ACCESS_TOKEN',
  'admin_user_ids' => [
    // 店舗通知を受けたいLINE userIdを入れます
    // 'Uxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
  ],
];
