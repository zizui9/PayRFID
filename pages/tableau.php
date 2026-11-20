<?php
// Tableau de bord du commerçant : son activité uniquement.
requireRole('commercant');

$s = $db->prepare("SELECT
        COALESCE(SUM(montant), 0)                                         AS ca_total,
        COUNT(*)                                                          AS nb_ventes,
        COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN montant END), 0) AS ca_jour,
        COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END), 0)       AS ventes_jour,
        COALESCE(AVG(montant), 0)                                         AS panier_moyen
    FROM transactions
    WHERE type = 'paiement' AND commercant_id = ?");
$s->execute([$user['id']]);
$st = $s->fetch();

$s = $db->prepare("SELECT t.montant, t.created_at, u.prenom, u.nom
    FROM transactions t JOIN users u ON u.id = t.user_id
    WHERE t.type = 'paiement' AND t.commercant_id = ?
    ORDER BY t.created_at DESC LIMIT 10");
$s->execute([$user['id']]);
$ventes = $s->fetchAll();

$eur = fn($v) => e(number_format((float) $v, 2, ',', ' '));
?>
<div class="solde-hero" style="background:linear-gradient(135deg,#064e3b,#059669,#10b981)">
  <div class="label">Chiffre d'affaires du jour</div>
  <div class="amount"><?= $eur($st['ca_jour']) ?><span class="currency">EUR</span></div>
  <div style="margin-top:12px;color:rgba(255,255,255,0.7)"><?= (int) $st['ventes_jour'] ?> vente(s) aujourd'hui</div>
</div>
<div class="stats-grid">
  <div class="stat-card"><div class="number"><?= $eur($st['ca_total']) ?> €</div><div class="label">CA total</div></div>
  <div class="stat-card"><div class="number"><?= (int) $st['nb_ventes'] ?></div><div class="label">Ventes</div></div>
  <div class="stat-card"><div class="number"><?= $eur($st['panier_moyen']) ?> €</div><div class="label">Panier moyen</div></div>
</div>
<div class="card"><h2><i data-lucide="receipt" style="width:20px;height:20px"></i> Dernières ventes</h2>
<?php if (!$ventes): ?>
  <p style="color:rgba(255,255,255,0.3);text-align:center;padding:30px">Aucune vente pour le moment</p>
<?php else: ?>
  <table><thead><tr><th>Date</th><th>Client</th><th>Montant</th></tr></thead><tbody>
  <?php foreach ($ventes as $v): ?>
    <tr><td><?= e(date('d/m/Y H:i', strtotime($v['created_at']))) ?></td>
        <td><?= e($v['prenom'] . ' ' . mb_substr($v['nom'], 0, 1)) ?>.</td>
        <td style="font-weight:600"><?= $eur($v['montant']) ?> €</td></tr>
  <?php endforeach; ?>
  </tbody></table>
<?php endif; ?>
</div>
<script>lucide.createIcons();</script>
