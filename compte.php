<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/db.php';

requireAuth();

$pro = currentProfessional();
$pdo = getPDO();
$usingDb = $pdo instanceof PDO;

$companyName = (string)($pro['company_name'] ?? '');
$customerEmail = (string)($pro['customer_email'] ?? '');

$message = '';
$messageType = 'info';

// --- Gestion des informations de compte ---
if (($_POST['action'] ?? '') === 'update_account') {
    if (!validateCsrf()) {
        $message = 'Sécurité : jeton de session invalide, veuillez réessayer.';
        $messageType = 'error';
    } else {
        $newCompany = trim((string)($_POST['company_name'] ?? ''));

        if ($newCompany === '') {
            $message = 'Merci de renseigner le nom de votre société.';
            $messageType = 'error';
        } elseif ($usingDb) {
            $updateStmt = $pdo->prepare(
                "UPDATE professionals SET company_name = :company_name WHERE id = :id"
            );
            $updateStmt->execute([
                ':company_name' => $newCompany,
                ':id' => $pro['id'],
            ]);

            // Mettre à jour la session
            $_SESSION['professional']['company_name'] = $newCompany;

            $companyName = $newCompany;
            $message = 'Vos informations de compte ont été mises à jour.';
            $messageType = 'success';
        } else {
            $message = 'La base de données n’est pas disponible. Merci de réessayer plus tard.';
            $messageType = 'error';
        }
    }
}

// --- Changement de mot de passe ---
if (($_POST['action'] ?? '') === 'change_password') {
    if (!validateCsrf()) {
        $message = 'Sécurité : jeton de session invalide, veuillez réessayer.';
        $messageType = 'error';
    } else {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($currentPassword === '') {
            $message = 'Merci de renseigner votre mot de passe actuel.';
            $messageType = 'error';
        } elseif (strlen($newPassword) < 8) {
            $message = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Les deux mots de passe ne correspondent pas.';
            $messageType = 'error';
        } elseif ($usingDb) {
            // Vérifier le mot de passe actuel
            $verifyStmt = $pdo->prepare(
                "SELECT password_hash FROM professionals WHERE id = :id"
            );
            $verifyStmt->execute([':id' => $pro['id']]);
            $row = $verifyStmt->fetch();

            if ($row && password_verify($currentPassword, (string)$row['password_hash'])) {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare(
                    "UPDATE professionals SET password_hash = :password_hash WHERE id = :id"
                );
                $updateStmt->execute([
                    ':password_hash' => $newHash,
                    ':id' => $pro['id'],
                ]);
                $message = 'Votre mot de passe a été modifié avec succès.';
                $messageType = 'success';
            } else {
                $message = 'Le mot de passe actuel est incorrect.';
                $messageType = 'error';
            }
        } else {
            $message = 'La base de données n’est pas disponible. Merci de réessayer plus tard.';
            $messageType = 'error';
        }
    }
}

$pageTitle = 'La Vingtaine | Gestion de compte';
$pageDescription = 'Modifiez les informations de votre compte professionnel.';
$currentPage = 'compte';
$loadThreeScene = false;

require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section id="compte" class="section auth-section">
        <h2>Gestion de votre compte professionnel</h2>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>" role="alert" aria-live="polite">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="account-forms-grid">
            <!-- Informations de compte -->
            <div class="account-form-card">
                <h3>Informations de compte</h3>
                <p class="account-form-subtitle">Modifiez le nom de votre société. L'e-mail de connexion est définitif.</p>
                <form class="auth-form" method="post" action="compte.php">
                    <input type="hidden" name="action" value="update_account">
                    <?= csrfField() ?>
                    <label>
                        Société
                        <input type="text" name="company_name" required
                               value="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Nom de votre société">
                    </label>
                    <label>
                        E-mail professionnel
                        <input type="email" name="customer_email" disabled
                               value="<?= htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="achat@societe.com">
                    </label>
                    <button type="submit" class="primary">Mettre à jour les informations</button>
                </form>
            </div>

            <!-- Changement de mot de passe -->
            <div class="account-form-card">
                <h3>Changement de mot de passe</h3>
                <p class="account-form-subtitle">Pour des raisons de sécurité, vérifiez votre mot de passe actuel avant d'en définir un nouveau.</p>
                <form class="auth-form" method="post" action="compte.php">
                    <input type="hidden" name="action" value="change_password">
                    <?= csrfField() ?>
                    <label>
                        Mot de passe actuel
                        <input type="password" name="current_password" required
                               placeholder="Votre mot de passe actuel">
                    </label>
                    <label>
                        Nouveau mot de passe
                        <input type="password" name="new_password" required minlength="8"
                               placeholder="Au moins 8 caractères">
                    </label>
                    <label>
                        Confirmer le nouveau mot de passe
                        <input type="password" name="confirm_password" required minlength="8"
                               placeholder="Répétez le nouveau mot de passe">
                    </label>
                    <button type="submit" class="primary">Changer le mot de passe</button>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>