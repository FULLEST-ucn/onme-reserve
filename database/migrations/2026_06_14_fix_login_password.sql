USE onme_reserve;

UPDATE staffs
SET
  password_hash = '$2y$12$/.9RZibuEMSQe8BFS/i.k.iYAIMdWCf0aVqakScHiQ1iJOnIQ65JO',
  role = 'owner'
WHERE login_id IN ('owner', 'kiho', 'yuina')
   OR LOWER(name) IN ('owner', 'kiho', 'yuina');

UPDATE staffs
SET login_id = LOWER(name)
WHERE login_id IS NULL OR login_id = '';
