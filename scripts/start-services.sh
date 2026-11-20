#!/bin/bash
# Démarrage de PayRFID au boot du Raspberry Pi (lancé par payrfid.service).
# Vérifie le matériel, démarre la base puis le serveur web, et journalise.
set -uo pipefail
LOG=/var/log/payrfid.log
log() { echo "$(date '+%F %T') $*" | tee -a "$LOG"; }

log "Démarrage de PayRFID"

# 1. Bus SPI : sans lui, le lecteur RC522 est inutilisable.
if [ -e /dev/spidev0.0 ]; then
  log "SPI OK (/dev/spidev0.0)"
else
  log "ERREUR : bus SPI absent. Activez-le : sudo raspi-config nonint do_spi 0 puis redémarrez."
fi

# 2. Base de données, puis serveur web (l'ordre compte : PHP a besoin de la base).
for svc in mariadb apache2; do
  if systemctl is-active --quiet "$svc"; then
    log "$svc déjà actif"
  elif systemctl start "$svc"; then
    log "$svc démarré"
  else
    log "ERREUR : impossible de démarrer $svc"; exit 1
  fi
done

# 3. Attente que la base réponde réellement (pas seulement que le service soit lancé).
for i in $(seq 1 15); do
  if mysqladmin ping --silent 2>/dev/null; then log "Base de données prête"; break; fi
  sleep 1
done

IP=$(hostname -I | awk '{print $1}')
log "PayRFID disponible sur http://$IP/rfid/"
