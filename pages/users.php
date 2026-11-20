<?php
// Gestion des comptes : création, activation, association des badges.
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int) ($_POST['user_id'] ?? 0);

    if (isset($_POST['create'])) {
        $role = $_POST['role'] ?? '';
        $login = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        if (!in_array($role, ['utilisateur', 'commercant'], true)) {
            flash('err', 'Rôle invalide.');
        } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $login)) {
            flash('err', 'Identifiant invalide (3 à 50 caractères : lettres, chiffres, . _ -).');
        } elseif (strlen($pass) < 8) {
            flash('err', 'Le mot de passe doit contenir au moins 8 caractères.');
        } else {
            try {
                $db->prepare('INSERT INTO users (username, password, nom, prenom, role) VALUES (?, ?, ?, ?, ?)')
                   ->execute([$login, hashPassword($pass), trim($_POST['nom'] ?? ''), trim($_POST['prenom'] ?? ''), $role]);
                flash('ok', 'Utilisateur créé. Associez-lui un badge pour qu\'il puisse se connecter.');
            } catch (PDOException $ex) {
                flash('err', 'Cet identifiant existe déjà.');
            }
        }
        redirect('?page=users');
    }

    if (isset($_POST['toggle'])) {
        $t = $db->prepare('SELECT role FROM users WHERE id = ?');
        $t->execute([$uid]);
        $target = $t->fetch();
        if (!$target) {
            flash('err', 'Utilisateur introuvable.');
        } elseif ($target['role'] === 'admin') {
            flash('err', 'Impossible de désactiver un administrateur.');
        } else {
            $db->prepare('UPDATE users SET actif = NOT actif WHERE id = ?')->execute([$uid]);
            flash('ok', 'Statut modifié.');
        }
        redirect('?page=users');
    }

    if (isset($_POST['scan_badge']) || isset($_POST['manual_badge'])) {
        $badge = isset($_POST['scan_badge']) ? scanBadge() : strtoupper(trim($_POST['badge_uid'] ?? ''));
        if (!$badge || !preg_match('/^[0-9A-F]{8,14}$/', $badge)) {
            flash('err', isset($_POST['scan_badge']) ? 'Aucun badge détecté.' : 'UID invalide (8 à 14 caractères hexadécimaux).');
        } else {
            try {
                $db->prepare('UPDATE users SET badge_uid = ? WHERE id = ?')->execute([$badge, $uid]);
                flash('ok', 'Badge <strong>' . e($badge) . '</strong> associé.');
            } catch (PDOException $ex) {
                flash('err', 'Ce badge est déjà utilisé.');
            }
        }
        redirect('?page=users');
    }
}

$users = $db->query('SELECT * FROM users ORDER BY id')->fetchAll();
$ca_map = $db->query("SELECT commercant_id, SUM(montant) AS ca FROM transactions
                      WHERE type = 'paiement' AND commercant_id IS NOT NULL GROUP BY commercant_id")
             ->fetchAll(PDO::FETCH_KEY_PAIR);
$eur = fn($v) => e(number_format((float) $v, 2, ',', ' '));
$ICONES = ['admin' => ['shield', 'rgba(99,102,241,0.2)', '#a5b4fc'], 'commercant' => ['store', 'rgba(16,185,129,0.2)', '#6ee7b7'], 'utilisateur' => ['user', 'rgba(255,255,255,0.08)', '#e2e8f0']];
?>
<div class="card"><h2><i data-lucide="user-plus" style="width:20px;height:20px"></i> Créer un utilisateur</h2>
<form method="POST"><?= csrfField() ?>
  <div class="form-row">
    <div class="form-group"><label>Prénom</label><input type="text" name="prenom" required placeholder="Prénom"></div>
    <div class="form-group"><label>Nom</label><input type="text" name="nom" required placeholder="Nom"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Identifiant</label><input type="text" name="username" required placeholder="Login"></div>
    <div class="form-group"><label>Mot de passe</label><input type="password" name="password" required minlength="8" placeholder="8 caractères minimum"></div>
  </div>
  <div class="form-group"><label>Rôle</label><select name="role"><option value="utilisateur">Client</option><option value="commercant">Commerçant</option></select></div>
  <button type="submit" name="create" value="1" class="btn btn-primary"><i data-lucide="plus" style="width:16px;height:16px"></i> Créer</button>
</form></div>

<div class="card"><h2><i data-lucide="users" style="width:20px;height:20px"></i> Utilisateurs</h2>
<?php foreach ($users as $u): [$ic, $bg, $col] = $ICONES[$u['role']]; ?>
<div style="display:flex;align-items:center;justify-content:space-between;padding:16px 0;border-bottom:1px solid rgba(255,255,255,0.06)">
  <div style="display:flex;align-items:center;gap:16px;flex:1">
    <div style="width:42px;height:42px;border-radius:12px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center"><i data-lucide="<?= $ic ?>" style="width:20px;height:20px;color:<?= $col ?>"></i></div>
    <div><div style="color:white;font-weight:600"><?= e($u['prenom'] . ' ' . $u['nom']) ?></div>
      <div style="font-size:0.8em;color:rgba(255,255,255,0.3)">@<?= e($u['username']) ?> · <span class="role"><?= e($u['role']) ?></span></div></div>
  </div>
  <div style="display:flex;align-items:center;gap:20px">
    <?php if ($u['role'] === 'utilisateur'): ?>
      <div style="text-align:right"><div style="font-weight:700;color:white"><?= $eur($u['solde']) ?> €</div><div style="font-size:0.75em;color:rgba(255,255,255,0.3)">Solde</div></div>
    <?php elseif ($u['role'] === 'commercant'): ?>
      <div style="text-align:right"><div style="font-weight:700;color:#6ee7b7"><?= $eur($ca_map[$u['id']] ?? 0) ?> €</div><div style="font-size:0.75em;color:rgba(255,255,255,0.3)">Chiffre d'affaires</div></div>
    <?php endif; ?>
    <div style="text-align:center;font-size:0.8em;color:<?= $u['badge_uid'] ? '#a5b4fc' : 'rgba(255,255,255,0.2)' ?>"><?= $u['badge_uid'] ? e($u['badge_uid']) : 'Pas de badge' ?></div>
    <div><span class="<?= $u['actif'] ? 'badge-active' : 'badge-inactive' ?>"><?= $u['actif'] ? 'Actif' : 'Inactif' ?></span></div>
    <div style="display:flex;gap:4px;align-items:center">
      <?php if ($u['role'] !== 'admin'): ?>
      <form method="POST" style="margin:0"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
        <button name="toggle" value="1" class="btn btn-small <?= $u['actif'] ? 'btn-danger' : 'btn-success' ?>"><?= $u['actif'] ? 'Off' : 'On' ?></button></form>
      <?php endif; ?>
      <form method="POST" style="margin:0"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
        <button name="scan_badge" value="1" class="btn btn-small btn-primary" title="Scanner un badge"><i data-lucide="nfc" style="width:14px;height:14px"></i></button></form>
      <form method="POST" style="margin:0;display:flex;gap:3px"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
        <input type="text" name="badge_uid" placeholder="UID" style="width:85px;padding:5px 8px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:6px;color:white;font-size:0.75em">
        <button name="manual_badge" value="1" class="btn btn-small btn-primary">OK</button></form>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>
<script>lucide.createIcons();</script>
