<?php requireLogin();
if($user['role']==='commercant'){
$ca=$db->prepare("SELECT COALESCE(SUM(montant),0) FROM transactions WHERE type='paiement' AND commercant_id=?");$ca->execute([$user['id']]);$total_ca=$ca->fetchColumn();
$nb=$db->prepare("SELECT COUNT(*) FROM transactions WHERE type='paiement' AND commercant_id=?");$nb->execute([$user['id']]);$nb_ventes=$nb->fetchColumn();
?>
<div class="solde-hero" style="background:linear-gradient(135deg,#064e3b,#059669,#10b981)">
<div class="label">Chiffre d'affaires total</div>
<div class="amount"><?=number_format($total_ca,2)?><span class="currency">EUR</span></div>
<div style="margin-top:12px;color:rgba(255,255,255,0.6)"><?=$nb_ventes?> vente<?=$nb_ventes>1?'s':''?> encaissee<?=$nb_ventes>1?'s':''?></div>
</div>
<?php }else{ ?>
<div class="solde-hero">
<div class="label">Votre solde</div>
<div class="amount"><?=number_format($user['solde'],2)?><span class="currency">EUR</span></div>
</div>
<?php } ?>
<div class="card">
<h2><i data-lucide="<?=$user['role']==='commercant'?'store':'user'?>" style="width:20px;height:20px"></i> Mes informations</h2>
<div class="info-row"><span class="info-label">Nom complet</span><span class="info-value"><?=htmlspecialchars($user['prenom'].' '.$user['nom'])?></span></div>
<div class="info-row"><span class="info-label">Identifiant</span><span class="info-value"><?=htmlspecialchars($user['username'])?></span></div>
<div class="info-row"><span class="info-label">Badge RFID</span><span class="info-value"><?=$user['badge_uid']?:'Aucun badge'?></span></div>
<div class="info-row"><span class="info-label">Role</span><span class="info-value"><?=ucfirst($user['role'])?></span></div>
</div>
<script>lucide.createIcons();</script>
