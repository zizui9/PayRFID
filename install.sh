#!/bin/bash
echo "==========================================="
echo "   PayRFID - Installation"
echo "==========================================="
if [ "$EUID" -ne 0 ]; then echo "Lancez avec : sudo ./install.sh"; exit 1; fi
echo "[1/4] Verification des dependances..."
apt-get update -qq
apt-get install -y -qq apache2 php libapache2-mod-php php-mysql default-mysql-server git
echo "[2/4] Configuration base de donnees..."
mysql -e "CREATE DATABASE IF NOT EXISTS rfid_paiement;"
mysql -e "CREATE USER IF NOT EXISTS 'rfid_user'@'localhost' IDENTIFIED BY 'rfid2026';"
mysql -e "GRANT ALL PRIVILEGES ON rfid_paiement.* TO 'rfid_user'@'localhost'; FLUSH PRIVILEGES;"
mysql rfid_paiement << 'SQL'
CREATE TABLE IF NOT EXISTS users(id INT AUTO_INCREMENT PRIMARY KEY,username VARCHAR(50) UNIQUE NOT NULL,password VARCHAR(255) NOT NULL,nom VARCHAR(100) NOT NULL,prenom VARCHAR(100) NOT NULL,role ENUM('admin','utilisateur','commercant') NOT NULL,badge_uid VARCHAR(20) UNIQUE,solde DECIMAL(10,2) DEFAULT 0.00,actif TINYINT(1) DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS transactions(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,commercant_id INT,type ENUM('credit','paiement') NOT NULL,montant DECIMAL(10,2) NOT NULL,description VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id),FOREIGN KEY(commercant_id) REFERENCES users(id));
SQL
ADMIN=$(mysql rfid_paiement -N -e "SELECT COUNT(*) FROM users WHERE username='admin';")
if [ "$ADMIN" -eq 0 ]; then
HASH=$(php -r "echo password_hash('admin123', PASSWORD_DEFAULT);")
mysql rfid_paiement -e "INSERT INTO users(username,password,nom,prenom,role,actif) VALUES('admin','$HASH','Administrateur','Systeme','admin',1);"
echo "  Compte admin cree (login: admin / mdp: admin123)"
fi
echo "[3/4] Deploiement fichiers..."
DEST="/var/www/html/rfid"
mkdir -p "$DEST/pages"
cp config.php login.php logout.php index.php "$DEST/"
cp pages/*.php "$DEST/pages/"
chown -R www-data:www-data "$DEST"
echo "[4/4] Droits RFID..."
grep -q "tag_detect" /etc/sudoers || echo "www-data ALL=(ALL) NOPASSWD: /home/pi/script/tag_detect.sh" >> /etc/sudoers
systemctl restart apache2
IP=$(hostname -I | awk '{print $1}')
echo ""
echo "==========================================="
echo "   Installation terminee !"
echo "   Ouvrez : http://$IP/rfid/"
echo "   Login : admin / Mot de passe : admin123"
echo "==========================================="
