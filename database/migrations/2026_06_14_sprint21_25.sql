USE onme_reserve;

CREATE TABLE IF NOT EXISTS stores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  phone VARCHAR(50) NULL,
  address VARCHAR(255) NULL,
  line_liff_id VARCHAR(255) NULL,
  line_channel_token TEXT NULL,
  google_calendar_id VARCHAR(255) NULL,
  stripe_public_key VARCHAR(255) NULL,
  stripe_secret_key VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO stores (id, name, slug, phone, address, created_at, updated_at)
VALUES (1, 'ON;ME NAIL', 'onme-nail', '', '', NOW(), NOW());

ALTER TABLE staffs ADD COLUMN store_id INT NULL DEFAULT 1;
ALTER TABLE customers ADD COLUMN store_id INT NULL DEFAULT 1;
ALTER TABLE menus ADD COLUMN store_id INT NULL DEFAULT 1;
ALTER TABLE menu_options ADD COLUMN store_id INT NULL DEFAULT 1;
ALTER TABLE reservations ADD COLUMN store_id INT NULL DEFAULT 1;
ALTER TABLE availability ADD COLUMN store_id INT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS notification_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NULL DEFAULT 1,
  customer_id INT NULL,
  reservation_id INT NULL,
  channel VARCHAR(50) NOT NULL,
  event_type VARCHAR(100) NOT NULL,
  recipient VARCHAR(255) NULL,
  message TEXT NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  response TEXT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ai_insights (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NULL DEFAULT 1,
  insight_type VARCHAR(100) NOT NULL,
  title VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  priority INT NOT NULL DEFAULT 3,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stripe_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NULL DEFAULT 1,
  reservation_id INT NULL,
  customer_id INT NULL,
  amount INT NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'jpy',
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  checkout_url TEXT NULL,
  provider_ref VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS google_calendar_sync (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NULL DEFAULT 1,
  reservation_id INT NOT NULL,
  google_event_id VARCHAR(255) NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  last_error TEXT NULL,
  synced_at DATETIME NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rich_menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NULL DEFAULT 1,
  label VARCHAR(100) NOT NULL,
  url TEXT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO rich_menu_items (store_id, label, url, sort_order, is_active, created_at)
SELECT 1, '予約する', '/customer/', 1, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM rich_menu_items WHERE label='予約する');

INSERT INTO rich_menu_items (store_id, label, url, sort_order, is_active, created_at)
SELECT 1, 'マイページ', '/customer/mypage.php', 2, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM rich_menu_items WHERE label='マイページ');
