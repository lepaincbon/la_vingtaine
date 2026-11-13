<?php
declare(strict_types=1);

$pageTitle = 'La Vingtaine | Carte des Régions';
$pageDescription = 'Explorez le catalogue B2B via une carte interactive des régions viticoles.';
$currentPage = 'carte';
$loadThreeScene = false;

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

$productsById = [];
$catalog = [];

foreach ($allProducts as $row) {
    $id = (string)$row['id'];
    $category = (string)$row['category'];

    $productsById[$id] = $row;

    if (!isset($catalog[$category])) {
        $catalog[$category] = [];
    }

    $catalog[$category][] = [
        'id' => $id,
        'name' => (string)$row['name'],
        'region' => (string)$row['region'],
        'type' => (string)$row['type'],
        'volume' => (string)$row['volume'],
        'abv' => (string)$row['abv'],
        'grape' => (string)$row['grape'],
        'price' => (float)$row['price'],
        'exception' => ((int)$row['is_exception']) === 1,
        'description' => (string)$row['description']
    ];
}

$availableRegions = array_keys($catalog);
sort($availableRegions);

$selectedRegion = (string)($_GET['region'] ?? ($availableRegions[0] ?? ''));
if ($selectedRegion === '' || !isset($catalog[$selectedRegion])) {
    $selectedRegion = $availableRegions[0] ?? '';
}

$message = '';
$messageType = 'info';
$selectionLink = '';

$action = $_POST['action'] ?? null;
if ($action === 'add_to_cart') {
    $id = (string)($_POST['id'] ?? '');
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $regionFromPost = (string)($_POST['region'] ?? $selectedRegion);

    $product = $productsById[$id] ?? null;

    if ($product) {
        if (!isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = [
                'id' => (string)$product['id'],
                'name' => (string)$product['name'],
                'price' => (float)$product['price'],
                'qty' => 0
            ];
        }
        $_SESSION['cart'][$id]['price'] = (float)$product['price'];
        $_SESSION['cart'][$id]['name'] = (string)$product['name'];
        $_SESSION['cart'][$id]['qty'] += $qty;

        $redirectRegion = $regionFromPost !== '' ? $regionFromPost : $selectedRegion;
        header('Location: carte.php?region=' . urlencode($redirectRegion) . '&added=1');
        exit;
    }

    $message = 'Product not found or unavailable.';
    $messageType = 'error';
}

if (isset($_GET['added']) && $_GET['added'] === '1') {
    $message = 'Wine added to your selection.';
    $messageType = 'success';
    $selectionLink = 'selection.php';
}

$products = $selectedRegion !== '' ? $catalog[$selectedRegion] : [];
$isProfessional = true;

$regionMapConfig = [
    'Champagne' => ['x' => 63, 'y' => 24],
    'Bourgogne' => ['x' => 60, 'y' => 45],
    'Bordeaux' => ['x' => 30, 'y' => 62],
    'Spiritueux' => ['x' => 42, 'y' => 50]
];
?>

<main>
    <section class="section" id="carte-regions">
        <h2>Carte des Régions</h2>
        <p>Click a region on the map to filter the professional catalogue.</p>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                <?php if ($selectionLink !== ''): ?>
                    <br><a href="<?= htmlspecialchars($selectionLink, ENT_QUOTES, 'UTF-8') ?>">View my selection</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="map-layout" style="display:grid;grid-template-columns:1fr;gap:24px;">
            <div class="map-visual" style="background:#f7f1e8;border:1px solid #d7c7b5;border-radius:16px;padding:20px;">
                <svg viewBox="0 0 100 100" width="100%" height="380" role="img" aria-label="Carte stylisée de la France viticole">
                    <path d="M30,8 L55,10 L75,24 L82,42 L78,62 L66,79 L47,90 L29,85 L16,70 L12,49 L18,28 Z"
                          fill="#efe6d8" stroke="#6b3f28" stroke-width="1.2"></path>

                    <?php foreach ($availableRegions as $region): ?>
                        <?php
                        $point = $regionMapConfig[$region] ?? ['x' => 50, 'y' => 50];
                        $isActive = $region === $selectedRegion;
                        ?>
                        <a xlink:href="carte.php?region=<?= urlencode($region) ?>#carte-regions">
                            <circle cx="<?= (float)$point['x'] ?>" cy="<?= (float)$point['y'] ?>" r="<?= $isActive ? 4.5 : 3.2 ?>"
                                    fill="<?= $isActive ? '#6b3f28' : '#b58a6a' ?>" stroke="#ffffff" stroke-width="1.2"></circle>
                            <text x="<?= (float)$point['x'] + 2.8 ?>" y="<?= (float)$point['y'] - 2.8 ?>"
                                  font-size="3.2" fill="#4a2b1b"><?= htmlspecialchars($region, ENT_QUOTES, 'UTF-8') ?></text>
                        </a>
                    <?php endforeach; ?>
                </svg>

                <div class="regions" style="margin-top:12px;">
                    <?php foreach ($availableRegions as $region): ?>
                        <a class="region-pill <?= $region === $selectedRegion ? 'is-active' : '' ?>"
                           href="carte.php?region=<?= urlencode($region) ?>#carte-regions">
                            <?= htmlspecialchars($region, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <h3>
                    Région sélectionnée :
                    <?= $selectedRegion !== '' ? htmlspecialchars($selectedRegion, ENT_QUOTES, 'UTF-8') : '—' ?>
                </h3>

                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <article class="product-card <?= $product['exception'] ? 'is-exception' : '' ?>">
                            <h3><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="product-meta">
                                <?= htmlspecialchars($product['type'], ENT_QUOTES, 'UTF-8') ?> •
                                <?= htmlspecialchars($product['volume'], ENT_QUOTES, 'UTF-8') ?> •
                                <?= htmlspecialchars($product['abv'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p><strong>Région :</strong> <?= htmlspecialchars($product['region'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>Cépage :</strong> <?= htmlspecialchars($product['grape'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p><?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?></p>

                            <?php if ($isProfessional): ?>
                                <p class="product-price">Tarif pro HT : €<?= number_format((float)$product['price'], 2) ?></p>
                            <?php else: ?>
                                <p class="product-price locked">Prix réservé aux professionnels</p>
                            <?php endif; ?>

                            <?php if ($isProfessional): ?>
                                <form method="post" class="add-form">
                                    <input type="hidden" name="action" value="add_to_cart">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="region" value="<?= htmlspecialchars($selectedRegion, ENT_QUOTES, 'UTF-8') ?>">
                                    <label>
                                        Qté
                                        <input type="number" name="qty" min="1" value="1">
                                    </label>
                                    <button type="submit">Ajouter à la sélection</button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if (count($products) === 0): ?>
                    <p>Aucun produit disponible pour cette région.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
