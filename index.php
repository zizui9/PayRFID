<?php
require_once 'config.php';requireLogin();
$s=$db->prepare('SELECT * FROM users WHERE id=?');$s->execute([$_SESSION['user_id']]);$user=$s->fetch(PDO::FETCH_ASSOC);$_SESSION['role']=$user['role'];
$page=$_GET['page']??($user['role']==='admin'?'dashboard':($user['role']==='commercant'?'paiement':'compte'));?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>PayRFID</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Outfit',sans-serif;background:#06080f;color:#e2e8f0;min-height:100vh}
.topbar{display:flex;justify-content:space-between;align-items:center;padding:16px 32px;background:rgba(255,255,255,0.03);border-bottom:1px solid rgba(255,255,255,0.06)}
.tb{font-size:1.3em;font-weight:800;color:white}.tb span{background:linear-gradient(135deg,#6366f1,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.tr{display:flex;align-items:center;gap:16px}.tu{color:rgba(255,255,255,0.6);font-size:0.9em}
.role{background:rgba(99,102,241,0.15);color:#a5b4fc;padding:4px 12px;border-radius:20px;font-size:0.75em;font-weight:600;text-transform:uppercase}
.lo{background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.6);padding:8px 16px;border-radius:10px;text-decoration:none;font-size:0.85em;display:flex;align-items:center;gap:6px}
.lo:hover{background:rgba(255,255,255,0.1);color:white}
.layout{display:flex;min-height:calc(100vh - 65px)}
.sidebar{width:240px;padding:24px 16px;background:rgba(255,255,255,0.02);border-right:1px solid rgba(255,255,255,0.06)}
.sidebar a{display:flex;align-items:center;gap:12px;padding:12px 16px;color:rgba(255,255,255,0.5);text-decoration:none;border-radius:12px;margin-bottom:4px;font-size:0.92em;font-weight:500}
.sidebar a:hover{background:rgba(255,255,255,0.05);color:white}.sidebar a.active{background:rgba(99,102,241,0.15);color:#a5b4fc}
.content{flex:1;padding:32px;max-width:960px}
.card{background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:28px;margin-bottom:20px}
.card h2{font-size:1.15em;font-weight:700;color:white;margin-bottom:20px;display:flex;align-items:center;gap:10px}
.solde-hero{background:linear-gradient(135deg,#312e81,#4f46e5,#6366f1);border-radius:20px;padding:40px;text-align:center;margin-bottom:24px}
.solde-hero .label{color:rgba(255,255,255,0.6);font-size:0.9em;text-transform:uppercase;letter-spacing:2px}
.solde-hero .amount{font-size:3.5em;font-weight:800;color:white;margin-top:8px}.solde-hero .currency{font-size:0.4em;font-weight:400;opacity:0.7}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:24px;text-align:center}
.stat-card .number{font-size:2em;font-weight:800;background:linear-gradient(135deg,#6366f1,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.stat-card .label{font-size:0.8em;color:rgba(255,255,255,0.4);margin-top:4px;text-transform:uppercase;letter-spacing:1px}
table{width:100%;border-collapse:collapse}th{text-align:left;padding:12px 16px;color:rgba(255,255,255,0.4);font-size:0.75em;font-weight:600;text-transform:uppercase;border-bottom:1px solid rgba(255,255,255,0.08)}
td{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,0.04);font-size:0.9em;color:rgba(255,255,255,0.7)}tr:hover td{background:rgba(255,255,255,0.02)}
.btn{padding:10px 20px;border:none;border-radius:10px;cursor:pointer;font-weight:600;font-family:'Outfit',sans-serif;font-size:0.9em;display:inline-flex;align-items:center;gap:6px;text-decoration:none}
.btn-primary{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:white}.btn-success{background:linear-gradient(135deg,#059669,#10b981);color:white}
.btn-danger{background:rgba(239,68,68,0.15);color:#fca5a5;border:1px solid rgba(239,68,68,0.2)}.btn-small{padding:6px 14px;font-size:0.8em;border-radius:8px}.btn-full{width:100%;justify-content:center}
.form-group{margin-bottom:16px}.form-group label{display:block;color:rgba(255,255,255,0.5);font-size:0.8em;font-weight:600;margin-bottom:6px;text-transform:uppercase;letter-spacing:1px}
.form-group input,.form-group select{width:100%;padding:12px 16px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:white;font-size:0.95em;font-family:'Outfit',sans-serif}
.form-group input:focus,.form-group select:focus{outline:none;border-color:rgba(99,102,241,0.5);box-shadow:0 0 0 3px rgba(99,102,241,0.15)}
.form-group input::placeholder{color:rgba(255,255,255,0.2)}.form-group select option{background:#1e1b4b;color:white}
.form-row{display:flex;gap:12px}.form-row .form-group{flex:1}
.alert-success{background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.2);color:#6ee7b7;padding:14px 18px;border-radius:12px;margin-bottom:16px}
.alert-error{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#fca5a5;padding:14px 18px;border-radius:12px;margin-bottom:16px}
.credit-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px}
.credit-btn{padding:14px;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.2);border-radius:12px;color:#6ee7b7;font-size:1.1em;font-weight:700;cursor:pointer;font-family:'Outfit',sans-serif}
.credit-btn:hover{background:rgba(16,185,129,0.2);transform:translateY(-2px)}
.scan-zone{border:2px dashed rgba(99,102,241,0.3);border-radius:20px;padding:40px;text-align:center;margin:20px 0}
.scan-zone p{color:rgba(255,255,255,0.4)}
.info-row{padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.06);display:flex;justify-content:space-between}.info-row:last-child{border-bottom:none}
.info-label{color:rgba(255,255,255,0.4)}.info-value{color:white;font-weight:500}
.badge-active{color:#6ee7b7;font-weight:600}.badge-inactive{color:#fca5a5;font-weight:600}
.type-credit{color:#6ee7b7;font-weight:600}.type-paiement{color:#fca5a5;font-weight:600}
</style></head><body>
<div class="topbar"><div class="tb">Pay<span>RFID</span></div><div class="tr">
<span class="tu"><?=htmlspecialchars($user['prenom'].' '.$user['nom'])?></span>
<span class="role"><?=$user['role']?></span>
<a href="logout.php" class="lo"><i data-lucide="log-out" style="width:16px;height:16px"></i>Deconnexion</a></div></div>
<div class="layout"><div class="sidebar">
<?php if($user['role']==='admin'):?>
<a href="?page=dashboard" class="<?=$page==='dashboard'?'active':''?>"><i data-lucide="layout-dashboard" style="width:18px;height:18px"></i><span>Tableau de bord</span></a>
<a href="?page=users" class="<?=$page==='users'?'active':''?>"><i data-lucide="users" style="width:18px;height:18px"></i><span>Utilisateurs</span></a>
<a href="?page=crediter" class="<?=$page==='crediter'?'active':''?>"><i data-lucide="wallet" style="width:18px;height:18px"></i><span>Crediter</span></a>
<?php elseif($user['role']==='commercant'):?>
<a href="?page=paiement" class="<?=$page==='paiement'?'active':''?>"><i data-lucide="credit-card" style="width:18px;height:18px"></i><span>Encaisser</span></a>
<a href="?page=historique" class="<?=$page==='historique'?'active':''?>"><i data-lucide="receipt" style="width:18px;height:18px"></i><span>Historique</span></a>
<?php else:?>
<a href="?page=compte" class="<?=$page==='compte'?'active':''?>"><i data-lucide="user" style="width:18px;height:18px"></i><span>Mon compte</span></a>
<a href="?page=historique" class="<?=$page==='historique'?'active':''?>"><i data-lucide="receipt" style="width:18px;height:18px"></i><span>Historique</span></a>
<?php endif;?></div>
<div class="content"><?php $f="pages/$page.php";if(file_exists($f))include $f;else echo'<div class="card"><h2>Page non trouvee</h2></div>';?></div>
</div><script>lucide.createIcons();</script></body></html>
