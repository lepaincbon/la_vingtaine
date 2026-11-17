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
            'description' => 'Bulles fines, notes de fruits blancs et finale minérale.'
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
            'description' => 'Cuvée millésimée exceptionnelle avec un grand potentiel de garde.'
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
            'description' => 'Profil vif et élégant, aux notes d’agrumes et de craie.'
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
            'description' => 'Tanins soyeux, fruits noirs et boisé raffiné.'
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
            'description' => 'Arômes complexes de fruits secs, vanille et épices.'
        ]
    ];
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
            description TEXT NOT NULL
        )"
    );

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
