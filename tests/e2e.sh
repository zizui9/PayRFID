#!/bin/bash
# Tests de bout en bout de PayRFID (environnement Docker, badge simulé)
B=http://localhost:8090/rfid; DIR="${1:-$(cd "$(dirname "$0")/.." && pwd)}"; BADGE="$DIR/docker/badge.txt"; T=$(mktemp -d)
pass=0; fail=0
ok(){ if [ "$1" = "$2" ]; then echo "  OK   $3"; pass=$((pass+1)); else echo "  FAIL $3 (attendu '$2', obtenu '$1')"; fail=$((fail+1)); fi; }
csrf(){ curl -s -b "$T/$1" -c "$T/$1" "$B/$2" | grep -o 'name="csrf" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
post(){ local jar=$1 url=$2; shift 2; curl -s -o /dev/null -w '%{http_code} %{redirect_url}' -b "$T/$jar" -c "$T/$jar" -X POST "$B/$url" "$@"; }
sql(){ docker exec payrfid-db-1 mariadb -uroot -proot rfid_paiement -N -e "$1"; }
login(){ # jar user badge
  local t; t=$(csrf $1 login.php)
  post $1 login.php -d "csrf=$t&login=1&username=$2&password=demo1234" >/dev/null
  echo "$3" > "$BADGE"; t=$(csrf $1 login.php)
  post $1 login.php -d "csrf=$t&scan_2fa=1" | cut -d' ' -f1
}

echo "== Double authentification"
t=$(csrf a login.php); r=$(post a login.php -d "csrf=$t&login=1&username=admin&password=demo1234")
ok "$(echo $r | cut -d' ' -f1)" 302 "mot de passe correct -> étape badge (pas encore connecté)"
ok "$(curl -s -o /dev/null -w '%{http_code}' -b $T/a $B/index.php)" 302 "index.php refusé tant que le badge n'est pas lu"
echo "FFFFFFFF" > "$BADGE"; t=$(csrf a login.php)
ok "$(curl -s -b $T/a -c $T/a -X POST $B/login.php -d "csrf=$t&scan_2fa=1" | grep -c 'ne correspond pas')" 1 "mauvais badge refusé"
echo "AD000001" > "$BADGE"; t=$(csrf a login.php)
ok "$(post a login.php -d "csrf=$t&scan_2fa=1" | cut -d' ' -f1)" 302 "bon badge -> connecté"
ok "$(curl -s -b $T/a $B/index.php | grep -q 'Tableau de bord' && echo 1)" 1 "admin voit son tableau de bord"
t=$(csrf z login.php)
ok "$(curl -s -b $T/z -c $T/z -X POST $B/login.php -d "csrf=$t&login=1&username=admin&password=mauvais" | grep -c 'Identifiants incorrects')" 1 "mauvais mot de passe refusé"

echo "== Sécurité"
ok "$(post a index.php?page=crediter -d 'user_id=3&montant=50' | cut -d' ' -f1)" 400 "POST sans jeton CSRF rejeté"
ok "$(curl -s -o /dev/null -w '%{http_code}' -b $T/a "$B/index.php?page=../config")" 404 "inclusion ?page=../config bloquée"
ok "$(login c client1 A1B2C3D4)" 302 "client connecté (mot de passe + badge)"
ok "$(curl -s -o /dev/null -w '%{http_code}' -b $T/c "$B/index.php?page=users")" 404 "client ne peut pas ouvrir la gestion des utilisateurs"

echo "== Crédit (transaction + Post/Redirect/Get)"
t=$(csrf a "index.php?page=crediter")
ok "$(post a "index.php?page=crediter" -d "csrf=$t&user_id=3&montant=10" | cut -d' ' -f1)" 302 "crédit de 10 € -> redirection (PRG)"
ok "$(sql 'SELECT solde FROM users WHERE id=3')" 10.00 "solde client = 10,00"

echo "== Paiement en 3 étapes"
ok "$(login m boulange C0000001)" 302 "commerçant connecté"
pay(){ local t; t=$(csrf m "index.php?page=paiement"); post m "index.php?page=paiement" -d "csrf=$t&activer=1&montant=$1" >/dev/null
  echo "$2" > "$BADGE"; t=$(csrf m "index.php?page=paiement"); post m "index.php?page=paiement" -d "csrf=$t&scanner_client=1" >/dev/null
  curl -s -b $T/m "$B/index.php?page=paiement" | grep -o 'Paiement accepté\|solde insuffisant\|non reconnu' | head -1; }
ok "$(pay 4 A1B2C3D4)" "Paiement accepté" "paiement 4 € accepté"
ok "$(sql 'SELECT solde FROM users WHERE id=3')" 6.00 "solde client = 6,00"
ok "$(pay 9 A1B2C3D4)" "solde insuffisant" "paiement 9 € refusé (solde 6 €)"
ok "$(pay 2 C0000001)" "non reconnu" "badge d'un commerçant refusé comme moyen de paiement"
ok "$(pay 1000 A1B2C3D4)" "" "montant au-dessus du plafond refusé dès l'étape 1"

echo "== Concurrence : 2 terminaux paient 5 € en même temps (solde 6 €)"
ok "$(login m2 boulange C0000001)" 302 "second terminal connecté"
for j in m m2; do t=$(csrf $j "index.php?page=paiement"); post $j "index.php?page=paiement" -d "csrf=$t&activer=1&montant=5" >/dev/null; done
echo A1B2C3D4 > "$BADGE"
t1=$(csrf m "index.php?page=paiement"); t2=$(csrf m2 "index.php?page=paiement")
post m "index.php?page=paiement" -d "csrf=$t1&scanner_client=1" >/dev/null & post m2 "index.php?page=paiement" -d "csrf=$t2&scanner_client=1" >/dev/null & wait
ok "$(sql "SELECT COUNT(*) FROM transactions WHERE type='paiement' AND montant=5")" 1 "un seul des deux paiements passe"
ok "$(sql 'SELECT solde FROM users WHERE id=3')" 1.00 "solde final 1,00 € (jamais négatif)"

echo "== Tableaux de bord"
ok "$(curl -s -b $T/m "$B/index.php?page=tableau" | grep -c 'Chiffre d&#039;affaires du jour\|Chiffre d.affaires du jour')" 1 "tableau de bord commerçant affiché"
ok "$(curl -s -b $T/a "$B/index.php?page=dashboard" | grep -c 'cohérent')" 1 "rapprochement soldes / transactions cohérent"
echo; echo "Résultat : $pass OK, $fail échec(s)"; rm -rf "$T"
