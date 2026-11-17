<?php
declare(strict_types=1);

$pageTitle = 'La Vingtaine | À propos';
$pageDescription = 'Découvrez La Vingtaine, agence B2B spécialisée dans la sélection de champagnes, vins et spiritueux pour les professionnels.';
$currentPage = 'apropos';
$loadThreeScene = false;

require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="section">
        <h1>À propos de La Vingtaine</h1>

        <h2>Notre histoire</h2>
        <p>
            La Vingtaine est une agence B2B spécialisée dans la sélection et la distribution
            de champagnes, vins et spiritueux pour les professionnels de l'art de la table :
            restaurants, bars, cavistes, hôtels et distributeurs.
        </p>
        <p>
            Notre nom évoque la vingtaine, cette équipe de vingt experts unis par une même passion :
            celle des grands vins et de l'excellence du service.
        </p>

        <h2>Notre mission</h2>
        <p>
            Vous accompagner dans la constitution de votre sélection de boissons alcoolisées
            en vous proposant des références authentiques, des tarifs professionnels adaptés
            à votre activité, et un suivi personnalisé de votre relation fournisseur.
        </p>

        <h2>Notre engagement</h2>
        <ul>
            <li><strong>Sélection rigoureuse</strong> : chaque produit est choisi pour sa qualité et sa typicité.</li>
            <li><strong>Tarifs dédiés aux professionnels</strong> : des prix HT adaptés à votre volume d'activité.</li>
            <li><strong>Suivi personnalisé</strong> : un interlocuteur unique pour vos demandes B2B.</li>
            <li><strong>Livraison rapide</strong> : livraison sous 3 à 5 jours ouvrés.</li>
        </ul>

        <h2>Qui sommes-nous ?</h2>
        <p>
            La Vingtaine est une agence indépendante, fondée par des amateurs de vins
            et des professionnels du commerce de gros. Nous travaillons en étroite collaboration
            avec nos productes pour vous proposer les meilleures références du marché.
        </p>

        <h2>Nos valeurs</h2>
        <p>
            <strong>Exigence</strong>, <strong>confidentialité</strong>, <strong>proximité</strong>
            et <strong>excellence</strong> guident chacune de nos actions au service de nos clients professionnels.
        </p>

        <h2>Nous contacter</h2>
        <p>
            Pour toute question, demande de catalogue personnalisé ou coordination fournisseur :
            <a href="contact.php">Contactez notre équipe</a>.
        </p>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>