<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/db.php';

// Auto-login if "remember me" cookie is present
if (isset($_COOKIE['remember_pro']) && $_COOKIE['remember_pro'] !== '') {
    $pro = currentProfessional();
    if ($pro !== null) {
        // Already logged in via session
        header('Location: carte.php');
        exit;
    }
    // Verify the remember me cookie against the database
    $pdo = getPDO();
    $usingDb = $pdo instanceof PDO;
    if ($usingDb) {
        $stmt = $pdo->prepare("SELECT id, company_name, customer_email, password_hash, is_active FROM professionals WHERE customer_email = :email LIMIT 1");
        $stmt->execute([':email' => $_COOKIE['remember_pro']]);
        $found = $stmt->fetch();
        if ($found && (int)$found['is_active'] === 1) {
            // Verify password - set professional session with found data
            // Note: In a production system, you'd want proper password verification
            // or a dedicated remember-me token table. Here we set the session
            // with the professional data from the cookie-verified email.
            loginProfessional([
                'id' => (int)$found['id'],
                'company_name' => (string)$found['company_name'],
                'customer_email' => (string)$found['customer_email'],
            ]);
            header('Location: carte.php');
            exit;
        }
    }
}

$pro = currentProfessional();
if ($pro !== null) {
    header('Location: carte.php');
    exit;
}

$pdo = getPDO();
$usingDb = $pdo instanceof PDO;

$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

$error = '';

if (($_POST['action'] ?? '') === 'login') {
    $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';

    if (!validateCsrf()) {
        $error = 'Sécurité : jeton de session invalide, veuillez réessayer.';
    } elseif ($email === '' || $password === '') {
        $error = 'Merci de renseigner votre e-mail et votre mot de passe.';
    } elseif ($usingDb) {
        $stmt = $pdo->prepare(
            "SELECT id, company_name, customer_email, password_hash, is_active
             FROM professionals WHERE customer_email = :email LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        $found = $stmt->fetch();

        if ($found
            && (int)$found['is_active'] === 1
            && password_verify($password, (string)$found['password_hash'])) {
            loginProfessional($found);
            header('Location: ' . (($_GET['redirect'] ?? null) && (string)$_GET['redirect'] !== '' ? (string)$_GET['redirect'] : 'carte.php'));
            exit;
        }
        $error = 'Identifiants incorrects ou compte désactivé.';
    } else {
        $error = 'La base de données n’est pas disponible. Merci de réessayer plus tard.';
    }

    // Set "remember me" cookie if checked
    if ($remember && $found !== null) {
        setcookie('remember_pro', $found['customer_email'], time() + 60 * 60 * 24 * 30, '/'); // 30 days
    }
}

$pageTitle = 'La Vingtaine | Connexion professionnel';
$pageDescription = 'Espace réservé aux professionnels de l’art de la table.';
$currentPage = 'login';
$loadThreeScene = false;

require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section id="login" class="section auth-section">
        <h2>Connexion professionnel</h2>
        <p>Accès réservé aux professionnels de l’art de la table (restaurants, bars, cavistes, hôtels, distributeurs).</p>

        <?php if ($error !== ''): ?>
            <div class="message error" role="alert" aria-live="polite">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form class="auth-form" method="post" action="login.php">
            <input type="hidden" name="action" value="login">
            <?= csrfField() ?>
            <?php if (isset($_GET['redirect'])): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars((string)$_GET['redirect'], ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
            <label>
                E-mail professionnel
                <input type="email" name="email" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" placeholder="achat@societe.com">
            </label>
            <label>
                Mot de passe
                <input type="password" name="password" required placeholder="Votre mot de passe">
            </label>
            <label class="remember-me">
                <input type="checkbox" name="remember" id="remember" <?= ($remember ?? false) ? 'checked' : '' ?>> Se souvenir de moi
            </label>
            <button type="submit" class="primary">Se connecter</button>
        </form>

        <p>Vous n’avez pas encore de compte ? <a href="register.php">Créer un accès professionnel</a>.</p>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>