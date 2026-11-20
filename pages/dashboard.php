<?php
// Tableau de bord administrateur : vue globale du système.
requireRole('admin');

$st = $db->query("SELECT
    (SELECT COUNT(*) FROM users WHERE role = 'utilisateur') AS nu,
    (SELECT COUNT(*) FROM users WHERE role = 'commercant')  AS nc,
    (SELECT COUNT(*) FROM transactions)                     AS nt,
    (SELECT COALESCE(SUM(montant), 0) FROM transactions WHERE type = 'credit')   AS tc,
    (SELECT COALESCE(SUM(montant), 0) FROM transactions WHERE type = 'paiement') AS tp,
    (SELECT COALESCE(SUM(solde), 0) FROM users WHERE role = 'utilisateur')       AS soldes")->fetch();
$eur = fn($v) => e(number_format((float) $v, 2, ',', ' '));
// Contrôle de cohérence : la somme des soldes doit valoir crédits - paiements.
$ecart = round($st['tc'] - $st['tp'] - $st['soldes'], 2);
?>
<h2 style="margin-bottom:20px">Tableau de bord</h2>
<div class="stats-grid">
  <div class="stat-card"><div class="number"><?= (int) $st['nu'] ?></div><div class="label">Clients</div></div>
  <div class="stat-card"><div class="number"><?= (int) $st['nc'] ?></div><div class="label">Commerçants</div></div>
  <div class="stat-card"><div class="number"><?= (int) $st['nt'] ?></div><div class="label">Transactions</div></div>
  <div class="stat-card"><div class="number"><?= $eur($st['tc']) ?> €</div><div class="label">Total crédité</div></div>
  <div class="stat-card"><div class="number"><?= $eur($st['tp']) ?> €</div><div class="label">Total paiements</div></div>
</div>
<div class="<?= $ecart == 0 ? 'alert-success' : 'alert-error' ?>">
  Rapprochement : soldes clients <?= $eur($st['soldes']) ?> € — crédits moins paiements <?= $eur($st['tc'] - $st['tp']) ?> €
  <?= $ecart == 0 ? '✓ cohérent' : '⚠ écart de ' . $eur($ecart) . ' €' ?>
</div>
