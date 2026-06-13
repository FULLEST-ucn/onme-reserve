CREATE TABLE IF NOT EXISTS customer_photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_customer_photos_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
