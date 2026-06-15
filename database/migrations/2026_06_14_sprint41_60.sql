USE onme_reserve;

CREATE TABLE IF NOT EXISTS customer_tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  color VARCHAR(30) NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customer_tag_links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  tag_id INT NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_customer_tag (customer_id, tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS review_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  reservation_id INT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  review_url TEXT NULL,
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS staff_commissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_id INT NOT NULL,
  month VARCHAR(7) NOT NULL,
  sales INT NOT NULL DEFAULT 0,
  commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
  commission_amount INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_staff_month (staff_id, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inventory_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  sku VARCHAR(100) NULL,
  stock INT NOT NULL DEFAULT 0,
  unit_cost INT NOT NULL DEFAULT 0,
  sale_price INT NOT NULL DEFAULT 0,
  alert_stock INT NOT NULL DEFAULT 3,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inventory_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  movement_type VARCHAR(50) NOT NULL,
  quantity INT NOT NULL,
  memo VARCHAR(255) NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pwa_push_subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NULL,
  endpoint TEXT NOT NULL,
  public_key TEXT NULL,
  auth_token TEXT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ai_chat_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_type VARCHAR(50) NOT NULL,
  user_id INT NULL,
  message TEXT NOT NULL,
  reply TEXT NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS salon_goals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  month VARCHAR(7) NOT NULL,
  sales_goal INT NOT NULL DEFAULT 0,
  reservation_goal INT NOT NULL DEFAULT 0,
  repeat_rate_goal INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_goal_month (month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
