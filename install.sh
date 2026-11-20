#!/bin/bash
# ─────────────────────────────────────────────────────────────
# PayRFID — installation sur Raspberry Pi OS (Debian)
# Usage : sudo ./install.sh
# ─────────────────────────────────────────────────────────────
set -euo pipefail

if [ "$EUID" -ne 0 ]; then echo "Lancez avec : sudo ./install.sh"; exit 1; fi
cd "$(dirname "$0")"
WEB=/var/www/html/rfid
OPT=/opt/payrfid

echo "[1/6] Dépendances système..."
apt-get update -qq
apt-get install -y -qq apache2 php libapache2-mod-php php-mysql mariadb-server python3-venv python3-dev

echo "[2/6] Activation du bus SPI (lecteur RC522)..."
if command -v raspi-config >/dev/null; then raspi-config nonint do_spi 0; fi

echo "[3/6] Base de données..."
DB_PASS=$(openssl rand -hex 16)   # mot de passe aléatoire, jamais versionné
mysql -e "CREATE DATABASE IF NOT EXISTS rfid_paiement CHARACTER SET utf8mb4;"
mysql -e "CREATE USER IF NOT EXISTS 'rfid_user'@'localhost';"
mysql -e "ALTER USER 'rfid_user'@'localhost' IDENTIFIED BY '$DB_PASS';"
# Droits minimaux : l'application lit et écrit des données, elle ne modifie pas le schéma.
mysql -e "GRANT SELECT, INSERT, UPDATE ON rfid_paiement.* TO 'rfid_user'@'localhost'; FLUSH PRIVILEGES;"
mysql rfid_paiement < sql/schema.sql

ADMIN_PASS=""
if [ "$(mysql rfid_paiement -N -e "SELECT COUNT(*) FROM users WHERE username='admin';")" -eq 0 ]; then
  ADMIN_PASS=$(openssl rand -base64 12)
  HASH=$(php -r 'echo password_hash($argv[1], PASSWORD_DEFAULT);' "$ADMIN_PASS")
  mysql rfid_paiement -e "INSERT INTO users (username,password,nom,prenom,role) VALUES ('admin','$HASH','Administrateur','Système','admin');"
fi

echo "[4/6] Application web..."
mkdir -p "$WEB/pages"
cp config.php login.php logout.php index.php "$WEB/"
cp pages/*.php "$WEB/pages/"
cat > "$WEB/config.local.php" <<PHP
<?php
return ['db_pass' => '$DB_PASS'];
PHP
chown -R root:www-data "$WEB"
chmod 640 "$WEB/config.local.php"   # lisible par Apache, pas par les autres utilisateurs

echo "[5/6] Lecteur RFID..."
mkdir -p "$OPT/scripts"
cp scripts/read_badge.py scripts/tag_detect.sh scripts/start-services.sh "$OPT/scripts/"
python3 -m venv "$OPT/venv"
"$OPT/venv/bin/pip" install -q mfrc522 spidev RPi.GPIO
chown -R root:root "$OPT"; chmod 755 "$OPT/scripts/"*
# Apache accède au SPI et aux GPIO via les groupes système : pas besoin de sudo.
usermod -aG spi,gpio www-data

echo "[6/6] Démarrage automatique au boot..."
cp scripts/payrfid.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable --now payrfid.service
systemctl restart apache2   # prise en compte des nouveaux groupes de www-data

IP=$(hostname -I | awk '{print $1}')
echo ""
echo "==========================================="
echo "  Installation terminée : http://$IP/rfid/"
if [ -n "$ADMIN_PASS" ]; then
  echo "  Compte admin : admin / $ADMIN_PASS"
  echo "  (notez-le maintenant, il ne sera plus affiché)"
fi
echo "==========================================="
