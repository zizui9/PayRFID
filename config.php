<?php
session_start();
$db = new PDO('mysql:host=localhost;dbname=rfid_paiement;charset=utf8', 'rfid_user', 'rfid2026');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
function hashPassword($p) { return password_hash($p, PASSWORD_DEFAULT); }
function verifyPassword($p, $h) { return password_verify($p, $h); }
function isLoggedIn() { return isset($_SESSION['user_id']); }
function requireLogin() { if (!isLoggedIn()) { header('Location: login.php'); exit; } }
function requireRole($r) { requireLogin(); if (!in_array($_SESSION['role'], (array)$r)) { die('Acces interdit'); } }
function scanBadge() { $u = trim(shell_exec('sudo /home/pi/script/tag_detect.sh')); return $u ?: null; }
?>
