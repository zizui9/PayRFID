#!/bin/bash
# Appelé par PHP (scanBadge) : renvoie l'UID du badge, ou rien après 10 s.
# `timeout` garantit que le processus Apache n'est jamais bloqué indéfiniment.
set -euo pipefail
exec timeout 12 /opt/payrfid/venv/bin/python /opt/payrfid/scripts/read_badge.py 10
