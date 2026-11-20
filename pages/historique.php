<?php
// Historique : ventes encaissées (commerçant) ou mouvements du compte (client).
requireRole(['commercant', 'utilisateur']);

if ($user['role'] === 'commercant') {
    $s = $db->prepare("SELECT t.*, u.prenom AS cp, u.nom AS cn FROM transactions t
                       JOIN users u ON t.user_id = u.id
                       WHERE t.commercant_id = ? ORDER BY t.created_at DESC LIMIT 50");
} else {
    $s = $db->prepare("SELECT t.*, u.prenom AS cp, u.nom AS cn FROM transactions t
                       LEFT JOIN users u ON t.commercant_id = u.id
                       WHERE t.user_id = ? ORDER BY t.created_at DESC LIMIT 50");
}
$s->execute([$user['id']]);
$tr = $s->fetchAll();
?>
<div class="card"><h2>Historique des transactions</h2>
<?php if (!$tr): ?>
  <p style="color:rgba(255,255,255,0.3);text-align:center;padding:40px">Aucune transaction</p>
<?php else: ?>
  <table><thead><tr><th>Date</th><th>Type</th><th>Montant</th><th>Description</th></tr></thead><tbody>
  <?php foreach ($tr as $t): $credit = $t['type'] === 'credit'; ?>
    <tr>
      <td><?= e(date('d/m/Y H:i', strtotime($t['created_at']))) ?></td>
      <td class="<?= $credit ? 'type-credit' : 'type-paiement' ?>"><?= $credit ? 'Crédit' : 'Paiement' ?></td>
      <td style="font-weight:600"><?= $credit ? '+' : '-' ?><?= e(number_format((float) $t['montant'], 2, ',', ' ')) ?> EUR</td>
      <td><?= e($t['description']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table>
<?php endif; ?>
</div>
