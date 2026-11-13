<?php
declare(strict_types=1);

session_start();

/**
 * Données de catalogue (structure d'exemple prête à évoluer vers 150-200 références).
 * Les prix sont visibles uniquement pour les professionnels.
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
            'description' => 'Bulles fines, notes de fruits blancs et finale minérale.'
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
            'description' => 'Cuvée millésimée exceptionnelle avec un grand potentiel de garde.'
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
            'description' => 'Profil vif et élégant, aux notes d’agrumes et de craie.'
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
            'description' => 'Tanins soyeux, fruits noirs et boisé raffiné.'
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
            'description' => 'Arômes complexes de fruits secs, vanille et épices.'
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
        $message = 'Produit ajouté au panier.';
        $messageType = 'success';
    } else {
        $message = 'Impossible d’ajouter ce produit.';
        $messageType = 'error';
    }
}

if ($action === 'remove_from_cart') {
    $id = (string)($_POST['id'] ?? '');
    if ($id !== '' && isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
        $message = 'Produit retiré du panier.';
        $messageType = 'success';
    }
}

if ($action === 'update_qty') {
    $id = (string)($_POST['id'] ?? '');
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if ($id !== '' && isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['qty'] = $qty;
        $message = 'Panier mis à jour.';
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
        $message = 'Commande bloquée : le minimum de commande est de 300 € (B2B uniquement).';
        $messageType = 'error';
    } elseif (count($cartItems) === 0) {
        $message = 'Votre panier est vide.';
        $messageType = 'error';
    } else {
        $message = 'Demande de commande reçue. Notre équipe vous contactera rapidement.';
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
 * Indicateur professionnel simulé.
 * En production, cela doit provenir d’un vrai système d’authentification/autorisation.
 */
$isProfessional = true;

$heroBottles = [];
foreach ($catalog as $items) {
    foreach ($items as $item) {
        $heroBottles[] = [
            'name' => $item['name'],
            'type' => $item['type'],
            'region' => $item['region'],
            'description' => $item['description']
        ];
    }
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
                        <p class="product-price">Tarif pro : €<?= number_format((float)$product['price'], 2) ?></p>
                    <?php else: ?>
                        <p class="product-price locked">Prix réservé aux professionnels</p>
                    <?php endif; ?>

                    <?php if ($isProfessional): ?>
                        <form method="post" class="add-form">
                            <input type="hidden" name="action" value="add_to_cart">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="name" value="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="price" value="<?= htmlspecialchars((string)$product['price'], ENT_QUOTES, 'UTF-8') ?>">
                            <label>
                                Qté
                                <input type="number" name="qty" min="1" value="1">
                            </label>
                            <button type="submit">Ajouter au panier</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="exception" class="section highlight">
        <h2>Sélection exceptionnelle mise en avant</h2>
        <p><strong>Cuvée Prestige Millésimée 2016</strong> — une référence rare et remarquable, idéale pour les cartes premium et les établissements hôteliers de luxe.</p>
    </section>

    <section class="section">
        <h2>Panier B2B</h2>

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
                            <div>€<?= number_format((float)$item['price'], 2) ?> / unité</div>
                        </div>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="update_qty">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="number" name="qty" min="1" value="<?= (int)$item['qty'] ?>">
                            <button type="submit">Mettre à jour</button>
                        </form>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="remove_from_cart">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="danger">Retirer</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Votre panier est vide.</p>
        <?php endif; ?>

        <div class="cart-total">
            <strong>Total :</strong> €<?= number_format($cartTotal, 2) ?>
        </div>
        <p class="cart-rule">Montant minimum de commande : <strong>300 €</strong> (obligatoire pour valider).</p>

        <form method="post">
            <input type="hidden" name="action" value="validate_order">
            <button type="submit" class="primary">Valider la demande de commande</button>
        </form>
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
