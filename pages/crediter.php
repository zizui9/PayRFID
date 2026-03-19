<?php
requireLogin();
$msg='';
if($user['role']==='admin'){
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['montant'])){
$uid=intval($_POST['user_id']??0);$m=round(floatval($_POST['montant']),2);
if($m>0&&$m<=1000&&$uid>0){
$db->prepare('UPDATE users SET solde=solde+? WHERE id=?')->execute([$m,$uid]);
$db->prepare('INSERT INTO transactions(user_id,type,montant,description)VALUES(?,?,?,?)')->execute([$uid,'credit',$m,"Credit de {$m} EUR"]);
$msg='<div class="alert-success">'.$m.' EUR credite avec succes !</div>';
}else{$msg='<div class="alert-error">Montant ou utilisateur invalide</div>';}}
$users=$db->query("SELECT id,prenom,nom,solde FROM users WHERE role='utilisateur' AND actif=1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card"><h2>Crediter un compte utilisateur</h2><?=$msg?>
<form method="POST">
<div class="form-group"><label>Utilisateur</label>
<select name="user_id" required>
<option value="">-- Choisir --</option>
<?php foreach($users as $u):?><option value="<?=$u['id']?>"><?=$u['prenom'].' '.$u['nom']?> (<?=number_format($u['solde'],2)?> EUR)</option><?php endforeach;?>
</select></div>
<div class="credit-grid">
<form method="POST"><input type="hidden" name="montant" value="5"><button class="credit-btn">+5 EUR</button></form>
<form method="POST"><input type="hidden" name="montant" value="10"><button class="credit-btn">+10 EUR</button></form>
<form method="POST"><input type="hidden" name="montant" value="20"><button class="credit-btn">+20 EUR</button></form>
<form method="POST"><input type="hidden" name="montant" value="50"><button class="credit-btn">+50 EUR</button></form>
</div>
<div class="form-group"><label>Montant personnalise (EUR)</label>
<input type="number" name="montant" min="0.01" max="1000" step="0.01" placeholder="Ex: 15.00"></div>
<button class="btn btn-primary btn-full">Crediter le compte</button></form></div>
<?php }else{ ?>
<div class="solde-hero">
<div class="label">Votre solde</div>
<div class="amount"><?=number_format($user['solde'],2)?><span class="currency">EUR</span></div>
</div>
<div class="card"><p style="color:rgba(255,255,255,0.5);text-align:center;padding:20px">Pour crediter votre compte, veuillez vous adresser a un administrateur.</p></div>
<?php } ?>
