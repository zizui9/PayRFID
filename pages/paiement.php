<?php
// ─────────────────────────────────────────────────────────────
// Encaissement en 3 étapes, validées chacune côté serveur :
//   1. montant   : saisi par le commerçant (> 0 et ≤ plafond)
//   2. badge     : lu sur le lecteur, doit correspondre à un client actif
//   3. paiement  : débit + écriture dans l'historique, dans UNE transaction SQL
// ─────────────────────────────────────────────────────────────
requireRole(['commercant', 'admin']);

$step = isset($_SESSION['paiement_montant']) ? 'scan' : 'montant';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Étape 1 : montant ──
    if (isset($_POST['activer'])) {
        unset($_SESSION['paiement_montant']); // une nouvelle saisie remplace toujours l'ancienne
        $m = round((float) ($_POST['montant'] ?? 0), 2);
        if ($m <= 0 || $m > MONTANT_MAX_PAIEMENT) {
            flash('err', 'Montant invalide (entre 0,01 et ' . number_format(MONTANT_MAX_PAIEMENT, 2, ',', ' ') . ' €).');
        } else {
            $_SESSION['paiement_montant'] = $m;
        }
        redirect('?page=paiement');
    }

    if (isset($_POST['annuler'])) {
        unset($_SESSION['paiement_montant']);
        redirect('?page=paiement');
    }

    // ── Étapes 2 et 3 : badge puis débit atomique ──
    if (isset($_POST['scanner_client'])) {
        $m = $_SESSION['paiement_montant'] ?? 0;
        if ($m <= 0) {
            flash('err', 'Entrez d\'abord un montant.');
            redirect('?page=paiement');
        }

        $uid = scanBadge();
        if ($uid === null) {
            flash('err', 'Aucun badge détecté. Le client doit approcher son badge.');
            redirect('?page=paiement');
        }

        try {
            $db->beginTransaction();

            // FOR UPDATE verrouille la ligne du client jusqu'au COMMIT :
            // un second paiement simultané sur le même badge attend ici,
            // puis relit le solde déjà débité. Pas de découvert possible.
            $s = $db->prepare("SELECT id, prenom, nom, solde FROM users
                               WHERE badge_uid = ? AND actif = 1 AND role = 'utilisateur'
                               FOR UPDATE");
            $s->execute([$uid]);
            $client = $s->fetch();

            if (!$client) {
                $db->rollBack();
                flash('err', 'Badge non reconnu ou compte non autorisé.');
                redirect('?page=paiement');
            }

            if ((float) $client['solde'] < $m) {
                $db->rollBack();
                unset($_SESSION['paiement_montant']);
                flash('err', 'Paiement refusé : solde insuffisant.');
                redirect('?page=paiement');
            }

            $db->prepare('UPDATE users SET solde = solde - ? WHERE id = ?')
               ->execute([$m, $client['id']]);
            $db->prepare('INSERT INTO transactions (user_id, commercant_id, type, montant, description)
                          VALUES (?, ?, \'paiement\', ?, ?)')
               ->execute([$client['id'], $user['id'], $m, "Paiement chez {$user['prenom']} {$user['nom']}"]);

            $db->commit();
        } catch (Throwable $ex) {
            if ($db->inTransaction()) { $db->rollBack(); }
            error_log('[PayRFID] Paiement annulé : ' . $ex->getMessage());
            flash('err', 'Erreur technique : le paiement a été annulé, aucun débit effectué.');
            redirect('?page=paiement');
        }

        // Le montant en attente est consommé : renvoyer le formulaire
        // (double clic, F5) ne peut pas déclencher un second débit.
        unset($_SESSION['paiement_montant']);
        flash('ok', '<strong>Paiement accepté</strong><br>Montant : ' . e(number_format($m, 2, ',', ' ')) . ' €<br>Client : '
            . e($client['prenom'] . ' ' . mb_substr($client['nom'], 0, 1)) . '.');
        redirect('?page=paiement');
    }
}
?>
<div class="card"><h2>Encaisser un paiement</h2>
<?php if ($step === 'montant'): ?>
<form method="POST"><?= csrfField() ?>
  <div class="form-group"><label>Montant à encaisser (EUR)</label>
    <input type="number" name="montant" min="0.01" max="<?= MONTANT_MAX_PAIEMENT ?>" step="0.01" placeholder="0.00" required style="font-size:2em;text-align:center;padding:20px">
  </div>
  <button type="submit" name="activer" value="1" class="btn btn-primary btn-full" style="padding:16px;font-size:1.1em"><i data-lucide="nfc" style="width:20px;height:20px"></i> Activer le paiement sans contact</button>
</form>
<?php else: ?>
<div class="scan-zone" style="border-color:rgba(99,102,241,0.5);background:rgba(99,102,241,0.05)">
  <i data-lucide="nfc" style="width:64px;height:64px;color:#a5b4fc"></i>
  <p style="font-size:1.5em;color:white;margin:16px 0"><strong><?= e(number_format($_SESSION['paiement_montant'], 2, ',', ' ')) ?> EUR</strong></p>
  <p style="color:rgba(255,255,255,0.5);margin-bottom:20px">En attente du badge client...</p>
  <form method="POST"><?= csrfField() ?>
    <button type="submit" name="scanner_client" value="1" class="btn btn-success" style="padding:16px 40px;font-size:1.1em"><i data-lucide="scan-line" style="width:20px;height:20px"></i> Lire le badge</button>
  </form>
  <form method="POST" style="margin-top:12px"><?= csrfField() ?>
    <button type="submit" name="annuler" value="1" class="btn btn-danger btn-small">Annuler</button>
  </form>
</div>
<?php endif; ?>
</div>
<script>lucide.createIcons();</script>
