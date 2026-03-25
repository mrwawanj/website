#!/bin/bash



echo "=== suntk ==="


read -p "DB Name: " DB_NAME
read -p "DB User: " DB_USER
read -sp "DB Pass: " DB_PASS
echo ""


read -p "Username: " NEW_USER
read -p "Email: " NEW_EMAIL
read -p "Password Hash (sudah di-encrypt): " WP_HASH


PREFIX=$(mysql -u "$DB_USER" -p"$DB_PASS" -N -e "SELECT SUBSTRING(TABLE_NAME,1,LENGTH(TABLE_NAME)-4) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name LIKE '%users' LIMIT 1" 2>/dev/null)


mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" << EOF 2>/dev/null
INSERT INTO ${PREFIX}users (user_login, user_pass, user_email, user_registered, display_name) 
VALUES ('$NEW_USER', '$WP_HASH', '$NEW_EMAIL', NOW(), '$NEW_USER');

SET @user_id = LAST_INSERT_ID();

INSERT INTO ${PREFIX}usermeta (user_id, meta_key, meta_value) 
VALUES 
(@user_id, '${PREFIX}capabilities', 'a:1:{s:13:"administrator";b:1;}'),
(@user_id, '${PREFIX}user_level', '10');
EOF

if [ $? -eq 0 ]; then
    echo "✓ Sukses! User $NEW_USER (admin) ditambahkan"
else
    echo "✗ Gagal! Cek koneksi database"
fi
