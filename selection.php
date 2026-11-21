<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

$pageTitle = 'La Vingtaine | Liste des vins sélectionnés';
$pageDescription = 'Gestion de la sélection B2B et validation de commande.';
$currentPage = 'selection';
$loadThreeScene = false;

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/generate_pdf.php';

requireAuth();

require_once __DIR__ . '/includes/header.php';

$pdo = getPDO();
$usingDb = $pdo instanceof PDO;

$pro = currentProfessional();
$customerEmail = trim((string)($_POST['customer_email'] ?? ($pro['customer_email'] ?? '')));
$companyName = trim((string)($_POST['company_name'] ?? ($pro['company_name'] ?? '')));

$message = '';
$messageType = 'info';

$action = $_POST['action'] ?? null;

if ($action === 'remove_from_cart') {
    if (!validateCsrf()) {
        $message = 'Sécurité : jeton de session invalide, veuillez réessayer.';
        $messageType = 'error';
    } else {
        $id = (string)($_POST['id'] ?? '');
        if ($id !== '' && isset($_SESSION['cart'][$id])) {
            unset($_SESSION['cart'][$id]);
            $message = 'Vin retiré de votre sélection.';
            $messageType = 'success';
        }
    }
}

if ($action === 'update_qty') {
    if (!validateCsrf()) {
        $message = 'Sécurité : jeton de session invalide, veuillez réessayer.';
        $messageType = 'error';
    } else {
        $id = (string)($_POST['id'] ?? '');
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        if ($id !== '' && isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty'] = $qty;
            $message = 'Votre sélection a été mise à jour.';
            $messageType = 'success';
        }
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
    if (!validateCsrf()) {
        $message = 'Sécurité : jeton de session invalide, veuillez réessayer.';
        $messageType = 'error';
    } elseif ($cartTotal < $minimumOrderAmount) {
        $message = 'Validation impossible : minimum de commande B2B de 300,00 € non atteint.';
        $messageType = 'error';
    } elseif (count($cartItems) === 0) {
        $message = 'Votre sélection est vide.';
        $messageType = 'error';
    } elseif ($companyName === '') {
        $message = 'Merci de renseigner le nom de votre société.';
        $messageType = 'error';
    } elseif ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $message = 'Merci de renseigner une adresse e-mail professionnelle valide.';
        $messageType = 'error';
    } elseif (empty($_POST['certify_adult'])) {
        $message = 'Vous devez certifier être majeur(e) et agir en qualité de professionnel pour valider la sélection.';
        $messageType = 'error';
    } else {
        if ($usingDb) {
            $orderInsert = $pdo->prepare(
                "INSERT INTO orders (customer_email, company_name, items_json, total_amount)
                 VALUES (:customer_email, :company_name, :items_json, :total_amount)"
            );
            $orderInsert->execute([
                ':customer_email' => $customerEmail,
                ':company_name' => $companyName,
                ':items_json' => json_encode($cartItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ':total_amount' => $cartTotal
            ]);
            $_SESSION['last_order_id'] = (int)$pdo->lastInsertId();
            $orderId = $_SESSION['last_order_id'];

            // Générer PDF et envoyer e-mail
            $pdfContent = generateOrderPdfOutput($orderId, [
                'created_at' => date('Y-m-d H:i:s'),
                'company_name' => $companyName,
                'customer_email' => $customerEmail
            ], $cartItems, $pro ?? ['company_name' => $companyName, 'customer_email' => $customerEmail]);

            $orderEmailSent = sendOrderEmailWithPdf($customerEmail, $companyName, $orderId, $pdfContent);
            $_SESSION['order_email_status'] = $orderEmailSent ? 'sent' : 'failed';
        } else {
            $_SESSION['orders_fallback'][] = [
                'customer_email' => $customerEmail,
                'company_name' => $companyName,
                'items_json' => json_encode($cartItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'total_amount' => $cartTotal,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $_SESSION['last_order_id'] = (int)count($_SESSION['orders_fallback']);
            $orderId = $_SESSION['last_order_id'];

            // Générer PDF et envoyer e-mail
            $pdfContent = generateOrderPdfOutput($orderId, [
                'created_at' => date('Y-m-d H:i:s'),
                'company_name' => $companyName,
                'customer_email' => $customerEmail
            ], $cartItems, $pro ?? ['company_name' => $companyName, 'customer_email' => $customerEmail]);

            $orderEmailSent = sendOrderEmailWithPdf($customerEmail, $companyName, $orderId, $pdfContent);
            $_SESSION['order_email_status'] = $orderEmailSent ? 'sent' : 'failed';
        }

        $message = 'Commande validée. Redirection vers la page de confirmation…';
        $messageType = 'success';
        $_SESSION['cart'] = [];
        $cartItems = [];
        $cartTotal = 0.0;
        $remainingAmount = $minimumOrderAmount;
        $customerEmail = '';
        $companyName = '';

        header('Location: confirmation.php');
        exit;
    }
}
?>

<main>
    <section id="selection-b2b" class="section selection-section">
        <h2>Liste des vins sélectionnés</h2>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>" role="alert" aria-live="polite">
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
                            <?= csrfField() ?>
                            <label>
                                Quantité
                                <input type="number" name="qty" min="1" value="<?= (int)$item['qty'] ?>">
                            </label>
                            <button type="submit">Mettre à jour</button>
                        </form>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="remove_from_cart">
                            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$item['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= csrfField() ?>
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
                <?= csrfField() ?>
                <label>
                    Société
                    <input
                        type="text"
                        name="company_name"
                        required
                        value="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Nom de votre société"
                    >
                </label>
                <label>
                    E-mail professionnel
                    <input
                        type="email"
                        name="customer_email"
                        required
                        value="<?= htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="achat@societe.com"
                    >
                </label>
                <label style="display: flex; align-items: flex-start; gap: 10px; margin: 1rem 0; cursor: pointer;">
                    <input
                        type="checkbox"
                        name="certify_adult"
                        value="1"
                        required
                        <?= !empty($_POST['certify_adult']) ? 'checked' : '' ?>
                        style="width: auto; margin-top: 4px;"
                    >
                    <span>
                        Je certifie être majeur(e) (18 ans révolus) et agir en qualité de professionnel.
                        Je reconnais que les tarifs affichés sont indicatifs, exprimés en HT et non contractuels.
                    </span>
                </label>
                <button type="submit" class="primary" <?= ($cartTotal < $minimumOrderAmount || count($cartItems) === 0) ? 'disabled' : '' ?>>
                    Valider la sélection
                </button>
            </form>
            <a class="secondary-button-link" href="contact.php">Contacter le fournisseur pour une demande spécifique</a>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
