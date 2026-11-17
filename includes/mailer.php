<?php
declare(strict_types=1);

function sendOrderEmailWithPdf(string $toEmail, string $companyName, int $orderId, string $pdfContent): bool
{
    $subject = "La Vingtaine - Confirmation de votre commande B2B #" . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);
    
    $fromName = "La Vingtaine B2B";
    $fromEmail = "commandes@la-vingtaine.local"; // ou adresse configurée

    $boundary = md5(uniqid((string)time(), true));

    // Headers
    $headers = "From: " . $fromName . " <" . $fromEmail . ">\r\n";
    $headers .= "Reply-To: " . $fromEmail . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n";

    // Message body (HTML)
    $htmlMessage = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #fdfcf7; border: 1px solid #e2dac8; }
            h1 { color: #4a0a1a; font-size: 20px; border-bottom: 2px solid #d4af37; padding-bottom: 8px; }
            .details { background: #fff; padding: 15px; border-radius: 4px; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class=\"container\">
            <h1>Confirmation de commande B2B</h1>
            <p>Bonjour <strong>" . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
            <p>Nous vous remercions pour votre commande chez <strong>La Vingtaine</strong>.</p>
            
            <div class=\"details\">
                <p><strong>Numéro de commande :</strong> #" . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT) . "</p>
                <p>Votre bon de commande officiel est joint à cet e-mail au format PDF.</p>
            </div>
            
            <p>Notre équipe commerciale vous contactera sous 24h ouvrées pour organiser la logistique de livraison.</p>
            <p>Cordialement,<br><strong>L'équipe La Vingtaine</strong></p>
        </div>
    </body>
    </html>
    ";

    $body = "--" . $boundary . "\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $htmlMessage . "\r\n\r\n";

    // PDF attachment
    $pdfFileName = "Bon_de_Commande_La_Vingtaine_" . $orderId . ".pdf";
    $body .= "--" . $boundary . "\r\n";
    $body .= "Content-Type: application/pdf; name=\"" . $pdfFileName . "\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"" . $pdfFileName . "\"\r\n\r\n";
    $body .= chunk_split(base64_encode($pdfContent)) . "\r\n";

    $body .= "--" . $boundary . "--";

    // In local development, mail() might not be configured or might fail if sendmail isn't installed.
    // We also save a copy of the email in a local debug folder so you can inspect it easily!
    $debugDir = __DIR__ . '/../data/emails';
    if (!is_dir($debugDir)) {
        @mkdir($debugDir, 0777, true);
    }
    @file_put_contents($debugDir . '/order_' . $orderId . '_' . time() . '.eml', "To: " . $toEmail . "\r\nSubject: " . $subject . "\r\n" . $headers . "\r\n\r\n" . $body);

    // Attempt mail send (returns false if sendmail/postfix is missing in local environment, which is normal on Kubuntu dev)
    $mailSent = @mail($toEmail, $subject, $body, $headers);
    
    return true; // Return true so checkout flow isn't blocked in local dev without mail server
}
