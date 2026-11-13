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
?>

<main>
    <section class="section" id="carte-regions">
        <h2>Carte des Régions</h2>
        <p>Cliquez sur une zone géographique pour filtrer instantanément le catalogue professionnel.</p>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                <?php if ($selectionLink !== ''): ?>
                    <br><a href="<?= htmlspecialchars($selectionLink, ENT_QUOTES, 'UTF-8') ?>">Voir ma sélection</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form id="region-filter-form" method="get" action="carte.php" style="display:none;">
            <input id="selected-region-input" type="hidden" name="region" value="<?= htmlspecialchars($selectedRegion, ENT_QUOTES, 'UTF-8') ?>">
            <button id="region-submit-btn" type="submit">Filtrer</button>
        </form>

        <div class="map-layout" style="display:grid;grid-template-columns:1fr;gap:24px;">
            <div class="map-visual" style="background:#f7f1e8;border:1px solid #d7c7b5;border-radius:16px;padding:20px;">
                <object
                    id="france-map-object"
                    type="image/svg+xml"
                    data="assets/departements.svg"
                    width="100%"
                    height="480"
                    aria-label="Carte interactive de la France métropolitaine"
                    style="display:block;background:#fffaf2;border-radius:12px;"
                ></object>

                <div class="regions" style="margin-top:12px;">
                    <?php foreach ($availableRegions as $region): ?>
                        <a
                            class="region-pill <?= $region === $selectedRegion ? 'is-active' : '' ?>"
                            href="carte.php?region=<?= urlencode($region) ?>#carte-regions"
                            data-region-link="<?= htmlspecialchars($region, ENT_QUOTES, 'UTF-8') ?>"
                        >
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

                <div class="products-grid" id="products-by-region">
                    <?php foreach ($products as $product): ?>
                        <article
                            class="product-card <?= $product['exception'] ? 'is-exception' : '' ?>"
                            data-product-region="<?= htmlspecialchars($selectedRegion, ENT_QUOTES, 'UTF-8') ?>"
                        >
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

                <p id="no-products-message" style="display:<?= count($products) === 0 ? 'block' : 'none' ?>;">
                    Aucun produit disponible pour cette région.
                </p>
            </div>
        </div>
    </section>
</main>

<script src="assets/js/carte.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
