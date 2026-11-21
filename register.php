<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/db.php';

$pro = currentProfessional();
if ($pro !== null) {
    header('Location: carte.php');
    exit;
}

$pdo = getPDO();
$usingDb = $pdo instanceof PDO;

$company = trim((string)($_POST['company_name'] ?? ''));
$email = trim((string)($_POST['customer_email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$passwordConfirm = (string)($_POST['password_confirm'] ?? '');
$acceptCgu = isset($_POST['accept_cgu']);

$error = '';

if (($_POST['action'] ?? '') === 'register') {
    if (!validateCsrf()) {
        $error = 'Sécurité : jeton de session invalide, veuillez réessayer.';
    } elseif ($company === '') {
        $error = 'Merci de renseigner le nom de votre société.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Merci de renseigner une adresse e-mail professionnelle valide.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Les deux mots de passe ne correspondent pas.';
    } elseif (!$acceptCgu) {
        $error = 'Merci d’accepter les Conditions Générales d’Utilisation pour créer votre compte.';
    } elseif ($usingDb) {
        $exists = $pdo->prepare("SELECT COUNT(*) FROM professionals WHERE customer_email = :email");
        $exists->execute([':email' => $email]);
        if ((int)$exists->fetchColumn() > 0) {
            $error = 'Un compte existe déjà avec cette adresse e-mail.';
        } else {
            $insert = $pdo->prepare(
                "INSERT INTO professionals (company_name, customer_email, password_hash)
                 VALUES (:company_name, :customer_email, :password_hash)"
            );
            $insert->execute([
                ':company_name' => $company,
                ':customer_email' => $email,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT)
            ]);

            $stmt = $pdo->prepare("SELECT id, company_name, customer_email, password_hash, is_active FROM professionals WHERE id = " . (int)$pdo->lastInsertId());
            $stmt->execute();
            $created = $stmt->fetch();

            if ($created) {
                loginProfessional($created);
                header('Location: carte.php');
                exit;
            }
            $error = 'L’inscription a échoué. Merci de réessayer.';
        }
    } else {
        $error = 'La base de données n’est pas disponible. Merci de réessayer plus tard.';
    }
}

$pageTitle = 'La Vingtaine | Inscription professionnel';
$pageDescription = 'Créez votre accès B2B à la sélection La Vingtaine.';
$currentPage = 'register';
$loadThreeScene = false;

require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section id="register" class="section auth-section">
        <h2>Inscription professionnel</h2>
        <p>Créez votre accès pour consulter les tarifs professionnels et finaliser vos sélections.</p>

        <?php if ($error !== ''): ?>
            <div class="message error" role="alert" aria-live="polite">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form class="auth-form" method="post" action="register.php">
            <input type="hidden" name="action" value="register">
            <?= csrfField() ?>
            <label>
                Société
                <input type="text" name="company_name" required value="<?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nom de votre société">
            </label>
            <label>
                E-mail professionnel
                <input type="email" name="customer_email" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" placeholder="achat@societe.com">
            </label>
            <label>
                Mot de passe
                <input type="password" name="password" required minlength="8" placeholder="Au moins 8 caractères">
            </label>
            <label>
                Confirmer le mot de passe
                <input type="password" name="password_confirm" required minlength="8" placeholder="Répétez le mot de passe">
            </label>
            <label class="cgu-accept">
                <input type="checkbox" name="accept_cgu" required>
                <span>J’accepte les <a href="cgu.php" target="_blank" rel="noopener noreferrer">Conditions Générales d’Utilisation</a>.</span>
            </label>
            <button type="submit" class="primary">Créer mon accès</button>
        </form>

        <p>Déjà inscrit ? <a href="login.php">Se connecter</a>.</p>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>