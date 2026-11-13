<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

$pdo = getPDO();
$usingDb = $pdo instanceof PDO;

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!isset($_SESSION['orders_fallback'])) {
    $_SESSION['orders_fallback'] = [];
}

$message = '';
$messageType = 'info';

if (!$usingDb) {
    $message = "Mode dégradé actif : l'extension pdo_sqlite est absente. Le site utilise des données de démonstration temporaires.";
    $messageType = 'error';
}

$allProducts = [];

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

$action = $_POST['action'] ?? null;

if ($action === 'add_to_cart') {
    $id = (string)($_POST['id'] ?? '');
    $qty = max(1, (int)($_POST['qty'] ?? 1));

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

        $message = 'Vin ajouté à votre sélection.';
        $messageType = 'success';
    } else {
        $message = 'Produit introuvable ou indisponible.';
        $messageType = 'error';
    }
}

if ($action === 'remove_from_cart') {
    $id = (string)($_POST['id'] ?? '');
    if ($id !== '' && isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
        $message = 'Vin retiré de votre sélection.';
        $messageType = 'success';
    }
}

if ($action === 'update_qty') {
    $id = (string)($_POST['id'] ?? '');
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if ($id !== '' && isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['qty'] = $qty;
        $message = 'Votre sélection a été mise à jour.';
        $messageType = 'success';
    }
}

$cartItems = array_values($_SESSION['cart']);
$cartTotal = 0.0;
foreach ($cartItems as $item) {
    $cartTotal += (float)$item['price'] * (int)$item['qty'];
}
$minimumOrderAmount = 300.0;
$remainingAmount = max(0.0, $minimumOrderAmount - $cartTotal);

if ($action === 'validate_order') {
    if ($cartTotal < $minimumOrderAmount) {
        $message = 'Validation impossible : minimum de commande B2B de 300,00 € non atteint.';
        $messageType = 'error';
    } elseif (count($cartItems) === 0) {
        $message = 'Votre sélection est vide.';
        $messageType = 'error';
    } else {
        if ($usingDb) {
            $orderInsert = $pdo->prepare(
                "INSERT INTO orders (customer_email, company_name, items_json, total_amount)
                 VALUES (:customer_email, :company_name, :items_json, :total_amount)"
            );

            $orderInsert->execute([
                ':customer_email' => null,
                ':company_name' => null,
                ':items_json' => json_encode($cartItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ':total_amount' => $cartTotal
            ]);
        } else {
            $_SESSION['orders_fallback'][] = [
                'customer_email' => null,
                'company_name' => null,
                'items_json' => json_encode($cartItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'total_amount' => $cartTotal,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        $message = 'Demande de commande reçue. Notre équipe vous contactera rapidement. Livraison sous 3 à 5 jours ouvrés.';
        $messageType = 'success';
        $_SESSION['cart'] = [];
        $cartItems = [];
        $cartTotal = 0.0;
        $remainingAmount = $minimumOrderAmount;
    }
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
$products = $selectedRegion !== '' ? $catalog[$selectedRegion] : [];

$isProfessional = true;

$heroBottles = [];
foreach ($allProducts as $row) {
    $heroBottles[] = [
        'name' => (string)$row['name'],
        'type' => (string)$row['type'],
        'region' => (string)$row['region'],
        'description' => (string)$row['description']
    ];
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Vingtaine | Agence B2B Champagne, Vins & Spiritueux</title>
    <meta name="description" content="La Vingtaine - Agence B2B de champagne, vins et spiritueux, pour restaurants, hôtels et palaces.">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div id="loader" class="loader">
    <div class="loader__logo">LA VINGTAINE</div>
</div>

<header class="header">
    <div class="logo">La Vingtaine</div>
    <nav class="nav">
        <a href="#catalog">Catalogue</a>
        <a href="#selection-b2b" class="nav-selection-link">Liste des vins sélectionnés (<?= count($cartItems) ?>)</a>
        <a href="#exception">Vin d’Exception</a>
        <a href="#delivery">Livraison</a>
        <a href="#contact">Contact</a>
        <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer">Instagram</a>
    </nav>
</header>

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

    <section id="catalog" class="section">
        <h2>Catalogue par régions viticoles</h2>
        <div class="regions">
            <?php foreach ($regions as $region): ?>
                <a class="region-pill <?= $region === $selectedRegion ? 'is-active' : '' ?>"
                   href="?region=<?= urlencode($region) ?>#catalog">
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
    </section>

    <section id="selection-b2b" class="section selection-section">
        <h2>Liste des vins sélectionnés</h2>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (count($cartItems) > 0): ?>
            <div class="cart-list">
                <?php foreach ($cartItems as $item): ?>
                    <?php $lineSubtotal = (float)$item['price'] * (int)$item['qty']; ?>
                    <div class="cart-item">
                        <div class="selection-item-main">
                            <h3 class="selection-item-name"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="selection-item-pricing">Prix unitaire HT/Pro : €<?= number_format((float)$item['price'], 2) ?></div>
                            <div class="selection-subtotal">Sous-total : €<?= number_format($lineSubtotal, 2) ?></div>
                        </div>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="update_qty">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <label>
                                Quantité
                                <input type="number" name="qty" min="1" value="<?= (int)$item['qty'] ?>">
                            </label>
                            <button type="submit">Mettre à jour</button>
                        </form>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="remove_from_cart">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="danger">Supprimer</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Votre sélection est vide.</p>
        <?php endif; ?>

        <div class="selection-summary">
            <p><strong>Montant total HT :</strong> €<?= number_format($cartTotal, 2) ?></p>
            <p class="cart-rule"><strong>Minimum de commande :</strong> 300,00 € pour valider la sélection.</p>
            <?php if ($remainingAmount > 0): ?>
                <p class="cart-rule"><strong>Montant restant pour atteindre le minimum :</strong> €<?= number_format($remainingAmount, 2) ?></p>
            <?php else: ?>
                <p class="cart-rule"><strong>Seuil minimum atteint :</strong> vous pouvez valider la sélection.</p>
            <?php endif; ?>
        </div>

        <div class="selection-actions">
            <form method="post">
                <input type="hidden" name="action" value="validate_order">
                <button type="submit" class="primary" <?= ($cartTotal < $minimumOrderAmount || count($cartItems) === 0) ? 'disabled' : '' ?>>
                    Valider la sélection
                </button>
            </form>
            <a class="secondary-button-link" href="#contact">Contacter le fournisseur pour une demande spécifique</a>
        </div>
    </section>

    <section id="exception" class="section highlight">
        <h2>Sélection exceptionnelle mise en avant</h2>
        <p><strong>Cuvée Prestige Millésimée 2016</strong> — une référence rare et remarquable, idéale pour les cartes premium et les établissements hôteliers de luxe.</p>
    </section>

    <section id="delivery" class="section">
        <h2>Informations de livraison</h2>
        <p>Délai de livraison standard : <strong>3 à 5 jours ouvrés</strong>.</p>
    </section>

    <section id="contact" class="section">
        <h2>Contact</h2>
        <p>Besoin d’une référence spécifique ou d’une coordination fournisseur ?</p>
        <div class="contact-actions">
            <a class="primary-link" href="mailto:contact@lavingtaine.fr">Nous contacter par e-mail</a>
            <a class="secondary-link" href="tel:+33100000000">Appeler le support fournisseur</a>
        </div>

        <form class="contact-form" method="post" action="#">
            <label>
                Société
                <input type="text" name="company" placeholder="Nom de votre société">
            </label>
            <label>
                E-mail
                <input type="email" name="email" placeholder="pro@societe.com">
            </label>
            <label>
                Message
                <textarea name="message" rows="4" placeholder="Votre demande"></textarea>
            </label>
            <button type="button">Envoyer la demande</button>
        </form>
    </section>
</main>

<footer class="footer">
    <p>© <?= date('Y') ?> La Vingtaine — B2B uniquement</p>
</footer>

<script id="hero-bottles-data" type="application/json"><?= json_encode($heroBottles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script type="importmap">
{
  "imports": {
    "three": "./node_modules/three/build/three.module.js"
  }
}
</script>
<script type="module" src="assets/js/main.js"></script>
</body>
</html>
