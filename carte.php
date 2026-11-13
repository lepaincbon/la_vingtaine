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

    $message = 'Produit introuvable ou indisponible.';
    $messageType = 'error';
}

if (isset($_GET['added']) && $_GET['added'] === '1') {
    $message = 'Vin ajouté à votre sélection.';
    $messageType = 'success';
    $selectionLink = 'selection.php';
}

$products = $selectedRegion !== '' ? $catalog[$selectedRegion] : [];
$isProfessional = true;

$regionMapConfig = [
    'Champagne' => ['x' => 370, 'y' => 165],
    'Bourgogne' => ['x' => 345, 'y' => 250],
    'Bordeaux' => ['x' => 205, 'y' => 345],
    'Spiritueux' => ['x' => 250, 'y' => 300]
];
?>

<main>
    <section class="section" id="carte-regions">
        <h2>Carte des Régions</h2>
        <p>Cliquez sur une région de la carte pour filtrer le catalogue professionnel.</p>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                <?php if ($selectionLink !== ''): ?>
                    <br><a href="<?= htmlspecialchars($selectionLink, ENT_QUOTES, 'UTF-8') ?>">Voir ma sélection</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="map-layout" style="display:grid;grid-template-columns:1fr;gap:24px;">
            <div class="map-visual" style="background:#f7f1e8;border:1px solid #d7c7b5;border-radius:16px;padding:20px;">
                <svg viewBox="0 0 540 620" width="100%" height="420" role="img" aria-label="Carte vectorielle de la France métropolitaine avec repères viticoles">
                    <defs>
                        <filter id="softShadow" x="-20%" y="-20%" width="140%" height="140%">
                            <feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="#6b3f28" flood-opacity="0.18"/>
                        </filter>
                    </defs>

                    <path
                        d="M150,78
                           L232,58
                           L312,74
                           L376,125
                           L422,190
                           L430,266
                           L404,337
                           L415,401
                           L377,472
                           L318,529
                           L247,551
                           L181,536
                           L138,494
                           L98,448
                           L80,385
                           L84,314
                           L70,248
                           L92,190
                           L121,141 Z"
                        fill="#efe6d8"
                        stroke="#6b3f28"
                        stroke-width="4"
                        filter="url(#softShadow)"
                    />

                    <path
                        d="M404,337 L421,348 L430,370 L420,392 L402,396 L392,377 L397,355 Z"
                        fill="#efe6d8"
                        stroke="#6b3f28"
                        stroke-width="3"
                    />

                    <?php foreach ($availableRegions as $region): ?>
                        <?php
                        $point = $regionMapConfig[$region] ?? ['x' => 270, 'y' => 310];
                        $isActive = $region === $selectedRegion;
                        $fillColor = $isActive ? '#6b3f28' : '#b58a6a';
                        $radius = $isActive ? 12 : 9;
                        ?>
                        <a xlink:href="carte.php?region=<?= urlencode($region) ?>#carte-regions" href="carte.php?region=<?= urlencode($region) ?>#carte-regions">
                            <circle
                                cx="<?= (float)$point['x'] ?>"
                                cy="<?= (float)$point['y'] ?>"
                                r="<?= $radius ?>"
                                fill="<?= $fillColor ?>"
                                stroke="#fffaf2"
                                stroke-width="3"
                            ></circle>
                            <text
                                x="<?= (float)$point['x'] + 16 ?>"
                                y="<?= (float)$point['y'] + 5 ?>"
                                font-size="17"
                                font-weight="<?= $isActive ? '700' : '500' ?>"
                                fill="#4a2b1b"
                            ><?= htmlspecialchars($region, ENT_QUOTES, 'UTF-8') ?></text>
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
