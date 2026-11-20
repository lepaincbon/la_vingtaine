<?php
declare(strict_types=1);

$pageTitle = 'La Vingtaine | Votre sélection';
$pageDescription = 'Consultez et gérez votre sélection de vins B2B.';
$currentPage = 'panier';
$loadThreeScene = false;

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

$pro = currentProfessional();
$cartItems = array_values($_SESSION['cart']);
$cartTotal = 0.0;
foreach ($cartItems as $item) {
    $cartTotal += (float)$item['price'] * (int)$item['qty'];
}
$minimumOrderAmount = 300.0;
$remainingAmount = max(0.0, $minimumOrderAmount - $cartTotal);
?>

<main>
    <section id="panier" class="section selection-section">
        <h2>Votre sélection de vins</h2>

        <?php if (count($cartItems) > 0): ?>
            <div class="cart-list">
                <?php foreach ($cartItems as $item): ?>
                    <?php $lineSubtotal = (float)$item['price'] * (int)$item['qty']; ?>
                    <div class="cart-item">
                        <div class="selection-item-main">
                            <h3 class="selection-item-name"><?= htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="selection-item-pricing">Prix unitaire HT/Pro : €<?= number_format((float)$item['price'], 2) ?></div>
                            <div class="selection-subtotal">Sous-total : €<?= number_format($lineSubtotal, 2) ?></div>
                        </div>
                        <div class="selection-item-qty">
                            <strong>Quantité : <?= (int)$item['qty'] ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Votre sélection est vide.</p>
            <p><a class="secondary-button-link" href="catalogue.php">Découvrir le catalogue</a></p>
        <?php endif; ?>

        <div class="selection-summary">
            <p><strong>Montant total HT : </strong>€<?= number_format($cartTotal, 2) ?></p>
            <p class="cart-rule"><strong>Minimum de commande : 300,00 € pour valider la sélection.</strong></p>
            <?php if ($remainingAmount > 0): ?>
                <p class="cart-rule"><strong>Montant restant pour atteindre le minimum : </strong>€<?= number_format($remainingAmount, 2) ?></p>
            <?php else: ?>
                <p class="cart-rule"><strong>Seuil minimum atteint : </strong>vous pouvez valider la sélection.</p>
            <?php endif; ?>
        </div>

        <div class="selection-actions">
            <?php if ($pro !== null): ?>
                <a class="primary-link" href="selection.php">Valider la sélection</a>
            <?php else: ?>
                <p> Pour finaliser votre commande, merci de vous identifier en tant que professionnel.
                <a href="register.php">Créer un accès professionnel</a> ou
                <a href="login.php">vous connecter</a>.
                </p>
            <?php endif; ?>
            <a class="secondary-button-link" href="catalogue.php">Continuer vos achats</a>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>