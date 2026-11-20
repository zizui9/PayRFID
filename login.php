<?php
// ─────────────────────────────────────────────────────────────
// Connexion en deux facteurs :
//   1. ce que l'utilisateur SAIT  : identifiant + mot de passe (bcrypt)
//   2. ce que l'utilisateur POSSÈDE : le badge RFID associé à son compte
// La session n'est ouverte qu'après les deux étapes.
// ─────────────────────────────────────────────────────────────
require_once 'config.php';

if (isLoggedIn()) { redirect('index.php'); }

$error = '';
$pending = $_SESSION['pending_2fa'] ?? null;

// Étape 2 expirée : on repart de zéro.
if ($pending && time() - $pending['since'] > DELAI_2FA_SECONDES) {
    unset($_SESSION['pending_2fa']);
    $pending = null;
    $error = 'Délai dépassé : reconnectez-vous.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // ── Étape 1 : identifiant + mot de passe ──
    if (isset($_POST['login'])) {
        $s = $db->prepare('SELECT * FROM users WHERE username = ? AND actif = 1');
        $s->execute([trim($_POST['username'] ?? '')]);
        $u = $s->fetch();

        if (!$u || !verifyPassword($_POST['password'] ?? '', $u['password'])) {
            usleep(300000); // ralentit les essais en force brute
            $error = 'Identifiants incorrects';
        } elseif ($u['badge_uid']) {
            // Mot de passe correct : on attend maintenant le badge.
            session_regenerate_id(true);
            $_SESSION['pending_2fa'] = ['user_id' => (int) $u['id'], 'since' => time(), 'tries' => 0];
            redirect('login.php');
        } elseif ($u['role'] === 'admin') {
            // Seule exception : un administrateur sans badge peut se connecter
            // pour associer son propre badge (premier démarrage).
            loginUser($u);
            flash('err', 'Associez un badge à votre compte pour activer la double authentification.');
            redirect('index.php?page=users');
        } else {
            $error = 'Aucun badge associé à ce compte : contactez un administrateur.';
        }
    }

    // ── Étape 2 : scan du badge ──
    if (isset($_POST['scan_2fa']) && $pending) {
        $s = $db->prepare('SELECT * FROM users WHERE id = ? AND actif = 1');
        $s->execute([$pending['user_id']]);
        $u = $s->fetch();
        $uid = scanBadge();

        if ($u && $uid !== null && hash_equals((string) $u['badge_uid'], $uid)) {
            loginUser($u);
            redirect('index.php');
        }

        $_SESSION['pending_2fa']['tries']++;
        if ($_SESSION['pending_2fa']['tries'] >= 3) {
            unset($_SESSION['pending_2fa']);
            $pending = null;
            $error = 'Trop d\'essais : reconnectez-vous.';
        } else {
            $pending = $_SESSION['pending_2fa'];
            $error = $uid === null ? 'Aucun badge détecté. Réessayez.' : 'Ce badge ne correspond pas à votre compte.';
        }
    }

    if (isset($_POST['annuler_2fa'])) {
        unset($_SESSION['pending_2fa']);
        redirect('login.php');
    }
}
?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>PayRFID</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
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
.steps{display:flex;gap:8px;margin-bottom:24px}.step{flex:1;padding:10px;border-radius:10px;text-align:center;font-size:0.8em;color:rgba(255,255,255,0.4);background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08)}
.step.on{color:#a5b4fc;border-color:rgba(99,102,241,0.5);background:rgba(99,102,241,0.1)}.step.done{color:#6ee7b7;border-color:rgba(16,185,129,0.3)}
.scan-box{background:rgba(99,102,241,0.08);border:2px dashed rgba(99,102,241,0.3);border-radius:20px;padding:32px;text-align:center;margin-bottom:16px}
.scan-box i{color:#a5b4fc;margin-bottom:12px}
.scan-box p{color:rgba(255,255,255,0.5);margin-bottom:16px}
.scan-btn{padding:14px 32px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:white;border:none;border-radius:14px;font-size:1em;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif}
.scan-btn:hover{box-shadow:0 12px 40px rgba(99,102,241,0.5)}
.link-btn{background:none;border:none;color:rgba(255,255,255,0.4);cursor:pointer;font-family:'Outfit',sans-serif;text-decoration:underline}
.fg{margin-bottom:16px}.fg label{display:block;color:rgba(255,255,255,0.5);font-size:0.8em;font-weight:500;margin-bottom:6px;text-transform:uppercase;letter-spacing:1px}
.fg input{width:100%;padding:14px 16px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:12px;color:white;font-size:1em;font-family:'Outfit',sans-serif}
.fg input:focus{outline:none;border-color:rgba(99,102,241,0.6);box-shadow:0 0 0 4px rgba(99,102,241,0.15)}
.fg input::placeholder{color:rgba(255,255,255,0.2)}
.bl{width:100%;padding:14px;background:rgba(255,255,255,0.08);color:white;border:1px solid rgba(255,255,255,0.1);border-radius:12px;font-size:0.95em;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif}
.bl:hover{background:rgba(255,255,255,0.12)}
.err{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#fca5a5;padding:12px;border-radius:12px;margin-bottom:16px;text-align:center;font-size:0.9em}
.ft{margin-top:40px;color:rgba(255,255,255,0.2);font-size:0.8em}
.nfc-anim{width:120px;height:120px;margin:0 auto 24px;position:relative}
.nfc-circle{position:absolute;top:50%;left:50%;border:2px solid rgba(165,180,252,0.3);border-radius:50%;animation:nfcpulse 2s ease-out infinite}
.nfc-circle:nth-child(1){width:40px;height:40px;margin:-20px 0 0 -20px;animation-delay:0s}
.nfc-circle:nth-child(2){width:70px;height:70px;margin:-35px 0 0 -35px;animation-delay:0.3s}
.nfc-circle:nth-child(3){width:100px;height:100px;margin:-50px 0 0 -50px;animation-delay:0.6s}
@keyframes nfcpulse{0%{transform:scale(0.8);opacity:1}100%{transform:scale(1.3);opacity:0}}
@media(max-width:900px){.right{display:none}.left{padding:40px}}
</style></head><body>
<div class="left"><div class="dots"></div><div style="position:relative;z-index:1;max-width:420px;width:100%">
<div class="brand"><h1>Pay<span>RFID</span></h1><p>Paiement sans contact sécurisé</p></div>

<div class="steps">
  <div class="step <?= $pending ? 'done' : 'on' ?>">1. Mot de passe</div>
  <div class="step <?= $pending ? 'on' : '' ?>">2. Badge RFID</div>
</div>

<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>

<?php if ($pending): ?>
  <form method="POST"><?= csrfField() ?>
    <div class="scan-box">
      <i data-lucide="nfc" style="width:48px;height:48px"></i>
      <p><strong>Approchez votre badge</strong> du lecteur pour terminer la connexion</p>
      <button type="submit" name="scan_2fa" value="1" class="scan-btn">Lire mon badge</button>
    </div>
  </form>
  <form method="POST" style="text-align:center"><?= csrfField() ?>
    <button type="submit" name="annuler_2fa" value="1" class="link-btn">Annuler</button>
  </form>
<?php else: ?>
  <form method="POST"><?= csrfField() ?>
    <div class="fg"><label>Identifiant</label><input type="text" name="username" placeholder="Votre identifiant" required autocomplete="username"></div>
    <div class="fg"><label>Mot de passe</label><input type="password" name="password" placeholder="Votre mot de passe" required autocomplete="current-password"></div>
    <button type="submit" name="login" value="1" class="bl">Continuer</button>
  </form>
<?php endif; ?>

<div class="ft">Projet Services Réseaux 2026 - ESIGELEC</div></div></div>
<div class="right"><div class="rc">
<div class="nfc-anim"><div class="nfc-circle"></div><div class="nfc-circle"></div><div class="nfc-circle"></div></div>
<h2 style="font-size:1.5em;font-weight:700;margin-bottom:12px">Double authentification</h2>
<p style="color:rgba(255,255,255,0.5);max-width:300px;text-align:center;line-height:1.6">Votre mot de passe, puis votre badge : un code que vous connaissez et un objet que vous possédez.</p>
</div></div>
<script>lucide.createIcons();</script>
</body></html>
