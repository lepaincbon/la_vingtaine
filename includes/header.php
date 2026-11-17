<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!isset($_SESSION['orders_fallback']) || !is_array($_SESSION['orders_fallback'])) {
    $_SESSION['orders_fallback'] = [];
}
if (!isset($_SESSION['contact_requests_fallback']) || !is_array($_SESSION['contact_requests_fallback'])) {
    $_SESSION['contact_requests_fallback'] = [];
}

$cartBottleCount = 0;
foreach ($_SESSION['cart'] as $cartLine) {
    $cartBottleCount += (int)($cartLine['qty'] ?? 0);
}

$pageTitle = $pageTitle ?? 'La Vingtaine';
$pageDescription = $pageDescription ?? 'La Vingtaine - Agence B2B de champagne, vins et spiritueux.';
$currentPage = $currentPage ?? '';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="header">
    <a class="logo" href="index.php" aria-label="La Vingtaine — Accueil">La Vingtaine</a>
    <nav class="nav">
        <a href="index.php" class="<?= $currentPage === 'home' ? 'is-active' : '' ?>">Accueil</a>
        <a href="catalogue.php" class="<?= $currentPage === 'catalogue' ? 'is-active' : '' ?>">Catalogue & Régions</a>
        <a href="carte.php" class="<?= $currentPage === 'carte' ? 'is-active' : '' ?>">Carte des Régions</a>
        <a href="selection.php" class="<?= $currentPage === 'selection' ? 'is-active' : '' ?>">Liste des vins sélectionnés (<?= $cartBottleCount ?>)</a>
        <a href="apropos.php" class="<?= $currentPage === 'apropos' ? 'is-active' : '' ?>">À propos</a>
<a href="contact.php" class="<?= $currentPage === 'contact' ? 'is-active' : '' ?>">Contact & Fournisseur</a>
    </nav>
</header>
