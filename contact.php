<?php
declare(strict_types=1);

$pageTitle = 'La Vingtaine | Contact Agence & Fournisseur';
$pageDescription = 'Coordonnées agence, contact fournisseur et demande B2B.';
$currentPage = 'contact';
$loadThreeScene = false;

require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section id="contact" class="section">
        <h2>Contact Agence & Fournisseur</h2>
        <p>Besoin d’une référence spécifique, d’un volume particulier ou d’une coordination fournisseur ?</p>

        <div class="contact-actions">
            <a class="primary-link" href="mailto:contact@lavingtaine.fr">Contacter l’agence par e-mail</a>
            <a class="secondary-link" href="tel:+33100000000">Appeler le support fournisseur</a>
        </div>

        <form class="contact-form" method="post" action="#">
            <label>
                Société
                <input type="text" name="company" placeholder="Nom de votre société">
            </label>
            <label>
                E-mail
                <input type="email" name="email" placeholder="pro@societe.com">
            </label>
            <label>
                Message
                <textarea name="message" rows="4" placeholder="Votre demande B2B"></textarea>
            </label>
            <button type="button">Envoyer la demande</button>
        </form>

        <p style="margin-top: 24px;">
            Instagram :
            <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer">Suivre La Vingtaine</a>
        </p>
        <p>Conditions de livraison : délai standard de <strong>3 à 5 jours ouvrés</strong>.</p>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
