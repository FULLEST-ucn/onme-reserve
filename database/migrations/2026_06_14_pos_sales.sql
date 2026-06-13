CREATE TABLE IF NOT EXISTS pos_sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reservation_id INT NULL,
  customer_id INT NULL,
  staff_id INT NULL,
  subtotal INT NOT NULL DEFAULT 0,
  discount INT NOT NULL DEFAULT 0,
  total INT NOT NULL DEFAULT 0,
  payment_method VARCHAR(50) NOT NULL,
  memo TEXT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_pos_sales_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
