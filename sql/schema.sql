-- PayRFID : schéma de la base (MySQL / MariaDB, moteur InnoDB pour les transactions)
CREATE TABLE IF NOT EXISTS users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(50)  NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,                 -- hash bcrypt (password_hash)
  nom        VARCHAR(100) NOT NULL,
  prenom     VARCHAR(100) NOT NULL,
  role       ENUM('admin','utilisateur','commercant') NOT NULL,
  badge_uid  VARCHAR(20)  UNIQUE,                   -- UID du badge RFID, second facteur
  solde      DECIMAL(10,2) NOT NULL DEFAULT 0.00,   -- montant exact, jamais un FLOAT
  actif      TINYINT(1)   NOT NULL DEFAULT 1,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_solde_positif CHECK (solde >= 0)   -- filet de sécurité : pas de découvert
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  commercant_id INT,
  type          ENUM('credit','paiement') NOT NULL,
  montant       DECIMAL(10,2) NOT NULL,
  description   VARCHAR(255),
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)       REFERENCES users(id),
  FOREIGN KEY (commercant_id) REFERENCES users(id),
  INDEX idx_tr_user (user_id, created_at),
  INDEX idx_tr_commercant (commercant_id, type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
