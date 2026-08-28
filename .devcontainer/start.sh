#!/bin/bash
# =========================================================
# Dijalankan SETIAP KALI Codespace dinyalakan/di-resume
# (lihat postStartCommand pada .devcontainer/devcontainer.json)
# =========================================================
cd "$(dirname "$0")/.."   # pindah ke root folder project

if sudo mysqladmin ping >/dev/null 2>&1; then
  echo ">> MariaDB sudah berjalan."
else
  echo ">> Menyalakan MariaDB..."
  sudo rm -f /run/mysqld/mysqld.pid /run/mysqld/mysqld.sock 2>/dev/null
  sudo service mariadb start
  for i in $(seq 1 20); do
    if sudo mysqladmin ping >/dev/null 2>&1; then break; fi
    sleep 1
  done
fi

echo ">> Menyalakan server PHP di port 8080..."
pkill -f "php -S 0.0.0.0:8080" 2>/dev/null
sleep 1
nohup php -S 0.0.0.0:8080 > /tmp/php-server.log 2>&1 < /dev/null &
disown -a

sleep 1
echo ""
echo "=================================================="
echo " SERVER SIAP!"
echo " Buka tab 'PORTS' di bagian bawah VS Code,"
echo " klik ikon globe di sebelah port 8080 untuk membuka,"
echo " atau salin link-nya untuk dibagikan ke teman."
echo "=================================================="
