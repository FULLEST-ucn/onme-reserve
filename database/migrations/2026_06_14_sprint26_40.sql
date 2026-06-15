USE onme_reserve;

CREATE TABLE IF NOT EXISTS customer_points (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  points INT NOT NULL DEFAULT 0,
  reason VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_customer_points_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  code VARCHAR(100) NOT NULL,
  discount_type VARCHAR(30) NOT NULL DEFAULT 'amount',
  discount_value INT NOT NULL DEFAULT 0,
  valid_from DATE NULL,
  valid_until DATE NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS consent_forms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  signature_data LONGTEXT NULL,
  signed_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_consent_forms_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS staff_shifts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_id INT NOT NULL,
  work_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'work',
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_staff_shift (staff_id, work_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menu_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE menus ADD COLUMN category_id INT NULL;
ALTER TABLE menus ADD COLUMN sort_order INT NOT NULL DEFAULT 0;
ALTER TABLE menus ADD COLUMN description TEXT NULL;

CREATE TABLE IF NOT EXISTS customer_subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  plan_name VARCHAR(255) NOT NULL,
  amount INT NOT NULL,
  billing_cycle VARCHAR(50) NOT NULL DEFAULT 'monthly',
  status VARCHAR(50) NOT NULL DEFAULT 'active',
  started_at DATE NOT NULL,
  ended_at DATE NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_customer_subscriptions_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ai_daily_reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_date DATE NOT NULL,
  title VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_ai_daily_report_date (report_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
