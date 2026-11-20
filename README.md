# PayRFID — Système de paiement sans contact par badge RFID

Projet Services Réseaux 2026 — ESIGELEC · Ziad Manzou

Terminal de paiement embarqué sur Raspberry Pi : les clients paient chez les commerçants avec un badge RFID, à partir d'un solde rechargé par un administrateur.

## Architecture

```
Badge RFID ──13,56 MHz──▶ Lecteur RC522 ──SPI──▶ Raspberry Pi
                                                  ├─ scripts/read_badge.py  (lecture de l'UID, Python)
                                                  ├─ Apache + PHP 8         (application web)
                                                  └─ MariaDB / MySQL        (comptes, soldes, transactions)
```

- **PHP** appelle `scripts/tag_detect.sh`, qui lit l'UID du badge avec un délai maximal (`timeout`).
- **Apache** accède au bus SPI via les groupes système `spi` et `gpio`, sans `sudo`.
- **systemd** (`payrfid.service`) lance `start-services.sh` au démarrage : vérification du bus SPI, démarrage de la base puis d'Apache, journal dans `/var/log/payrfid.log`.

## Rôles

| Rôle | Accès |
|---|---|
| Administrateur | Tableau de bord global, gestion des comptes et des badges, crédit des comptes |
| Commerçant | Encaissement, tableau de bord des ventes, historique |
| Client | Solde, historique |

## Sécurité

- **Double authentification** : mot de passe (bcrypt) **puis** badge RFID associé au compte, avec 2 minutes et 3 essais maximum.
- **Paiement transactionnel** en 3 étapes (montant → badge → débit). Le débit et l'écriture de l'historique se font dans une transaction InnoDB, avec un verrou de ligne (`SELECT … FOR UPDATE`). Deux paiements simultanés ne peuvent pas créer de découvert, et la contrainte `CHECK (solde >= 0)` sert de filet de sécurité.
- **Requêtes préparées PDO** (sans émulation), **jetons CSRF** sur tous les formulaires, **échappement HTML** de toutes les sorties.
- **Contrôle d'accès par rôles** avec une liste blanche des pages, régénération de l'identifiant de session à la connexion, et cookie `HttpOnly` / `SameSite`.
- **Post/Redirect/Get** : rafraîchir la page ne rejoue jamais un crédit ou un paiement.
- Mots de passe de la base et de l'admin **générés aléatoirement** à l'installation, jamais versionnés.

## Installation (Raspberry Pi OS)

```bash
git clone https://github.com/zizui9/PayRFID.git && cd PayRFID
sudo ./install.sh
```

Le script active le SPI, installe Apache, PHP, MariaDB et la bibliothèque `mfrc522`, crée la base, déploie l'application et active le démarrage automatique. Il affiche le mot de passe admin une seule fois.

## Développement sans Raspberry Pi

```bash
docker compose -f docker-compose.dev.yml up -d   # http://localhost:8090/rfid/
echo A1B2C3D4 > docker/badge.txt                  # simule un badge posé sur le lecteur
./tests/e2e.sh                                    # 23 tests de bout en bout
```

Comptes de démonstration (mot de passe `demo1234`) : `admin` (badge `AD000001`), `boulange` (commerçant, `C0000001`), `client1` (`A1B2C3D4`).

## Structure

```
config.php            connexion PDO, session, CSRF, échappement, lecture du badge
login.php             connexion en deux facteurs
index.php             contrôle d'accès et routage par rôle
pages/                tableau de bord, paiement, crédit, utilisateurs, historique…
sql/schema.sql        schéma de la base
scripts/              lecture RC522, démarrage des services, unité systemd
tests/e2e.sh          tests de bout en bout (Docker)
```
