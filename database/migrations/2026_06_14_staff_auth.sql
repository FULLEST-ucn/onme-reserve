ALTER TABLE staffs
  ADD COLUMN IF NOT EXISTS login_id VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS role VARCHAR(30) NULL DEFAULT 'staff';

UPDATE staffs SET login_id = LOWER(name) WHERE login_id IS NULL OR login_id = '';

-- 初期パスワード: 1234
UPDATE staffs SET password_hash = '$2y$10$wHn/yLIrCtUm.IjaJ2ZuF.KKbNS1y4b76XxGxdwzd93k6FccxCy9K'
WHERE password_hash IS NULL OR password_hash = '';
