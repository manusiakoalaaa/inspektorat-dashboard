#!/bin/bash
# =========================================================
# Setup otomatis untuk GitHub Codespaces
# Dijalankan SEKALI saja saat Codespace pertama kali dibuat
# (lihat postCreateCommand pada .devcontainer/devcontainer.json)
# =========================================================
set -e
cd "$(dirname "$0")/.."   # pindah ke root folder project

echo "=================================================="
echo " [1/5] Menginstall PHP & MariaDB..."
echo "=================================================="
sudo apt-get update -y || true   # jangan berhenti walau ada 1 repo pihak-ketiga yg gagal
sudo apt-get install -y php-cli php-mysql mariadb-server

echo "=================================================="
echo " [2/5] Menyalakan MariaDB..."
echo "=================================================="
sudo service mariadb start
for i in $(seq 1 20); do
  if sudo mysqladmin ping >/dev/null 2>&1; then break; fi
  sleep 1
done
sudo mysqladmin ping

echo "=================================================="
echo " [3/5] Membuat user database khusus aplikasi..."
echo "=================================================="
# Membuat user terpisah (bukan root) yang login pakai password lewat TCP,
# supaya tidak tergantung user OS yang menjalankan PHP (menghindari
# masalah autentikasi 'auth_socket' bawaan MariaDB).
sudo mysql -u root <<'SQL'
CREATE USER IF NOT EXISTS 'inspektorat'@'localhost' IDENTIFIED BY 'inspektorat123';
CREATE USER IF NOT EXISTS 'inspektorat'@'127.0.0.1' IDENTIFIED BY 'inspektorat123';
CREATE USER IF NOT EXISTS 'inspektorat'@'%' IDENTIFIED BY 'inspektorat123';
SQL

echo "=================================================="
echo " [4/5] Import skema & data awal (database.sql)..."
echo "=================================================="
sudo mysql -u root < database.sql
sudo mysql -u root <<'SQL'
GRANT ALL PRIVILEGES ON inspektorat_reviu.* TO 'inspektorat'@'localhost';
GRANT ALL PRIVILEGES ON inspektorat_reviu.* TO 'inspektorat'@'127.0.0.1';
GRANT ALL PRIVILEGES ON inspektorat_reviu.* TO 'inspektorat'@'%';
FLUSH PRIVILEGES;
SQL

echo "=================================================="
echo " [5/5] Menyesuaikan config/database.php..."
echo "=================================================="
sed -i "s/\$DB_HOST = 'localhost';/\$DB_HOST = '127.0.0.1';/" config/database.php
sed -i "s/\$DB_USER = 'root';/\$DB_USER = 'inspektorat';/" config/database.php
sed -i "s/\$DB_PASS = '';/\$DB_PASS = 'inspektorat123';/" config/database.php

echo ""
echo "=================================================="
echo " SETUP SELESAI!"
echo " Server akan otomatis dinyalakan setelah ini."
echo "=================================================="
