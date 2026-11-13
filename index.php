<?php
declare(strict_types=1);

session_start();

/**
 * Base catalog data (sample structure ready to scale to 150-200 references).
 * Prices are visible only for professional users.
 */
$catalog = [
    'Champagne' => [
        [
            'id' => 'ch-001',
            'name' => 'Champagne Premier Cru Brut',
            'region' => 'Montagne de Reims',
            'type' => 'Champagne',
            'volume' => '75cl',
            'abv' => '12%',
            'grape' => 'Pinot Noir, Chardonnay',
            'price' => 42.00,
            'exception' => false,
            'description' => 'Fine bubbles, white fruit notes, and mineral finish.'
        ],
        [
            'id' => 'ch-002',
            'name' => 'Cuvée Prestige Millésimée 2016',
            'region' => 'Côte des Blancs',
            'type' => 'Champagne',
            'volume' => '75cl',
            'abv' => '12.5%',
            'grape' => '100% Chardonnay',
            'price' => 98.00,
            'exception' => true,
            'description' => 'Exceptional vintage cuvée with long aging potential.'
        ]
    ],
    'Bourgogne' => [
        [
            'id' => 'bg-001',
            'name' => 'Chablis Vieilles Vignes',
            'region' => 'Bourgogne',
            'type' => 'Vin blanc',
            'volume' => '75cl',
            'abv' => '13%',
            'grape' => 'Chardonnay',
            'price' => 29.00,
            'exception' => false,
            'description' => 'Crisp and elegant profile with citrus and chalky notes.'
        ]
    ],
    'Bordeaux' => [
        [
            'id' => 'bdx-001',
            'name' => 'Saint-Émilion Grand Cru',
            'region' => 'Bordeaux',
            'type' => 'Vin rouge',
            'volume' => '75cl',
            'abv' => '14%',
            'grape' => 'Merlot, Cabernet Franc',
            'price' => 46.00,
            'exception' => false,
            'description' => 'Silky tannins, black fruits, and refined oak.'
        ]
    ],
    'Spiritueux' => [
        [
            'id' => 'sp-001',
            'name' => 'Cognac XO Réserve',
            'region' => 'Cognac',
            'type' => 'Spiritueux',
            'volume' => '70cl',
            'abv' => '40%',
            'grape' => 'Ugni Blanc',
            'price' => 120.00,
            'exception' => false,
            'description' => 'Complex aromas of dried fruit, vanilla and spice.'
        ]
    ]
];

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? null;
$message = '';
$messageType = 'info';

if ($action === 'add_to_cart') {
    $id = (string)($_POST['id'] ?? '');
    $name = (string)($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    if ($id !== '' && $name !== '' && $price > 0) {
        if (!isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = [
                'id' => $id,
                'name' => $name,
                'price' => $price,
                'qty' => 0
            ];
        }
        $_SESSION['cart'][$id]['qty'] += $qty;
        $message = 'Product added to cart.';
        $messageType = 'success';
    } else {
        $message = 'Unable to add this product.';
        $messageType = 'error';
    }
}

if ($action === 'remove_from_cart') {
    $id = (string)($_POST['id'] ?? '');
    if ($id !== '' && isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
        $message = 'Product removed from cart.';
        $messageType = 'success';
    }
}

if ($action === 'update_qty') {
    $id = (string)($_POST['id'] ?? '');
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if ($id !== '' && isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['qty'] = $qty;
        $message = 'Cart updated.';
        $messageType = 'success';
    }
}

$cartItems = array_values($_SESSION['cart']);
$cartTotal = 0.0;
foreach ($cartItems as $item) {
    $cartTotal += $item['price'] * $item['qty'];
}

if ($action === 'validate_order') {
    if ($cartTotal < 300) {
        $message = 'Order blocked: minimum order amount is €300 (B2B only).';
        $messageType = 'error';
    } elseif (count($cartItems) === 0) {
        $message = 'Your cart is empty.';
        $messageType = 'error';
    } else {
        $message = 'Order request received. Our team will contact you shortly.';
        $messageType = 'success';
        $_SESSION['cart'] = [];
        $cartItems = [];
        $cartTotal = 0.0;
    }
}

$regions = array_keys($catalog);
$selectedRegion = (string)($_GET['region'] ?? $regions[0]);
if (!isset($catalog[$selectedRegion])) {
    $selectedRegion = $regions[0];
}
$products = $catalog[$selectedRegion];

/**
 * Mock professional flag.
 * In production, this should come from real authentication/authorization.
 */
$isProfessional = true;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Vingtaine | B2B Champagne, Wine & Spirits</title>
    <meta name="description" content="La Vingtaine - B2B agency for champagne, wine and spirits, for restaurants, hotels and palaces.">
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
        <a href="#catalog">Catalog</a>
        <a href="#exception">Exceptional Wine</a>
        <a href="#delivery">Delivery</a>
        <a href="#contact">Contact</a>
        <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer">Instagram</a>
    </nav>
</header>

<main>
    <section class="hero">
        <div class="hero__text">
            <h1>B2B Selection of Champagne, Wines & Spirits</h1>
            <p>For restaurants, hotels and palaces. A curated, premium portfolio and professional support.</p>
        </div>
        <div class="hero__canvas-wrap">
            <canvas id="bottle-scene" aria-label="3D bottle carousel"></canvas>
        </div>
    </section>

    <section id="catalog" class="section">
        <h2>Catalog by Wine Regions</h2>
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
                    <p><strong>Region:</strong> <?= htmlspecialchars($product['region'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Grape:</strong> <?= htmlspecialchars($product['grape'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?></p>

                    <?php if ($isProfessional): ?>
                        <p class="product-price">Pro price: €<?= number_format((float)$product['price'], 2) ?></p>
                    <?php else: ?>
                        <p class="product-price locked">Price reserved for professionals</p>
                    <?php endif; ?>

                    <?php if ($isProfessional): ?>
                        <form method="post" class="add-form">
                            <input type="hidden" name="action" value="add_to_cart">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="name" value="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="price" value="<?= htmlspecialchars((string)$product['price'], ENT_QUOTES, 'UTF-8') ?>">
                            <label>
                                Qty
                                <input type="number" name="qty" min="1" value="1">
                            </label>
                            <button type="submit">Add to cart</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="exception" class="section highlight">
        <h2>Featured Exceptional Selection</h2>
        <p><strong>Cuvée Prestige Millésimée 2016</strong> — an outstanding and rare reference, ideal for premium wine lists and luxury hospitality venues.</p>
    </section>

    <section class="section">
        <h2>B2B Cart</h2>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (count($cartItems) > 0): ?>
            <div class="cart-list">
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                        <div>
                            <strong><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <div>€<?= number_format((float)$item['price'], 2) ?> / unit</div>
                        </div>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="update_qty">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="number" name="qty" min="1" value="<?= (int)$item['qty'] ?>">
                            <button type="submit">Update</button>
                        </form>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="remove_from_cart">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="danger">Remove</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Your cart is empty.</p>
        <?php endif; ?>

        <div class="cart-total">
            <strong>Total:</strong> €<?= number_format($cartTotal, 2) ?>
        </div>
        <p class="cart-rule">Minimum order amount: <strong>€300</strong> (required to validate).</p>

        <form method="post">
            <input type="hidden" name="action" value="validate_order">
            <button type="submit" class="primary">Validate order request</button>
        </form>
    </section>

    <section id="delivery" class="section">
        <h2>Delivery Information</h2>
        <p>Standard delivery time: <strong>3 to 5 business days</strong>.</p>
    </section>

    <section id="contact" class="section">
        <h2>Contact</h2>
        <p>Need a specific reference or supplier coordination?</p>
        <div class="contact-actions">
            <a class="primary-link" href="mailto:contact@lavingtaine.fr">Contact us by email</a>
            <a class="secondary-link" href="tel:+33100000000">Call supplier support</a>
        </div>

        <form class="contact-form" method="post" action="#">
            <label>
                Company
                <input type="text" name="company" placeholder="Your company name">
            </label>
            <label>
                Email
                <input type="email" name="email" placeholder="pro@company.com">
            </label>
            <label>
                Message
                <textarea name="message" rows="4" placeholder="Your request"></textarea>
            </label>
            <button type="button">Send request</button>
        </form>
    </section>
</main>

<footer class="footer">
    <p>© <?= date('Y') ?> La Vingtaine — B2B only</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/three@0.161.0/build/three.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
