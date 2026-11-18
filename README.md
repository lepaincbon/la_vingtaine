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

## Configuration e-mail (Brevo)

Le projet envoie les e-mails via SMTP Brevo (`includes/mailer.php`).

Variables d'environnement recommandées :

- `BREVO_SMTP_HOST` (défaut : `smtp-relay.brevo.com`)
- `BREVO_SMTP_PORT` (défaut : `587`)
- `BREVO_SMTP_USERNAME`
- `BREVO_SMTP_PASSWORD`
- `APP_MAIL_DOMAIN` (ex : `la-vingtaine.com`)
- `MAIL_FROM_CONTACT` (ex : `contact@la-vingtaine.com`)
- `MAIL_FROM_ORDER` (ex : `commandes@la-vingtaine.com`)
- `MAIL_TO_CONTACT` (ex : `contact@la-vingtaine.com`)

Un exemple est fourni dans `.env.example`.

Configuration rapide :

```bash
cp .env.example .env
```

Puis remplace les valeurs `your-brevo-*` par tes identifiants SMTP Brevo.
Le projet charge automatiquement le fichier `.env` (si présent).

⚠️ N'utilise pas de domaine local (`.local` / `localhost`) pour l'expéditeur : Brevo rejettera l'envoi.

### Authentifier le domaine dans Brevo

Pour une délivrabilité correcte et éviter les rejets :

1. Brevo → **Senders, Domains & Dedicated IP** → **Domains**.
2. Ajouter le domaine réel (ex : `la-vingtaine.com`).
3. Créer les enregistrements DNS demandés par Brevo (SPF + DKIM).
4. Ajouter/ajuster une politique DMARC (recommandé).
5. Attendre la propagation DNS puis cliquer **Verify/Authenticate** dans Brevo.
6. Vérifier que les adresses `MAIL_FROM_*` appartiennent bien au domaine authentifié.

### Important sécurité

- Ne stocke jamais le mot de passe SMTP en dur dans le code.
- Fais une rotation de ton mot de passe SMTP Brevo si tu l'as déjà exposé.

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
