<?php
requireRole('admin');$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
if(isset($_POST['create'])){try{$db->prepare('INSERT INTO users(username,password,nom,prenom,role)VALUES(?,?,?,?,?)')->execute([trim($_POST['username']),hashPassword($_POST['password']),trim($_POST['nom']),trim($_POST['prenom']),$_POST['role']]);$msg='<div class="alert-success">Utilisateur cree avec succes !</div>';}catch(Exception $e){$msg='<div class="alert-error">Cet identifiant existe deja</div>';}}
if(isset($_POST['toggle'])){$uid=intval($_POST['user_id']);
if($uid===$user['id']){$msg='<div class="alert-error">Vous ne pouvez pas desactiver votre propre compte</div>';}
else{$t=$db->prepare('SELECT role FROM users WHERE id=?');$t->execute([$uid]);$target=$t->fetch();
if($target['role']==='admin'){$msg='<div class="alert-error">Impossible de desactiver un administrateur</div>';}
else{$db->prepare('UPDATE users SET actif=NOT actif WHERE id=?')->execute([$uid]);$msg='<div class="alert-success">Statut modifie</div>';}}}
if(isset($_POST['scan_badge'])){$uid=intval($_POST['user_id']);$badge=scanBadge();
if($badge){try{$db->prepare('UPDATE users SET badge_uid=? WHERE id=?')->execute([$badge,$uid]);$msg='<div class="alert-success">Badge <strong>'.$badge.'</strong> associe !</div>';}catch(Exception $e){$msg='<div class="alert-error">Ce badge est deja utilise</div>';}}
else{$msg='<div class="alert-error">Aucun badge detecte</div>';}}
if(isset($_POST['manual_badge'])){$uid=intval($_POST['user_id']);$b=trim($_POST['badge_uid']??'');
if($b){try{$db->prepare('UPDATE users SET badge_uid=? WHERE id=?')->execute([$b,$uid]);$msg='<div class="alert-success">Badge associe !</div>';}catch(Exception $e){$msg='<div class="alert-error">Ce badge est deja utilise</div>';}}
else{$msg='<div class="alert-error">Entrez un UID</div>';}}}
$users=$db->query('SELECT * FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$cas=$db->query("SELECT commercant_id,COALESCE(SUM(montant),0) as ca FROM transactions WHERE type='paiement' AND commercant_id IS NOT NULL GROUP BY commercant_id")->fetchAll(PDO::FETCH_ASSOC);
$ca_map=[];foreach($cas as $c){$ca_map[$c['commercant_id']]=$c['ca'];}
?>
<?=$msg?>
<div class="card"><h2><i data-lucide="user-plus" style="width:20px;height:20px"></i> Creer un utilisateur</h2>
<form method="POST"><div class="form-row">
<div class="form-group"><label>Prenom</label><input type="text" name="prenom" required placeholder="Prenom"></div>
<div class="form-group"><label>Nom</label><input type="text" name="nom" required placeholder="Nom"></div></div>
<div class="form-row">
<div class="form-group"><label>Identifiant</label><input type="text" name="username" required placeholder="Login"></div>
<div class="form-group"><label>Mot de passe</label><input type="password" name="password" required placeholder="Mot de passe"></div></div>
<div class="form-group"><label>Role</label><select name="role"><option value="utilisateur">Utilisateur</option><option value="commercant">Commercant</option></select></div>
<button type="submit" name="create" value="1" class="btn btn-primary"><i data-lucide="plus" style="width:16px;height:16px"></i> Creer</button></form></div>
<div class="card"><h2><i data-lucide="users" style="width:20px;height:20px"></i> Utilisateurs</h2>
<?php foreach($users as $u):?>
<div style="display:flex;align-items:center;justify-content:space-between;padding:16px 0;border-bottom:1px solid rgba(255,255,255,0.06)">
<div style="display:flex;align-items:center;gap:16px;flex:1">
<div style="width:42px;height:42px;border-radius:12px;background:<?=$u['role']==='admin'?'rgba(99,102,241,0.2)':($u['role']==='commercant'?'rgba(16,185,129,0.2)':'rgba(255,255,255,0.08)')?>;display:flex;align-items:center;justify-content:center">
<i data-lucide="<?=$u['role']==='admin'?'shield':($u['role']==='commercant'?'store':'user')?>" style="width:20px;height:20px;color:<?=$u['role']==='admin'?'#a5b4fc':($u['role']==='commercant'?'#6ee7b7':'#e2e8f0')?>"></i></div>
<div><div style="color:white;font-weight:600"><?=htmlspecialchars($u['prenom'].' '.$u['nom'])?></div>
<div style="font-size:0.8em;color:rgba(255,255,255,0.3)">@<?=$u['username']?> · <span class="role"><?=$u['role']?></span></div></div></div>
<div style="display:flex;align-items:center;gap:20px">
<?php if($u['role']==='utilisateur'):?>
<div style="text-align:right"><div style="font-weight:700;color:white"><?=number_format($u['solde'],2)?> &euro;</div><div style="font-size:0.75em;color:rgba(255,255,255,0.3)">Solde</div></div>
<?php elseif($u['role']==='commercant'):?>
<div style="text-align:right"><div style="font-weight:700;color:#6ee7b7"><?=number_format($ca_map[$u['id']]??0,2)?> &euro;</div><div style="font-size:0.75em;color:rgba(255,255,255,0.3)">Chiffre affaires</div></div>
<?php endif;?>
<div style="text-align:center">
<?php if($u['badge_uid']):?><div style="font-size:0.8em;color:#a5b4fc"><?=$u['badge_uid']?></div>
<?php else:?><div style="font-size:0.8em;color:rgba(255,255,255,0.2)">Pas de badge</div><?php endif;?>
</div>
<div><span class="<?=$u['actif']?'badge-active':'badge-inactive'?>"><?=$u['actif']?'Actif':'Inactif'?></span></div>
<?php if($u['role']!=='admin'):?>
<div style="display:flex;gap:4px;align-items:center">
<form method="POST" style="margin:0"><input type="hidden" name="user_id" value="<?=$u['id']?>">
<button name="toggle" value="1" class="btn btn-small <?=$u['actif']?'btn-danger':'btn-success'?>"><?=$u['actif']?'Off':'On'?></button></form>
<form method="POST" style="margin:0"><input type="hidden" name="user_id" value="<?=$u['id']?>">
<button name="scan_badge" value="1" class="btn btn-small btn-primary"><i data-lucide="nfc" style="width:14px;height:14px"></i></button></form>
<form method="POST" style="margin:0;display:flex;gap:3px"><input type="hidden" name="user_id" value="<?=$u['id']?>">
<input type="text" name="badge_uid" placeholder="UID" style="width:85px;padding:5px 8px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:6px;color:white;font-size:0.75em">
<button name="manual_badge" value="1" class="btn btn-small btn-primary">OK</button></form></div>
<?php endif;?>
</div></div>
<?php endforeach;?></div>
<script>lucide.createIcons();</script>
