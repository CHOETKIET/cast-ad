INSERT INTO users (
  username,
  password_hash,
  display_name,
  is_active,
  created_at,
  updated_at
) VALUES (
  'admin',
  '$2y$12$qD3s8ADkmElS2/eAqddczODerE/2KW69MrW8W/tAGm/haGaX7TLH2',
  'Administrator',
  1,
  NOW(),
  NOW()
);

INSERT INTO devices (
  device_id,
  device_name,
  status,
  is_active,
  created_at,
  updated_at
) VALUES (
  'SCREEN001',
  'Demo Screen 001',
  'offline',
  1,
  NOW(),
  NOW()
);
