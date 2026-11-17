<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/generate_pdf.php';

$orderId = (int)($_GET['order_id'] ?? $_SESSION['last_order_id'] ?? 0);
if ($orderId <= 0) {
    http_response_code(404);
    echo "Commande introuvable.";
    exit;
}

$pdo = getPDO();
$usingDb = $pdo instanceof PDO;

if (!$usingDb) {
    http_response_code(500);
    echo "Base de données indisponible.";
    exit;
}

// Fetch order
$orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
$orderStmt->execute([':id' => $orderId]);
$order = $orderStmt->fetch();

if (!$order) {
    http_response_code(404);
    echo "Commande introuvable.";
    exit;
}

// Fetch items from JSON field
$items = json_decode($order['items_json'] ?? '[]', true) ?: [];

$pro = currentProfessional() ?? [
    'company_name' => $order['company_name'] ?? 'Client B2B',
    'customer_email' => $order['customer_email'] ?? 'contact@societe.com'
];

$pdfContent = generateOrderPdfOutput($orderId, $order, $items, $pro);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="Bon_de_Commande_La_Vingtaine_' . $orderId . '.pdf"');
header('Content-Length: ' . strlen($pdfContent));
echo $pdfContent;
exit;
