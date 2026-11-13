<?php
declare(strict_types=1);

$pageTitle = 'La Vingtaine | Accueil B2B Champagne, Vins & Spiritueux';
$pageDescription = 'Accueil La Vingtaine - Expérience 3D des bouteilles et sélection d’exception.';
$currentPage = 'home';
$loadThreeScene = true;

require_once __DIR__ . '/includes/header.php';

$pdo = getPDO();
$usingDb = $pdo instanceof PDO;

if ($usingDb) {
    $allProductsStmt = $pdo->query(
        "SELECT id, name, region, category, type, volume, abv, grape, price, is_exception, description
         FROM products
         ORDER BY category ASC, name ASC"
    );
    $allProducts = $allProductsStmt->fetchAll();
} else {
    $allProducts = getSeedProducts();
}

$heroBottles = [];
foreach ($allProducts as $row) {
    $heroBottles[] = [
        'name' => (string)$row['name'],
        'type' => (string)$row['type'],
        'region' => (string)$row['region'],
        'description' => (string)$row['description']
    ];
}

$exceptionProduct = null;
foreach ($allProducts as $row) {
    if ((int)$row['is_exception'] === 1) {
        $exceptionProduct = $row;
        break;
    }
}
if (!$exceptionProduct && count($allProducts) > 0) {
    $exceptionProduct = $allProducts[0];
}
?>

<div id="loader" class="loader">
    <div class="loader__logo">LA VINGTAINE</div>
</div>

<main>
    <section class="hero-scroll" id="hero-scroll">
        <div class="hero-sticky">
            <div class="hero-sticky__media">
                <div class="hero__canvas-wrap">
                    <canvas id="bottle-scene" aria-label="Bouteille 3D pilotée par le défilement"></canvas>
                </div>
            </div>
            <div class="hero-sticky__content">
                <h1>Sélection B2B de Champagnes, Vins & Spiritueux</h1>
                <p class="hero-intro">Défilez pour découvrir chaque bouteille, sa région et son profil de dégustation.</p>

                <div id="hero-bottle-description">
                    <h3 id="hero-bottle-title">-</h3>
                    <p id="hero-bottle-meta">-</p>
                    <p id="hero-bottle-text">-</p>
                </div>
            </div>
        </div>
    </section>

    <section id="exception" class="section highlight">
        <h2>Sélection exceptionnelle mise en avant</h2>
        <?php if ($exceptionProduct): ?>
            <p>
                <strong><?= htmlspecialchars((string)$exceptionProduct['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                — <?= htmlspecialchars((string)$exceptionProduct['description'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php else: ?>
            <p>Découvrez notre sélection premium pour les établissements exigeants.</p>
        <?php endif; ?>
        <p>
            <a class="secondary-button-link" href="catalogue.php">Découvrir le catalogue B2B</a>
        </p>
    </section>
</main>

<script id="hero-bottles-data" type="application/json"><?= json_encode($heroBottles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
