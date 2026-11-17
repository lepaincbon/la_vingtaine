<?php
declare(strict_types=1);

$pageTitle = 'La Vingtaine | Confirmation de commande';
$pageDescription = 'Confirmation de votre commande B2B chez La Vingtaine.';
$currentPage = 'confirmation';
$loadThreeScene = false;

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();

require_once __DIR__ . '/includes/header.php';

$pdo = getPDO();
$usingDb = $pdo instanceof PDO;

$lastOrder = null;

$lastOrderId = (int)($_SESSION['last_order_id'] ?? 0);
if ($lastOrderId > 0) {
    if ($usingDb) {
        $stmt = $pdo->prepare(
            "SELECT id, customer_email, company_name, items_json, total_amount, created_at
             FROM orders
             WHERE id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $lastOrderId]);
        $lastOrder = $stmt->fetch() ?: null;
    } else {
        $fallbackOrders = $_SESSION['orders_fallback'] ?? [];
        $index = $lastOrderId - 1;
        $lastOrder = $fallbackOrders[$index] ?? null;
    }
}

if (!$lastOrder) {
    header('Location: selection.php');
    exit;
}

$items = json_decode($lastOrder['items_json'] ?? '[]', true) ?: [];
?>

<main>
    <section class="section">
        <h1>✅ Commande confirmée</h1>

        <div class="message success" role="alert" aria-live="polite">
            Votre commande a été enregistrée avec succès. Notre équipe vous recontactera
            sous 24h ouvrées pour finaliser la livraison.
        </div>

        <div class="confirmation-actions" style="margin: 20px 0;">
            <a href="download_pdf.php?order_id=<?= (int)$lastOrder['id'] ?>" class="button primary" target="_blank" style="display: inline-block; background-color: #4a0a1a; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold;">
                📄 Télécharger le bon de commande PDF
            </a>
        </div>

        <h2>Récapitulatif de la commande</h2>
        <p><strong>Numéro de commande：</strong>#<?= (int)$lastOrder['id'] ?></p>
        <p><strong>Société：</strong><?= htmlspecialchars((string)($lastOrder['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Email：</strong><?= htmlspecialchars((string)($lastOrder['customer_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Date：</strong><?= htmlspecialchars((string)($lastOrder['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>

        <h3>Produits commandés</h3>
        <?php if (count($items) > 0): ?>
            <table class="order-summary">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Prix unitaire</th>
                        <th>Qté</th>
                        <th>Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>€<?= number_format((float)($item['price'] ?? 0), 2) ?></td>
                            <td><?= (int)($item['qty'] ?? 0) ?></td>
                            <td>€<?= number_format((float)($item['price'] ?? 0) * (int)($item['qty'] ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Total HT</strong></td>
                        <td><strong>€<?= number_format((float)($lastOrder['total_amount'] ?? 0), 2) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        <?php else: ?>
            <p>Aucun détail disponible pour cette commande.</p>
        <?php endif; ?>

        <h3>Informations de livraison</h3>
        <ul>
            <li><strong>Délai de livraison：</strong>3 à 5 jours ouvrés</li>
            <li><strong>Zone de livraison：</strong>France métropolitaine et Europe</li>
            <li><strong>Conditions：</strong>Tarifs professionnels HT, TVA applicable selon votre statut</li>
        </ul>

        <p style="margin-top: 2rem;">
            <a class="secondary-button-link" href="catalogue.php">Continuer vos achats</a>
            <a class="primary-link" href="contact.php">Besoin d'aide ?</a>
        </p>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>