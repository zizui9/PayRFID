<?php
// Espace client : solde et informations du compte.
requireRole('utilisateur');
?>
<div class="solde-hero">
  <div class="label">Votre solde</div>
  <div class="amount"><?= e(number_format((float) $user['solde'], 2, ',', ' ')) ?><span class="currency">EUR</span></div>
</div>
<div class="card">
  <h2><i data-lucide="user" style="width:20px;height:20px"></i> Mes informations</h2>
  <div class="info-row"><span class="info-label">Nom complet</span><span class="info-value"><?= e($user['prenom'] . ' ' . $user['nom']) ?></span></div>
  <div class="info-row"><span class="info-label">Identifiant</span><span class="info-value"><?= e($user['username']) ?></span></div>
  <div class="info-row"><span class="info-label">Badge RFID</span><span class="info-value"><?= $user['badge_uid'] ? e($user['badge_uid']) : 'Aucun badge' ?></span></div>
  <div class="info-row"><span class="info-label">Pour recharger</span><span class="info-value">Adressez-vous à un administrateur</span></div>
</div>
<script>lucide.createIcons();</script>
