<?php
declare(strict_types=1);

$pageTitle = 'La Vingtaine | Contact Agence & Fournisseur';
$pageDescription = 'Coordonnées agence, contact fournisseur et demande B2B.';
$currentPage = 'contact';
$loadThreeScene = false;

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// La page Contact & Fournisseur est accessible sans compte.
// Le compte professionnel reste optionnel : il permet de pré-remplir
// la société et l'e-mail, mais l'envoi de la demande est soumis à la
// validation obligatoire d'une société et d'un e-mail valide.

$pdo = getPDO();
$usingDb = $pdo instanceof PDO;

$pro = currentProfessional();

$messageType = 'info';
$feedbackMessage = '';

$company = trim((string)($_POST['company'] ?? ($pro['company_name'] ?? '')));
$customerEmail = trim((string)($_POST['email'] ?? ($pro['customer_email'] ?? '')));
$contactMessage = trim((string)($_POST['message'] ?? ''));

if (($_POST['action'] ?? '') === 'send_contact') {
    if (!validateCsrf()) {
        $feedbackMessage = 'Sécurité : jeton de session invalide, veuillez réessayer.';
        $messageType = 'error';
    } elseif ($company === '') {
        $feedbackMessage = 'Merci de renseigner le nom de votre société.';
        $messageType = 'error';
    } elseif ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
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
                ':customer_email' => $customerEmail,
                ':message' => $contactMessage
            ]);
        } else {
            $_SESSION['contact_requests_fallback'][] = [
                'company_name' => $company,
                'customer_email' => $customerEmail,
                'message' => $contactMessage,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        // Envoi de l'e-mail de notification à l'agence
        $mailSent = sendContactNotification($customerEmail, $company, $contactMessage);

        if ($mailSent) {
            $feedbackMessage = 'Demande envoyée. Notre équipe vous recontacte rapidement.';
            $messageType = 'success';
        } else {
            $feedbackMessage = 'Demande enregistrée, mais la notification e-mail a échoué. Merci de contacter directement contact@la-vingtaine.com.';
            $messageType = 'error';
        }
        $company = '';
        $customerEmail = '';
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
            <a class="primary-link" href="mailto:contact@la-vingtaine.com">Contacter l’agence par e-mail</a>
            <a class="secondary-link" href="tel:+331487234567">Appeler le support fournisseur</a>
        </div>

        <?php if ($feedbackMessage !== ''): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>" role="alert" aria-live="polite">
                <?= htmlspecialchars($feedbackMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form class="contact-form" method="post" action="contact.php">
            <input type="hidden" name="action" value="send_contact">
            <?= csrfField() ?>
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
                    value="<?= htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8') ?>"
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
