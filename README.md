# PayRFID - Systeme de Paiement Sans Contact

Projet Services Reseaux 2026 - ESIGELEC
Auteur : Ziad Manzou - Groupe 24

## Description
Systeme de paiement sans contact par badge RFID sur Raspberry Pi.
Authentification par badge RFID, gestion des comptes, paiements securises.

## Installation
git clone <URL> && cd rfid && sudo chmod +x install.sh && sudo ./install.sh

## Utilisation
Ouvrir : http://<IP_DU_RASPBERRY>/rfid/
Compte admin : admin / admin123

## Roles
- Admin : gerer utilisateurs, associer badges, crediter comptes, dashboard
- Utilisateur : consulter solde, historique, connexion par badge
- Commercant : encaisser paiements par scan badge, voir CA

## Technologies
Raspberry Pi + RFID RC522 + Apache + PHP 7.4 + MySQL
Interface : Outfit font + Lucide Icons

## Structure
config.php login.php logout.php index.php install.sh README.md
pages/ : dashboard.php users.php compte.php crediter.php paiement.php historique.php
