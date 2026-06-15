USE onme_reserve;

ALTER TABLE customers
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;

UPDATE customers SET is_active = 1 WHERE is_active IS NULL;
