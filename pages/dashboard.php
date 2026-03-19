<?php
requireRole('admin');
$nu = $db->query("SELECT COUNT(*) FROM users WHERE role='utilisateur'")->fetchColumn();
$nc = $db->query("SELECT COUNT(*) FROM users WHERE role='commercant'")->fetchColumn();
$nt = $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$tc = $db->query("SELECT COALESCE(SUM(montant),0) FROM transactions WHERE type='credit'")->fetchColumn();
$tp = $db->query("SELECT COALESCE(SUM(montant),0) FROM transactions WHERE type='paiement'")->fetchColumn();
?>
<h2>Tableau de bord</h2>
<div class="stats-grid">
<div class="stat-card"><div class="number"><?=$nu?></div><div class="label">Utilisateurs</div></div>
<div class="stat-card"><div class="number"><?=$nc?></div><div class="label">Commercants</div></div>
<div class="stat-card"><div class="number"><?=$nt?></div><div class="label">Transactions</div></div>
<div class="stat-card"><div class="number"><?=number_format($tc,2)?> EUR</div><div class="label">Total credite</div></div>
<div class="stat-card"><div class="number"><?=number_format($tp,2)?> EUR</div><div class="label">Total paiements</div></div>
</div>
