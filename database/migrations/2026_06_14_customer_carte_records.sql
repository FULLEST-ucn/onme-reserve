CREATE TABLE IF NOT EXISTS customer_carte_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  nail_condition TEXT NULL,
  color VARCHAR(255) NULL,
  materials VARCHAR(255) NULL,
  design_note TEXT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_customer_carte_records_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
