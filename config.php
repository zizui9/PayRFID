<?php
// ─────────────────────────────────────────────────────────────
// PayRFID — configuration commune et fonctions utilitaires
// ─────────────────────────────────────────────────────────────

// Mise en tampon de la sortie : permet de rediriger (Post/Redirect/Get)
// même si la page a déjà commencé à afficher du HTML.
ob_start();

// Cookie de session : inaccessible au JavaScript, envoyé seulement en navigation
// de premier niveau (SameSite=Lax), et uniquement en HTTPS quand il est actif.
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

// Identifiants de la base : générés à l'installation dans config.local.php
// (non versionné), ou fournis par variables d'environnement (Docker).
$cfg = [
    'db_host' => getenv('PAYRFID_DB_HOST') ?: 'localhost',
    'db_name' => getenv('PAYRFID_DB_NAME') ?: 'rfid_paiement',
    'db_user' => getenv('PAYRFID_DB_USER') ?: 'rfid_user',
    'db_pass' => getenv('PAYRFID_DB_PASS') ?: '',
];
if (is_file(__DIR__ . '/config.local.php')) {
    $cfg = array_merge($cfg, require __DIR__ . '/config.local.php');
}

$db = new PDO(
    "mysql:host={$cfg['db_host']};dbname={$cfg['db_name']};charset=utf8mb4",
    $cfg['db_user'],
    $cfg['db_pass'],
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // vraies requêtes préparées côté serveur
    ]
);

const MONTANT_MAX_PAIEMENT = 500.00;
const MONTANT_MAX_CREDIT   = 1000.00;
const DELAI_2FA_SECONDES   = 120;

// ── Échappement HTML (protection XSS) ────────────────────────
function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

// ── Mots de passe (bcrypt via password_hash) ─────────────────
function hashPassword(string $p): string { return password_hash($p, PASSWORD_DEFAULT); }
function verifyPassword(string $p, string $h): bool { return password_verify($p, $h); }

// ── Session et contrôle d'accès par rôles ────────────────────
function isLoggedIn(): bool { return isset($_SESSION['user_id']); }

function requireLogin(): void
{
    if (!isLoggedIn()) { redirect('login.php'); }
}

function requireRole($roles): void
{
    requireLogin();
    if (!in_array($_SESSION['role'], (array) $roles, true)) {
        http_response_code(403);
        exit('Accès interdit');
    }
}

// Ouverture de session après authentification complète.
// session_regenerate_id empêche la fixation de session.
function loginUser(array $u): void
{
    session_regenerate_id(true);
    unset($_SESSION['pending_2fa']);
    $_SESSION['user_id'] = (int) $u['id'];
    $_SESSION['role']    = $u['role'];
}

function redirect(string $url): void
{
    if (ob_get_level() > 0) { ob_end_clean(); }
    header('Location: ' . $url);
    exit;
}

// ── Messages flash (affichés une fois après une redirection) ──
function flash(string $type, string $msg): void { $_SESSION['flash'] = [$type, $msg]; }

function renderFlash(): string
{
    if (empty($_SESSION['flash'])) { return ''; }
    [$type, $msg] = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $class = $type === 'ok' ? 'alert-success' : 'alert-error';
    return '<div class="' . $class . '">' . $msg . '</div>'; // $msg est déjà échappé à la création
}

// ── Protection CSRF : jeton aléatoire par session ────────────
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}

function verifyCsrf(): void
{
    if (!hash_equals(csrfToken(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Requête invalide (jeton CSRF).');
    }
}

// ── Lecture d'un badge RFID (RC522 sur bus SPI) ──────────────
// Le script renvoie l'UID en hexadécimal, ou rien après son délai d'attente.
// PAYRFID_FAKE_BADGE_FILE permet de simuler un badge en développement (Docker).
function scanBadge(): ?string
{
    $fake = getenv('PAYRFID_FAKE_BADGE_FILE');
    if ($fake) {
        $uid = is_file($fake) ? trim((string) file_get_contents($fake)) : '';
    } else {
        $uid = trim((string) shell_exec('/opt/payrfid/scripts/tag_detect.sh 2>/dev/null'));
    }
    return preg_match('/^[0-9A-F]{8,14}$/', strtoupper($uid)) ? strtoupper($uid) : null;
}
