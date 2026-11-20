<?php
// Crédit d'un compte client par un administrateur.
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['montant'])) {
    $uid = (int) ($_POST['user_id'] ?? 0);
    $brut = $_POST['montant'] === 'perso' ? ($_POST['montant_perso'] ?? 0) : $_POST['montant'];
    $m = round((float) $brut, 2);

    if ($m <= 0 || $m > MONTANT_MAX_CREDIT || $uid <= 0) {
        flash('err', 'Montant ou utilisateur invalide.');
        redirect('?page=crediter');
    }

    try {
        $db->beginTransaction();
        $s = $db->prepare("UPDATE users SET solde = solde + ? WHERE id = ? AND role = 'utilisateur' AND actif = 1");
        $s->execute([$m, $uid]);
        if ($s->rowCount() !== 1) {
            $db->rollBack();
            flash('err', 'Utilisateur introuvable ou inactif.');
            redirect('?page=crediter');
        }
        $db->prepare("INSERT INTO transactions (user_id, type, montant, description) VALUES (?, 'credit', ?, ?)")
           ->execute([$uid, $m, 'Crédit par l\'administrateur']);
        $db->commit();
    } catch (Throwable $ex) {
        if ($db->inTransaction()) { $db->rollBack(); }
        error_log('[PayRFID] Crédit annulé : ' . $ex->getMessage());
        flash('err', 'Erreur technique : crédit annulé.');
        redirect('?page=crediter');
    }

    // Post/Redirect/Get : rafraîchir la page ne recrédite pas le compte.
    flash('ok', e(number_format($m, 2, ',', ' ')) . ' EUR crédité avec succès.');
    redirect('?page=crediter');
}

$users = $db->query("SELECT id, prenom, nom, solde FROM users WHERE role = 'utilisateur' AND actif = 1 ORDER BY nom")->fetchAll();
?>
<div class="card"><h2>Créditer un compte utilisateur</h2>
<form method="POST"><?= csrfField() ?>
  <div class="form-group"><label>Utilisateur</label>
    <select name="user_id" required>
      <option value="">-- Choisir --</option>
      <?php foreach ($users as $u): ?>
        <option value="<?= (int) $u['id'] ?>"><?= e($u['prenom'] . ' ' . $u['nom']) ?> (<?= e(number_format($u['solde'], 2, ',', ' ')) ?> EUR)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="credit-grid">
    <?php foreach ([5, 10, 20, 50] as $v): ?>
      <button type="submit" name="montant" value="<?= $v ?>" class="credit-btn">+<?= $v ?> EUR</button>
    <?php endforeach; ?>
  </div>
  <div class="form-group"><label>Montant personnalisé (EUR)</label>
    <input type="number" name="montant_perso" min="0.01" max="<?= MONTANT_MAX_CREDIT ?>" step="0.01" placeholder="Ex : 15.00">
  </div>
  <button type="submit" name="montant" value="perso" class="btn btn-primary btn-full">Créditer le compte</button>
</form>
</div>
