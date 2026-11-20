<?php
declare(strict_types=1);

function sqliteDriverAvailable(): bool
{
    return in_array('sqlite', PDO::getAvailableDrivers(), true);
}

function getPDO(): ?PDO
{
    static $pdo = false;

    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if ($pdo === null) {
        return null;
    }

    if (!sqliteDriverAvailable()) {
        $pdo = null;
        return null;
    }

    $dataDir = getDataDirectory();
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0775, true);
    }

    $dbPath = $dataDir . '/la_vingtaine.sqlite';
    $legacyDbPath = __DIR__ . '/data/la_vingtaine.sqlite';

    if (!is_file($dbPath) && is_file($legacyDbPath)) {
        if (!copy($legacyDbPath, $dbPath)) {
            error_log('La Vingtaine: failed to migrate legacy database from ' . $legacyDbPath);
        }
    }

    try {
        $instance = new PDO('sqlite:' . $dbPath);
        $instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        initializeDatabase($instance);
        $pdo = $instance;
        return $pdo;
    } catch (Throwable $e) {
        error_log('La Vingtaine DB error: ' . $e->getMessage());
        $pdo = null;
        return null;
    }
}

function getDataDirectory(): string
{
    $configuredDir = trim((string)getenv('LA_VINGTAINE_DATA_DIR'));
    if ($configuredDir !== '') {
        return $configuredDir;
    }

    return dirname(__DIR__) . '/.la_vingtaine_data';
}

function getSeedProducts(): array
{
    return [
        [
            'id' => 'ch-001',
            'name' => 'Champagne Premier Cru Brut',
            'region' => 'Montagne de Reims',
            'category' => 'Champagne',
            'type' => 'Champagne',
            'volume' => '75cl',
            'abv' => '12%',
            'grape' => 'Pinot Noir, Chardonnay',
            'price' => 42.00,
            'is_exception' => 0,
            'description' => 'Bulles fines, notes de fruits blancs et finale minérale.',
            'image' => ''
        ],
        [
            'id' => 'ch-002',
            'name' => 'Cuvée Prestige Millésimée 2016',
            'region' => 'Côte des Blancs',
            'category' => 'Champagne',
            'type' => 'Champagne',
            'volume' => '75cl',
            'abv' => '12.5%',
            'grape' => '100% Chardonnay',
            'price' => 98.00,
            'is_exception' => 1,
            'description' => 'Cuvée millésimée exceptionnelle avec un grand potentiel de garde.',
            'image' => ''
        ],
        [
            'id' => 'bg-001',
            'name' => 'Chablis Vieilles Vignes',
            'region' => 'Bourgogne',
            'category' => 'Bourgogne',
            'type' => 'Vin blanc',
            'volume' => '75cl',
            'abv' => '13%',
            'grape' => 'Chardonnay',
            'price' => 29.00,
            'is_exception' => 0,
            'description' => 'Profil vif et élégant, aux notes d’agrumes et de craie.',
            'image' => ''
        ],
        [
            'id' => 'bdx-001',
            'name' => 'Saint-Émilion Grand Cru',
            'region' => 'Bordeaux',
            'category' => 'Bordeaux',
            'type' => 'Vin rouge',
            'volume' => '75cl',
            'abv' => '14%',
            'grape' => 'Merlot, Cabernet Franc',
            'price' => 46.00,
            'is_exception' => 0,
            'description' => 'Tanins soyeux, fruits noirs et boisé raffiné.',
            'image' => ''
        ],
        [
            'id' => 'sp-001',
            'name' => 'Cognac XO Réserve',
            'region' => 'Cognac',
            'category' => 'Spiritueux',
            'type' => 'Spiritueux',
            'volume' => '70cl',
            'abv' => '40%',
            'grape' => 'Ugni Blanc',
            'price' => 120.00,
            'is_exception' => 0,
            'description' => 'Arômes complexes de fruits secs, vanille et épices.',
            'image' => ''
        ]
    ];
}

function getProductImage(array $product): string
{
    $image = (string)($product['image'] ?? '');
    if ($image !== '') {
        return $image;
    }

    $type = mb_strtolower((string)($product['type'] ?? $product['category'] ?? ''));
    
    $bottleColor = '#6b4c3b';
    $liquidColor = '#5a1022';
    $labelBg = '#fcf8f2';
    $capColor = '#8c2438';
    $accentColor = '#c9a34f';

    if (str_contains($type, 'champagne')) {
        $bottleColor = '#3a4a34';
        $liquidColor = '#c9a34f';
        $capColor = '#d4af37';
        $labelBg = '#faf7f0';
    } elseif (str_contains($type, 'blanc') || str_contains($type, 'rosé')) {
        $bottleColor = '#4a5d43';
        $liquidColor = '#e6c875';
        $capColor = '#b89d5b';
        $labelBg = '#faf7f0';
    } elseif (str_contains($type, 'spiritueux') || str_contains($type, 'cognac') || str_contains($type, 'rhum')) {
        $bottleColor = '#5c3a21';
        $liquidColor = '#b8742c';
        $capColor = '#4a2d18';
        $labelBg = '#f5ebdc';
    }

    $name = htmlspecialchars((string)($product['name'] ?? 'La Vingtaine'), ENT_QUOTES, 'UTF-8');
    $sub = htmlspecialchars((string)($product['region'] ?? 'Grand Cru'), ENT_QUOTES, 'UTF-8');

    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 500" width="100%" height="100%">
  <rect width="400" height="500" fill="#f8f3eb" rx="12"/>
  <circle cx="200" cy="240" r="150" fill="#f0e6d8" opacity="0.5"/>
  <g transform="translate(100, 40)">
    <!-- Capsule -->
    <path d="M85 45 L115 45 L118 85 L82 85 Z" fill="{$capColor}"/>
    <!-- Neck -->
    <path d="M86 85 L114 85 L118 160 L82 160 Z" fill="{$bottleColor}"/>
    <!-- Body -->
    <path d="M82 160 Q60 190 55 240 L55 400 Q55 415 70 415 L130 415 Q145 415 145 400 L145 240 Q140 190 118 160 Z" fill="{$bottleColor}"/>
    <!-- Reflection/Shine -->
    <path d="M68 240 Q65 200 84 170 L90 170 Q75 200 75 240 L75 395 L68 395 Z" fill="#ffffff" opacity="0.25"/>
    <!-- Label -->
    <rect x="65" y="240" width="70" height="110" rx="4" fill="{$labelBg}" stroke="{$accentColor}" stroke-width="1.5"/>
    <rect x="70" y="245" width="60" height="100" rx="2" fill="none" stroke="{$accentColor}" stroke-width="0.5" stroke-dasharray="2,2"/>
    <text x="100" y="280" font-family="Cormorant Garamond, serif" font-size="11" font-weight="bold" fill="#3a2518" text-anchor="middle">LA VINGTAINE</text>
    <line x1="80" y1="290" x2="120" y2="290" stroke="{$accentColor}" stroke-width="0.8"/>
    <text x="100" y="315" font-family="Inter, sans-serif" font-size="8" fill="#6e4530" text-anchor="middle">SÉLECTION</text>
  </g>
</svg>
SVG;

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

function getProductImageById(string $productId): string
{
    $pdo = getPDO();
    if ($pdo instanceof PDO) {
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = :id");
        $stmt->execute([':id' => $productId]);
        $row = $stmt->fetch();
        if ($row && (string)($row['image'] ?? '') !== '') {
            return (string)$row['image'];
        }
    } else {
        foreach (getSeedProducts() as $seedProduct) {
            if ((string)$seedProduct['id'] === $productId) {
                return getProductImage($seedProduct);
            }
        }
    }

    return getProductImage(['id' => $productId, 'type' => 'vin']);
}

function initializeDatabase(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS products (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            region TEXT NOT NULL,
            category TEXT NOT NULL,
            type TEXT NOT NULL,
            volume TEXT NOT NULL,
            abv TEXT NOT NULL,
            grape TEXT NOT NULL,
            price REAL NOT NULL,
            is_exception INTEGER NOT NULL DEFAULT 0,
            description TEXT NOT NULL,
            image TEXT NOT NULL DEFAULT ''
        )"
    );

    // Migration : ajouter la colonne image si elle n'existe pas encore
    $hasImageColumn = false;
    $columns = $pdo->query("PRAGMA table_info(products)")->fetchAll();
    foreach ($columns as $col) {
        if (isset($col['name']) && $col['name'] === 'image') {
            $hasImageColumn = true;
            break;
        }
    }
    if (!$hasImageColumn) {
        $pdo->exec("ALTER TABLE products ADD COLUMN image TEXT NOT NULL DEFAULT ''");
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_email TEXT,
            company_name TEXT,
            items_json TEXT NOT NULL,
            total_amount REAL NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS contact_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            company_name TEXT NOT NULL,
            customer_email TEXT NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS professionals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            company_name TEXT NOT NULL,
            customer_email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $count = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($count > 0) {
        return;
    }

    $insert = $pdo->prepare(
        "INSERT INTO products (
            id, name, region, category, type, volume, abv, grape, price, is_exception, description
        ) VALUES (
            :id, :name, :region, :category, :type, :volume, :abv, :grape, :price, :is_exception, :description
        )"
    );

    foreach (getSeedProducts() as $product) {
        $insert->execute($product);
    }
}
