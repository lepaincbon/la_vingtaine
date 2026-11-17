# la_vingtaine

Site B2B (PHP + SQLite) pour la sélection de champagnes, vins et spiritueux.

## Prérequis

- PHP 8+ avec l'extension **SQLite3**
- Node.js (uniquement pour Three.js, utilisé sur la page d'accueil)

## Installation

```bash
npm install
```

La scène 3D (accueil) utilise Three.js via un `importmap` dans `includes/footer.php`.
Aucun build step n'est nécessaire : le navigateur charge `node_modules/three/build/three.module.js` directement.

## Démarrage

Lance un serveur web simple depuis le répertoire racine :

```bash
php -S localhost:8080
```

La base de données SQLite est créée automatiquement dans `~/.la_vingtaine_data/`
(ou dans `LA_VINGTAINE_DATA_DIR` si cette variable d'environnement est définie).

## Structure

| Fichier | Rôle |
|---|---|
| `index.php` | Accueil avec animation 3D |
| `catalogue.php` | Catalogue par région |
| `carte.php` | Carte SVG interactive des régions |
| `selection.php` | Panier / validation commande B2B |
| `confirmation.php` | Confirmation de commande |
| `contact.php` | Formulaire de contact |
| `apropos.php` | À propos |
| `cgu.php` | Conditions générales d'utilisation |
| `db.php` | Connexion SQLite + schéma |
| `includes/header.php` / `footer.php` | Balises communes |