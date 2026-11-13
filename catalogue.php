<?php
declare(strict_types=1);

$pageTitle = 'La Vingtaine | Catalogue & Régions';
$pageDescription = 'Catalogue B2B La Vingtaine par régions viticoles.';
$currentPage = 'catalogue';
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
foreach ($allProducts as $row) {
    $productsById[(string)$row['id']] = $row;
}

$catalog = [];
foreach ($allProducts as $row) {
    $category = (string)$row['category'];
    if (!isset($catalog[$category])) {
        $catalog[$category] = [];
    }
    $catalog[$category][] = [
        'id' => (string)$row['id'],
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

$regions = array_keys($catalog);
$selectedRegion = (string)($_GET['region'] ?? ($regions[0] ?? ''));
if ($selectedRegion === '' || !isset($catalog[$selectedRegion])) {
    $selectedRegion = $regions[0] ?? '';
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
        header('Location: catalogue.php?region=' . urlencode($redirectRegion) . '&added=1');
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
    <section id="catalog" class="section">
        <h2>Catalogue par régions viticoles</h2>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                <?php if ($selectionLink !== ''): ?>
                    <br><a href="<?= htmlspecialchars($selectionLink, ENT_QUOTES, 'UTF-8') ?>">Voir ma sélection</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="regions">
            <?php foreach ($regions as $region): ?>
                <a class="region-pill <?= $region === $selectedRegion ? 'is-active' : '' ?>"
                   href="catalogue.php?region=<?= urlencode($region) ?>#catalog">
                    <?= htmlspecialchars($region, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </div>

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

        <p style="margin-top: 24px;">
            Vous ne trouvez pas une référence ?
            <a href="contact.php">Contactez directement le fournisseur</a>.
        </p>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
