<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';

function loadDotEnvIfPresent(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $envPath = dirname(__DIR__) . '/.env';
    if (!is_file($envPath) || !is_readable($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $eqPos = strpos($trimmed, '=');
        if ($eqPos === false || $eqPos === 0) {
            continue;
        }

        $key = trim(substr($trimmed, 0, $eqPos));
        $value = trim(substr($trimmed, $eqPos + 1));

        if ($key === '') {
            continue;
        }

        $len = strlen($value);
        if ($len >= 2) {
            $first = $value[0];
            $last = $value[$len - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

loadDotEnvIfPresent();

function envValue(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    return trim((string)$value);
}

function smtpUsername(): string
{
    return envValue('BREVO_SMTP_USERNAME', envValue('BREVO_SMTP_USER', ''));
}

function smtpPassword(): string
{
    return envValue('BREVO_SMTP_PASSWORD', envValue('BREVO_SMTP_PASS', ''));
}

function hasUsableSmtpConfig(): bool
{
    return smtpUsername() !== '' && smtpPassword() !== '';
}

function isForbiddenMailDomain(string $email): bool
{
    $atPos = strrpos($email, '@');
    if ($atPos === false) {
        return true;
    }

    $domain = strtolower(substr($email, $atPos + 1));
    return $domain === 'localhost' || str_ends_with($domain, '.local');
}

function safeMailDomain(): string
{
    $domain = strtolower(envValue('APP_MAIL_DOMAIN', 'la-vingtaine.com'));
    if ($domain === '' || $domain === 'localhost' || str_ends_with($domain, '.local') || !str_contains($domain, '.')) {
        return 'la-vingtaine.com';
    }

    return $domain;
}

function configuredEmail(string $envKey, string $fallback): string
{
    $candidate = envValue($envKey, $fallback);
    if (!filter_var($candidate, FILTER_VALIDATE_EMAIL) || isForbiddenMailDomain($candidate)) {
        error_log(sprintf('Mailer config invalide pour %s: %s', $envKey, $candidate));
        return $fallback;
    }

    return $candidate;
}

function createBrevoMailer(string $fromEmail, string $fromName): PHPMailer
{
    if (!hasUsableSmtpConfig()) {
        throw new Exception('Configuration SMTP Brevo manquante. Définir BREVO_SMTP_USERNAME et BREVO_SMTP_PASSWORD.');
    }

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = envValue('BREVO_SMTP_HOST', 'smtp-relay.brevo.com');
    $mail->SMTPAuth = true;
    $mail->Username = smtpUsername();
    $mail->Password = smtpPassword();
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int)envValue('BREVO_SMTP_PORT', '587');
    $mail->CharSet = 'UTF-8';

    if ($mail->Port <= 0) {
        $mail->Port = 587;
    }

    $mail->setFrom($fromEmail, $fromName);

    return $mail;
}

function sendContactNotification(string $customerEmail, string $companyName, string $message): bool
{
    $defaultDomain = safeMailDomain();
    $fallbackFrom = 'contact@' . $defaultDomain;
    $fromEmail = configuredEmail('MAIL_FROM_CONTACT', $fallbackFrom);
    $toEmail = configuredEmail('MAIL_TO_CONTACT', $fallbackFrom);

    try {
        if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            error_log('PHPMailer Error (contact): adresse reply-to invalide');
            return false;
        }

        $mail = createBrevoMailer($fromEmail, 'La Vingtaine Contact');
        $mail->addAddress($toEmail, 'Équipe La Vingtaine');
        $mail->addReplyTo($customerEmail, $companyName);

        $mail->isHTML(true);
        $mail->Subject = 'Nouvelle demande B2B - ' . $companyName;
        $mail->Body = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #fdfcf7; border: 1px solid #e2dac8; }
                h1 { color: #4a0a1a; font-size: 20px; border-bottom: 2px solid #d4af37; padding-bottom: 8px; }
                .details { background: #fff; padding: 15px; border-radius: 4px; margin: 15px 0; white-space: pre-wrap; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Nouvelle demande de contact B2B</h1>
                <p>Une nouvelle demande a été soumise via le site La Vingtaine.</p>
                <div class="details">
                    <p><strong>Société：</strong>' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '</p>
                    <p><strong>Email contact：</strong>' . htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8') . '</p>
                    <p><strong>Message：</strong><br>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>
                </div>
                <p>Merci de recontacter le client sous 24h ouvrées.</p>
                <p>Cordialement,<br><strong>L\'équipe La Vingtaine</strong></p>
            </div>
        </body>
        </html>
        ';

        $mail->send();
        return true;
    } catch (Exception $e) {
        $errorInfo = isset($mail) ? $mail->ErrorInfo : '';
        error_log('PHPMailer Error (contact): ' . $e->getMessage() . ($errorInfo !== '' ? ' | ' . $errorInfo : ''));
        return false;
    }
}

function sendOrderEmailWithPdf(string $toEmail, string $companyName, int $orderId, string $pdfContent): bool
{
    $defaultDomain = safeMailDomain();
    $fallbackFrom = 'commandes@' . $defaultDomain;
    $fromEmail = configuredEmail('MAIL_FROM_ORDER', $fallbackFrom);

    try {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL) || isForbiddenMailDomain($toEmail)) {
            error_log('PHPMailer Error (order): destinataire invalide ' . $toEmail);
            return false;
        }

        $mail = createBrevoMailer($fromEmail, 'La Vingtaine B2B');

        // --- DESTINATAIRE ---
        $mail->addAddress($toEmail, $companyName);

        // --- PIÈCE JOINTE (le PDF) ---
        $mail->addStringAttachment(
            $pdfContent,
            "Bon_de_Commande_La_Vingtaine_" . $orderId . ".pdf",
            'base64',
            'application/pdf'
        );

        // --- CONTENU HTML ---
        $mail->isHTML(true);
        $mail->Subject = "La Vingtaine - Confirmation de votre commande B2B #" . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);

        $mail->Body = "
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
                    <p><strong>Numéro de commande：</strong> #" . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT) . "</p>
                    <p>Votre bon de commande officiel est joint à cet e-mail au format PDF.</p>
                </div>
                
                <p>Notre équipe commerciale vous contactera sous 24h ouvrées pour organiser la logistique de livraison.</p>
                <p>Cordialement,<br><strong>L'équipe La Vingtaine</strong></p>
            </div>
        </body>
        </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        $errorInfo = isset($mail) ? $mail->ErrorInfo : '';
        error_log('PHPMailer Error (order): ' . $e->getMessage() . ($errorInfo !== '' ? ' | ' . $errorInfo : ''));
        return false;
    }
}
