<?php
declare(strict_types=1);

$pageTitle = 'La Vingtaine | Fiche produit';
$pageDescription = 'Détails du produit B2B La Vingtaine.';
$currentPage = 'produit';
$loadThreeScene = false;

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

$pdo = getPDO();
$usingDb = $pdo instanceof PDO;

$productId = (string)($_GET['id'] ?? '');

if ($usingDb) {
    $stmt = $pdo->prepare(
        "SELECT id, name, region, category, type, volume, abv, grape, price, is_exception, description, image
         FROM products
         WHERE id = :id"
    );
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();
} else {
    $product = null;
    foreach (getSeedProducts() as $row) {
        if ((string)$row['id'] === $productId) {
            $product = $row;
            break;
        }
    }
}
if ($product && !isset($product['image'])) {
    $product['image'] = '';
}

if (!$product) {
    $product = null;
}

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product) {
    $action = $_POST['action'] ?? null;
    if ($action === 'add_to_cart') {
        if (!validateCsrf()) {
            $message = 'Jeton de sécurité invalide. Veuillez réessayer.';
            $messageType = 'error';
        } else {
            $qty = max(1, (int)($_POST['qty'] ?? 1));
            if (!isset($_SESSION['cart'][$product['id']])) {
                $_SESSION['cart'][$product['id']] = [
                    'id' => (string)$product['id'],
                    'name' => (string)$product['name'],
                    'price' => (float)$product['price'],
                    'qty' => 0
                ];
            }
            $_SESSION['cart'][$product['id']]['price'] = (float)$product['price'];
            $_SESSION['cart'][$product['id']]['name'] = (string)$product['name'];
            $_SESSION['cart'][$product['id']]['qty'] += $qty;

            $message = $product['name'] . ' ajouté à votre sélection.';
            $messageType = 'success';
        }
    }
}

$isProfessional = currentProfessional() !== null;
?>

<main>
    <section class="section">
        <p>
            <a href="catalogue.php" class="secondary-button-link">← Retour au catalogue</a>
        </p>

        <?php if (!$product): ?>
            <div class="message error" role="alert">Produit introuvable ou indisponible.</div>
            <p>Vous ne trouvez pas cette référence ? <a href="contact.php">Contactez directement le fournisseur</a>.</p>
        <?php else: ?>
            <article class="product-detail-card <?= ((int)$product['is_exception']) === 1 ? 'is-exception' : '' ?>">
                <img class="product-detail-image" src="<?= htmlspecialchars(getProductImage($product), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)$product['name'], ENT_QUOTES, 'UTF-8') ?>">
                <h1><?= htmlspecialchars((string)$product['name'], ENT_QUOTES, 'UTF-8') ?></h1>

                <p class="product-meta">
                    <?= htmlspecialchars((string)$product['type'], ENT_QUOTES, 'UTF-8') ?> •
                    <?= htmlspecialchars((string)$product['volume'], ENT_QUOTES, 'UTF-8') ?> •
                    <?= htmlspecialchars((string)$product['abv'], ENT_QUOTES, 'UTF-8') ?>
                </p>

                <div class="product-detail-grid">
                    <div class="product-detail-block">
                        <h3>Profil de dégustation</h3>
                        <p><?= nl2br(htmlspecialchars((string)$product['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                    </div>

                    <div class="product-detail-block">
                        <h3>Informations techniques</h3>
                        <ul class="product-detail-list">
                            <li><strong>Région：</strong> <?= htmlspecialchars((string)$product['region'], ENT_QUOTES, 'UTF-8') ?></li>
                            <li><strong>Cépage：</strong> <?= htmlspecialchars((string)$product['grape'], ENT_QUOTES, 'UTF-8') ?></li>
                            <li><strong>Catégorie：</strong> <?= htmlspecialchars((string)$product['category'], ENT_QUOTES, 'UTF-8') ?></li>
                            <li><strong>Format：</strong> <?= htmlspecialchars((string)$product['volume'], ENT_QUOTES, 'UTF-8') ?></li>
                            <li><strong>Degré d'alcool：</strong> <?= htmlspecialchars((string)$product['abv'], ENT_QUOTES, 'UTF-8') ?></li>
                            <?php if (((int)$product['is_exception']) === 1): ?>
                                <li><strong>Sélection：</strong> Exceptionnelle</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <p class="product-price">
                    Tarif pro HT : €<?= number_format((float)$product['price'], 2, ',', ' ') ?>
                </p>

                <?php if ($message !== ''): ?>
                    <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>" role="alert">
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="add-form">
                    <input type="hidden" name="action" value="add_to_cart">
                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$product['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= csrfField() ?>
                    <label>
                        Qté
                        <input type="number" name="qty" min="1" value="1">
                    </label>
                    <button type="submit">Ajouter à la sélection</button>
                </form>

                <p style="margin-top: 1rem;">
                    Vous ne trouvez pas une référence ?
                    <a href="contact.php">Contactez directement le fournisseur</a>.
                </p>
            </article>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>