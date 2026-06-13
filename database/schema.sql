-- ON;ME RESERVE / ON;ME OS v2.0 database schema
-- Charset: utf8mb4

CREATE TABLE IF NOT EXISTS staffs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_no VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('owner','staff') NOT NULL DEFAULT 'staff',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menus (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  price INT NOT NULL DEFAULT 0,
  duration_minutes INT NOT NULL DEFAULT 90,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menu_options (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  price INT NOT NULL DEFAULT 0,
  duration_minutes INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  line_user_id VARCHAR(100) NULL UNIQUE,
  line_display_name VARCHAR(150) NULL,
  name VARCHAR(120) NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(190) NULL,
  memo TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS availability (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_id INT NOT NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_availability_staff_time (staff_id, start_datetime, end_datetime),
  CONSTRAINT fk_availability_staff FOREIGN KEY (staff_id) REFERENCES staffs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  staff_id INT NOT NULL,
  menu_id INT NOT NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  total_price INT NOT NULL DEFAULT 0,
  total_duration_minutes INT NOT NULL DEFAULT 0,
  payment_method ENUM('cash','credit','paypay') NULL,
  status ENUM('requested','confirmed','completed','cancelled','no_show') NOT NULL DEFAULT 'requested',
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_reservation_staff_time (staff_id, start_datetime, end_datetime),
  INDEX idx_reservation_customer (customer_id),
  CONSTRAINT fk_reservation_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_reservation_staff FOREIGN KEY (staff_id) REFERENCES staffs(id) ON DELETE CASCADE,
  CONSTRAINT fk_reservation_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reservation_options (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reservation_id INT NOT NULL,
  option_id INT NOT NULL,
  price INT NOT NULL DEFAULT 0,
  duration_minutes INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_reservation_option_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
  CONSTRAINT fk_reservation_option_option FOREIGN KEY (option_id) REFERENCES menu_options(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customer_notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  staff_id INT NULL,
  reservation_id INT NULL,
  note TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_note_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_note_staff FOREIGN KEY (staff_id) REFERENCES staffs(id) ON DELETE SET NULL,
  CONSTRAINT fk_note_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customer_photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  reservation_id INT NULL,
  file_path VARCHAR(255) NOT NULL,
  caption VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_photo_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_photo_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initial data
INSERT IGNORE INTO staffs (employee_no, name, password_hash, role) VALUES
('9999', 'OWNER', '$2y$10$z3FQjtEccN3oYgYs0mWamuYi87aLwy1.2l5BCOKd3z9D9Egm2/f3.', 'owner'),
('1001', 'KIHO', '$2y$10$z3FQjtEccN3oYgYs0mWamuYi87aLwy1.2l5BCOKd3z9D9Egm2/f3.', 'staff'),
('1002', 'YUINA', '$2y$10$z3FQjtEccN3oYgYs0mWamuYi87aLwy1.2l5BCOKd3z9D9Egm2/f3.', 'staff');
-- initial password for all: onme2026

INSERT IGNORE INTO menus (id, name, price, duration_minutes, sort_order) VALUES
(1, 'ワンカラー', 6500, 90, 10),
(2, 'グラデーション', 7000, 90, 20),
(3, 'フレンチ', 8000, 120, 30),
(4, 'マグネットネイル', 7500, 90, 40),
(5, '定額シンプル', 8000, 120, 50),
(6, '定額デザイン', 9500, 120, 60),
(7, '持ち込みデザイン', 11000, 150, 70),
(8, '長さ出し10本', 14500, 180, 80),
(9, 'フットワンカラー', 7500, 90, 90),
(10, 'オフのみ', 4000, 60, 100);

INSERT IGNORE INTO menu_options (id, name, price, duration_minutes, sort_order) VALUES
(1, 'オフあり', 1000, 30, 10),
(2, '長さ出し1〜3本', 1500, 30, 20),
(3, '長さ出し4〜6本', 3000, 60, 30),
(4, '亀裂補強', 500, 15, 40),
(5, 'パーツ追加', 500, 15, 50);
