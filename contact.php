<?php
declare(strict_types=1);

$pageTitle = 'La Vingtaine | Contact Agence & Fournisseur';
$pageDescription = 'Coordonnées agence, contact fournisseur et demande B2B.';
$currentPage = 'contact';
$loadThreeScene = false;

require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pdo = getPDO();
$usingDb = $pdo instanceof PDO;

$messageType = 'info';
$feedbackMessage = '';

$company = trim((string)($_POST['company'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$contactMessage = trim((string)($_POST['message'] ?? ''));

if (($_POST['action'] ?? '') === 'send_contact') {
    if ($company === '') {
        $feedbackMessage = 'Merci de renseigner le nom de votre société.';
        $messageType = 'error';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $feedbackMessage = 'Merci de renseigner une adresse e-mail valide.';
        $messageType = 'error';
    } elseif ($contactMessage === '') {
        $feedbackMessage = 'Merci de préciser votre demande.';
        $messageType = 'error';
    } else {
        if ($usingDb) {
            $insert = $pdo->prepare(
                "INSERT INTO contact_requests (company_name, customer_email, message)
                 VALUES (:company_name, :customer_email, :message)"
            );
            $insert->execute([
                ':company_name' => $company,
                ':customer_email' => $email,
                ':message' => $contactMessage
            ]);
        } else {
            $_SESSION['contact_requests_fallback'][] = [
                'company_name' => $company,
                'customer_email' => $email,
                'message' => $contactMessage,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        $feedbackMessage = 'Demande envoyée. Notre équipe vous recontacte rapidement.';
        $messageType = 'success';
        $company = '';
        $email = '';
        $contactMessage = '';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section id="contact" class="section">
        <h2>Contact Agence & Fournisseur</h2>
        <p>Besoin d’une référence spécifique, d’un volume particulier ou d’une coordination fournisseur ?</p>

        <div class="contact-actions">
            <a class="primary-link" href="mailto:contact@lavingtaine.com">Contacter l’agence par e-mail</a>
            <a class="secondary-link" href="tel:+331487234567">Appeler le support fournisseur</a>
        </div>

        <?php if ($feedbackMessage !== ''): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>" role="alert" aria-live="polite">
                <?= htmlspecialchars($feedbackMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form class="contact-form" method="post" action="contact.php">
            <input type="hidden" name="action" value="send_contact">
            <label>
                Société
                <input
                    type="text"
                    name="company"
                    required
                    placeholder="Nom de votre société"
                    value="<?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?>"
                >
            </label>
            <label>
                E-mail
                <input
                    type="email"
                    name="email"
                    required
                    placeholder="pro@societe.com"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                >
            </label>
            <label>
                Message
                <textarea
                    name="message"
                    rows="4"
                    required
                    placeholder="Votre demande B2B"
                ><?= htmlspecialchars($contactMessage, ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
            <button type="submit">Envoyer la demande</button>
        </form>

        <p style="margin-top: 24px;">
            Instagram :
            <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer">Suivre La Vingtaine</a>
        </p>
        <p>Conditions de livraison : délai standard de <strong>3 à 5 jours ouvrés</strong>.</p>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
