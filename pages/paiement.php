<?php
requireRole(['commercant','admin']);
$msg='';$step='montant';
if($_SERVER['REQUEST_METHOD']==='POST'){
if(isset($_POST['nouveau'])){unset($_SESSION['paiement_montant']);$step='montant';}
elseif(isset($_POST['annuler'])){unset($_SESSION['paiement_montant']);$step='montant';}
elseif(isset($_POST['activer'])){
$m=round(floatval($_POST['montant']??0),2);
if($m>0){$step='scan';$_SESSION['paiement_montant']=$m;}
else{$msg='<div class="alert-error">Montant invalide</div>';}}
elseif(isset($_POST['scanner_client'])){
$m=$_SESSION['paiement_montant']??0;
if($m<=0){$msg='<div class="alert-error">Entrez un montant d abord</div>';}
else{$uid=scanBadge();
if(!$uid){$step='scan';$msg='<div class="alert-error">Aucun badge detecte. Le client doit approcher son badge.</div>';}
else{$s=$db->prepare('SELECT * FROM users WHERE badge_uid=? AND actif=1 AND role="utilisateur"');$s->execute([$uid]);$c=$s->fetch(PDO::FETCH_ASSOC);
if(!$c){$step='scan';$msg='<div class="alert-error">Badge non reconnu ou compte non autorise</div>';}
elseif($c['solde']<$m){$step='done';unset($_SESSION['paiement_montant']);$msg='<div class="alert-error">Paiement refuse - Solde insuffisant</div>';}
else{$db->prepare('UPDATE users SET solde=solde-? WHERE id=?')->execute([$m,$c['id']]);
$db->prepare('INSERT INTO transactions(user_id,commercant_id,type,montant,description)VALUES(?,?,?,?,?)')->execute([$c['id'],$user['id'],'paiement',$m,"Paiement de {$m} EUR chez {$user['prenom']} {$user['nom']}"]);
$step='done';unset($_SESSION['paiement_montant']);
$msg='<div class="alert-success"><strong>Paiement accepte !</strong><br>Montant : '.$m.' EUR<br>Client : '.$c['prenom'].' '.substr($c['nom'],0,1).'.</div>';}}}}}
if(isset($_SESSION['paiement_montant'])&&$step==='montant'){$step='scan';}
?>
<div class="card"><h2>Encaisser un paiement</h2><?=$msg?>
<?php if($step==='montant'):?>
<form method="POST">
<div class="form-group"><label>Montant a encaisser (EUR)</label>
<input type="number" name="montant" min="0.01" step="0.01" placeholder="0.00" required style="font-size:2em;text-align:center;padding:20px">
</div>
<button type="submit" name="activer" value="1" class="btn btn-primary btn-full" style="padding:16px;font-size:1.1em"><i data-lucide="nfc" style="width:20px;height:20px"></i> Activer le paiement sans contact</button>
</form>
<?php elseif($step==='scan'):?>
<div class="scan-zone" style="border-color:rgba(99,102,241,0.5);background:rgba(99,102,241,0.05)">
<i data-lucide="nfc" style="width:64px;height:64px;color:#a5b4fc"></i>
<p style="font-size:1.5em;color:white;margin:16px 0"><strong><?=$_SESSION['paiement_montant']?> EUR</strong></p>
<p style="color:rgba(255,255,255,0.5);margin-bottom:20px">En attente du badge client...</p>
<form method="POST">
<button type="submit" name="scanner_client" value="1" class="btn btn-success" style="padding:16px 40px;font-size:1.1em"><i data-lucide="scan-line" style="width:20px;height:20px"></i> Lire le badge</button>
</form>
<form method="POST" style="margin-top:12px">
<button type="submit" name="annuler" value="1" class="btn btn-danger btn-small">Annuler</button>
</form></div>
<?php else:?>
<form method="POST">
<button type="submit" name="nouveau" value="1" class="btn btn-primary btn-full" style="padding:16px"><i data-lucide="plus" style="width:20px;height:20px"></i> Nouveau paiement</button>
</form>
<?php endif;?></div>
<script>lucide.createIcons();</script>
