#!/usr/bin/env python3
"""Lit l'UID d'un badge sur le lecteur RC522 (bus SPI) et l'affiche en hexadécimal.

Code de sortie 0 : badge lu (UID sur la sortie standard).
Code de sortie 1 : aucun badge pendant le délai d'attente.
Bibliothèque : mfrc522 (pip), qui s'appuie sur spidev et RPi.GPIO.
"""
import sys
import time

import RPi.GPIO as GPIO
from mfrc522 import MFRC522

DELAI_SECONDES = float(sys.argv[1]) if len(sys.argv) > 1 else 10.0


def main() -> int:
    lecteur = MFRC522()
    fin = time.monotonic() + DELAI_SECONDES
    try:
        while time.monotonic() < fin:
            # 1. Requête : y a-t-il une carte dans le champ (13,56 MHz) ?
            statut, _ = lecteur.MFRC522_Request(lecteur.PICC_REQIDL)
            if statut == lecteur.MI_OK:
                # 2. Anticollision : récupère l'UID (4 octets + octet de contrôle BCC)
                statut, uid = lecteur.MFRC522_Anticoll()
                if statut == lecteur.MI_OK:
                    print("".join(f"{octet:02X}" for octet in uid[:4]))
                    return 0
            time.sleep(0.1)
        return 1
    finally:
        GPIO.cleanup()


if __name__ == "__main__":
    sys.exit(main())
