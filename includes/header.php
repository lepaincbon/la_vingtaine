<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
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

/**
 * Ajoute ?v=<date de modification> à une ressource statique.
 * Le navigateur (surtout mobile) télécharge donc automatiquement la nouvelle
 * version de style.css / nav.js dès qu'elle change, sans vider le cache.
 */
if (!function_exists('assetUrl')) {
    function assetUrl(string $path): string
    {
        static $versions = [];
        $path = ltrim($path, '/');
        if (!isset($versions[$path])) {
            $file = dirname(__DIR__) . '/' . $path;
            $versions[$path] = is_file($file) ? (string)filemtime($file) : '1';
        }
        return $path . '?v=' . $versions[$path];
    }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#f9f6f2">
    <meta name="format-detection" content="telephone=no">
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(assetUrl('assets/img/favicon.svg'), ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="website">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap">
    <link rel="preload" as="style" href="<?= htmlspecialchars(assetUrl('assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="<?= htmlspecialchars(assetUrl('assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <script src="<?= htmlspecialchars(assetUrl('assets/js/nav.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    </noscript>
</head>
<body>
<header class="header">
    <a class="logo" href="index.php" aria-label="La Vingtaine — Accueil">La Vingtaine</a>
    <button class="nav-toggle" id="nav-toggle" type="button" aria-label="Ouvrir le menu" aria-controls="primary-nav" aria-expanded="false">
        <span class="nav-toggle-bar"></span>
        <span class="nav-toggle-bar"></span>
        <span class="nav-toggle-bar"></span>
    </button>
    <nav class="nav" id="primary-nav" aria-label="Navigation principale">
        <a href="index.php" class="<?= $currentPage === 'home' ? 'is-active' : '' ?>">Accueil</a>
        <a href="carte.php" class="<?= $currentPage === 'carte' ? 'is-active' : '' ?>">Carte des Régions</a>
        <a href="panier.php" class="<?= $currentPage === 'panier' ? 'is-active' : '' ?>">Liste des vins sélectionnés (<?= $cartBottleCount ?>)</a>
        <a href="apropos.php" class="<?= $currentPage === 'apropos' ? 'is-active' : '' ?>">À propos</a>
        <a href="contact.php" class="<?= $currentPage === 'contact' ? 'is-active' : '' ?>">Contact & Fournisseur</a>
        <?php if ($currentPage === 'login'): ?>
            <span class="nav-auth">
                <a href="login.php" class="is-active">Connexion</a>
                <span class="nav-sep">·</span>
                <a href="register.php">Inscription</a>
            </span>
        <?php elseif (currentProfessional() !== null): ?>
            <span class="nav-auth">
                <span class="nav-company" title="Connecté en tant que <?= htmlspecialchars(currentProfessional()['company_name'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars(currentProfessional()['company_name'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="nav-auth-divider">·</span>
                <a href="compte.php" class="nav-account-link">Mon compte</a>
                <span class="nav-auth-divider">·</span>
                <form method="post" action="logout.php" class="logout-form">
                    <?= csrfField() ?>
                    <button type="submit" class="nav-logout">Déconnexion</button>
                </form>
            </span>
        <?php else: ?>
            <span class="nav-auth">
                <a href="login.php">Connexion</a>
                <span class="nav-sep">·</span>
                <a href="register.php">Inscription</a>
            </span>
        <?php endif; ?>
    </nav>
</header>
