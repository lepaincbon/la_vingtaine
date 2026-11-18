<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/mailer.php';

function cliOut(string $message): void
{
    echo $message . PHP_EOL;
}

function minimalPdfContent(string $companyName, int $orderId): string
{
    $text = sprintf('Test commande #%d - %s', $orderId, $companyName);
    $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);

    return "%PDF-1.4\n"
        . "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n"
        . "2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n"
        . "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources<<>> >>endobj\n"
        . "4 0 obj<< /Length 56 >>stream\n"
        . "BT /F1 14 Tf 72 770 Td (" . $escaped . ") Tj ET\n"
        . "endstream endobj\n"
        . "xref\n0 5\n0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n0000000117 00000 n \n0000000224 00000 n \n"
        . "trailer<< /Size 5 /Root 1 0 R >>\nstartxref\n330\n%%EOF\n";
}

$toEmail = envValue('TEST_ORDER_TO', envValue('MAIL_TO_CONTACT', ''));
$company = 'Commande Test La Vingtaine';
$orderId = (int)date('His');

$errors = [];

if (!hasUsableSmtpConfig()) {
    $errors[] = 'BREVO_SMTP_USERNAME / BREVO_SMTP_PASSWORD manquants';
}

if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL) || isForbiddenMailDomain($toEmail)) {
    $errors[] = 'Adresse destinataire invalide dans TEST_ORDER_TO ou MAIL_TO_CONTACT';
}

$fromOrder = envValue('MAIL_FROM_ORDER', '');
if (!filter_var($fromOrder, FILTER_VALIDATE_EMAIL) || isForbiddenMailDomain($fromOrder)) {
    $errors[] = 'MAIL_FROM_ORDER invalide';
}

if (count($errors) > 0) {
    cliOut('❌ Configuration invalide :');
    foreach ($errors as $error) {
        cliOut('- ' . $error);
    }
    exit(1);
}

$pdfContent = minimalPdfContent($company, $orderId);
$sent = sendOrderEmailWithPdf($toEmail, $company, $orderId, $pdfContent);

if ($sent) {
    cliOut('✅ E-mail de test envoyé avec succès.');
    cliOut('Destinataire : ' . $toEmail);
    cliOut('Commande fictive : #' . $orderId);
    exit(0);
}

cliOut('❌ Échec envoi e-mail de test.');
cliOut('Vérifie dans Brevo : Transactional > Logs (raison du rejet).');
exit(2);
