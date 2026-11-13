<?php
declare(strict_types=1);

$pageTitle = 'La Vingtaine | Liste des vins sélectionnés';
$pageDescription = 'Gestion de la sélection B2B et validation de commande.';
$currentPage = 'selection';
$loadThreeScene = false;

require_once __DIR__ . '/includes/header.php';

$pdo = getPDO();
$usingDb = $pdo instanceof PDO;

$message = '';
$messageType = 'info';

$action = $_POST['action'] ?? null;

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

        $message = 'Commande validée. Notre équipe vous contactera rapidement. Livraison sous 3 à 5 jours ouvrés.';
        $messageType = 'success';
        $_SESSION['cart'] = [];
        $cartItems = [];
        $cartTotal = 0.0;
        $remainingAmount = $minimumOrderAmount;
    }
}
?>

<main>
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
                            <h3 class="selection-item-name"><?= htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="selection-item-pricing">Prix unitaire HT/Pro : €<?= number_format((float)$item['price'], 2) ?></div>
                            <div class="selection-subtotal">Sous-total : €<?= number_format($lineSubtotal, 2) ?></div>
                        </div>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="update_qty">
                            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$item['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <label>
                                Quantité
                                <input type="number" name="qty" min="1" value="<?= (int)$item['qty'] ?>">
                            </label>
                            <button type="submit">Mettre à jour</button>
                        </form>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="remove_from_cart">
                            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$item['id'], ENT_QUOTES, 'UTF-8') ?>">
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
            <a class="secondary-button-link" href="contact.php">Contacter le fournisseur pour une demande spécifique</a>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
