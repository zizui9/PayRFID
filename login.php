<?php
require_once 'config.php';
$error='';$badge_msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
if(isset($_POST['scan_login'])){
$uid=scanBadge();
if($uid){$s=$db->prepare('SELECT * FROM users WHERE badge_uid=? AND actif=1');$s->execute([$uid]);$u=$s->fetch(PDO::FETCH_ASSOC);
if($u){$_SESSION['user_id']=$u['id'];$_SESSION['username']=$u['username'];$_SESSION['nom']=$u['nom'];$_SESSION['prenom']=$u['prenom'];$_SESSION['role']=$u['role'];header('Location: index.php');exit;}
else{$badge_msg='Badge non reconnu';}}else{$badge_msg='Aucun badge detecte. Reessayez.';}}
if(isset($_POST['login'])){$s=$db->prepare('SELECT * FROM users WHERE username=? AND actif=1');$s->execute([$_POST['username']??'']);$u=$s->fetch(PDO::FETCH_ASSOC);
if($u&&verifyPassword($_POST['password']??'',$u['password'])){$_SESSION['user_id']=$u['id'];$_SESSION['username']=$u['username'];$_SESSION['nom']=$u['nom'];$_SESSION['prenom']=$u['prenom'];$_SESSION['role']=$u['role'];header('Location: index.php');exit;}
$error='Identifiants incorrects';}}?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>PayRFID</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Outfit',sans-serif;min-height:100vh;display:flex;background:#06080f;overflow:hidden}
.left{flex:1;display:flex;flex-direction:column;justify-content:center;padding:60px 80px;position:relative;z-index:1}
.right{flex:1;position:relative;overflow:hidden}
.right::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,#0f172a,#1e1b4b 40%,#312e81 70%,#4f46e5)}
.right::after{content:'';position:absolute;top:50%;left:50%;width:500px;height:500px;transform:translate(-50%,-50%);border-radius:50%;background:radial-gradient(circle,rgba(99,102,241,0.3),transparent 70%);animation:glow 4s ease-in-out infinite alternate}
@keyframes glow{0%{transform:translate(-50%,-50%) scale(1)}100%{transform:translate(-50%,-50%) scale(1.3)}}
.rc{position:relative;z-index:1;height:100%;display:flex;flex-direction:column;justify-content:center;align-items:center;padding:60px;color:white}
.dots{position:absolute;top:0;left:0;width:100%;height:100%;background-image:radial-gradient(rgba(255,255,255,0.05) 1px,transparent 1px);background-size:30px 30px}
.brand{margin-bottom:40px}.brand h1{font-size:2.5em;font-weight:800;color:white}.brand h1 span{background:linear-gradient(135deg,#6366f1,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.brand p{color:rgba(255,255,255,0.4);margin-top:8px}
.scan-box{background:rgba(99,102,241,0.08);border:2px dashed rgba(99,102,241,0.3);border-radius:20px;padding:32px;text-align:center;margin-bottom:32px}
.scan-box i{color:#a5b4fc;margin-bottom:12px}
.scan-box p{color:rgba(255,255,255,0.5);margin-bottom:16px}
.scan-btn{padding:14px 32px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:white;border:none;border-radius:14px;font-size:1em;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif}
.scan-btn:hover{box-shadow:0 12px 40px rgba(99,102,241,0.5)}
.divider{display:flex;align-items:center;gap:16px;margin-bottom:24px;color:rgba(255,255,255,0.2);font-size:0.85em}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:rgba(255,255,255,0.1)}
.fg{margin-bottom:16px}.fg label{display:block;color:rgba(255,255,255,0.5);font-size:0.8em;font-weight:500;margin-bottom:6px;text-transform:uppercase;letter-spacing:1px}
.fg input{width:100%;padding:14px 16px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:12px;color:white;font-size:1em;font-family:'Outfit',sans-serif}
.fg input:focus{outline:none;border-color:rgba(99,102,241,0.6);box-shadow:0 0 0 4px rgba(99,102,241,0.15)}
.fg input::placeholder{color:rgba(255,255,255,0.2)}
.bl{width:100%;padding:14px;background:rgba(255,255,255,0.08);color:white;border:1px solid rgba(255,255,255,0.1);border-radius:12px;font-size:0.95em;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif}
.bl:hover{background:rgba(255,255,255,0.12)}
.err{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#fca5a5;padding:12px;border-radius:12px;margin-bottom:16px;text-align:center;font-size:0.9em}
.succ{background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.2);color:#6ee7b7;padding:12px;border-radius:12px;margin-bottom:16px;text-align:center}
.ft{margin-top:40px;color:rgba(255,255,255,0.2);font-size:0.8em}
.nfc-anim{width:120px;height:120px;margin:0 auto 24px;position:relative}
.nfc-circle{position:absolute;top:50%;left:50%;border:2px solid rgba(165,180,252,0.3);border-radius:50%;animation:nfcpulse 2s ease-out infinite}
.nfc-circle:nth-child(1){width:40px;height:40px;margin:-20px 0 0 -20px;animation-delay:0s}
.nfc-circle:nth-child(2){width:70px;height:70px;margin:-35px 0 0 -35px;animation-delay:0.3s}
.nfc-circle:nth-child(3){width:100px;height:100px;margin:-50px 0 0 -50px;animation-delay:0.6s}
@keyframes nfcpulse{0%{transform:scale(0.8);opacity:1}100%{transform:scale(1.3);opacity:0}}
@media(max-width:900px){.right{display:none}.left{padding:40px}}
</style></head><body>
<div class="left"><div class="dots"></div><div style="position:relative;z-index:1;max-width:420px">
<div class="brand"><h1>Pay<span>RFID</span></h1><p>Paiement sans contact securise</p></div>
<?php if($badge_msg):?><div class="err"><?=$badge_msg?></div><?php endif;?>
<form method="POST"><div class="scan-box">
<i data-lucide="nfc" style="width:48px;height:48px"></i>
<p><strong>Scannez votre badge</strong> pour vous connecter</p>
<button type="submit" name="scan_login" value="1" class="scan-btn"><i data-lucide="scan-line" style="width:18px;height:18px;vertical-align:middle;margin-right:8px"></i>Scanner mon badge</button>
</div></form>
<div class="divider">ou connectez-vous avec vos identifiants</div>
<?php if($error):?><div class="err"><?=$error?></div><?php endif;?>
<form method="POST"><div class="fg"><label>Identifiant</label><input type="text" name="username" placeholder="Votre identifiant" required></div>
<div class="fg"><label>Mot de passe</label><input type="password" name="password" placeholder="Votre mot de passe" required></div>
<button type="submit" name="login" value="1" class="bl">Se connecter</button></form>
<div class="ft">Projet Services Reseaux 2026 - ESIGELEC</div></div></div>
<div class="right"><div class="rc">
<div class="nfc-anim"><div class="nfc-circle"></div><div class="nfc-circle"></div><div class="nfc-circle"></div></div>
<h2 style="font-size:1.5em;font-weight:700;margin-bottom:12px">Authentification RFID</h2>
<p style="color:rgba(255,255,255,0.5);max-width:300px;text-align:center;line-height:1.6">Approchez votre badge du lecteur pour vous identifier instantanement et acceder a vos services.</p>
</div></div>
<script>lucide.createIcons();</script>
</body></html>
