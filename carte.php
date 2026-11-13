<?php
declare(strict_types=1);

$pageTitle = 'La Vingtaine | Carte des Régions';
$pageDescription = 'Explorez le catalogue B2B via une carte interactive des régions viticoles.';
$currentPage = 'carte';
$loadThreeScene = false;

require_once __DIR__ . '/includes/header.php';

$pdo = getPDO();
$usingDb = $pdo instanceof PDO;

if ($usingDb) {
    $allProductsStmt = $pdo->query(
        "SELECT id, name, region, category, type, volume, abv, grape, price, is_exception, description
         FROM products
         ORDER BY category ASC, name ASC"
    );
    $allProducts = $allProductsStmt->fetchAll();
} else {
    $allProducts = getSeedProducts();
}

$productsById = [];
$catalog = [];

foreach ($allProducts as $row) {
    $id = (string)$row['id'];
    $category = (string)$row['category'];

    $productsById[$id] = $row;

    if (!isset($catalog[$category])) {
        $catalog[$category] = [];
    }

    $catalog[$category][] = [
        'id' => $id,
        'name' => (string)$row['name'],
        'region' => (string)$row['region'],
        'type' => (string)$row['type'],
        'volume' => (string)$row['volume'],
        'abv' => (string)$row['abv'],
        'grape' => (string)$row['grape'],
        'price' => (float)$row['price'],
        'exception' => ((int)$row['is_exception']) === 1,
        'description' => (string)$row['description']
    ];
}

$availableRegions = array_keys($catalog);
sort($availableRegions);

$selectedRegion = (string)($_GET['region'] ?? ($availableRegions[0] ?? ''));
if ($selectedRegion === '' || !isset($catalog[$selectedRegion])) {
    $selectedRegion = $availableRegions[0] ?? '';
}

$message = '';
$messageType = 'info';
$selectionLink = '';

$action = $_POST['action'] ?? null;
if ($action === 'add_to_cart') {
    $id = (string)($_POST['id'] ?? '');
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $regionFromPost = (string)($_POST['region'] ?? $selectedRegion);

    $product = $productsById[$id] ?? null;

    if ($product) {
        if (!isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = [
                'id' => (string)$product['id'],
                'name' => (string)$product['name'],
                'price' => (float)$product['price'],
                'qty' => 0
            ];
        }
        $_SESSION['cart'][$id]['price'] = (float)$product['price'];
        $_SESSION['cart'][$id]['name'] = (string)$product['name'];
        $_SESSION['cart'][$id]['qty'] += $qty;

        $redirectRegion = $regionFromPost !== '' ? $regionFromPost : $selectedRegion;
        header('Location: carte.php?region=' . urlencode($redirectRegion) . '&added=1');
        exit;
    }

    $message = 'Produit introuvable ou indisponible.';
    $messageType = 'error';
}

if (isset($_GET['added']) && $_GET['added'] === '1') {
    $message = 'Vin ajouté à votre sélection.';
    $messageType = 'success';
    $selectionLink = 'selection.php';
}

$products = $selectedRegion !== '' ? $catalog[$selectedRegion] : [];
$isProfessional = true;
?>

<main>
    <section class="section" id="carte-regions">
        <h2>Carte des Régions</h2>
        <p>Cliquez sur une zone géographique pour filtrer instantanément le catalogue professionnel.</p>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                <?php if ($selectionLink !== ''): ?>
                    <br><a href="<?= htmlspecialchars($selectionLink, ENT_QUOTES, 'UTF-8') ?>">Voir ma sélection</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form id="region-filter-form" method="get" action="carte.php" style="display:none;">
            <input id="selected-region-input" type="hidden" name="region" value="<?= htmlspecialchars($selectedRegion, ENT_QUOTES, 'UTF-8') ?>">
            <button id="region-submit-btn" type="submit">Filtrer</button>
        </form>

        <div class="map-layout">
            <div class="map-visual">
                <div class="map-object-wrap">
                    <object
                        id="france-map-object"
                        type="image/svg+xml"
                        data="assets/departements.svg"
                        aria-label="Carte interactive de la France métropolitaine"
                    ></object>
                </div>

                <div class="regions" style="margin-top:12px;">
                    <?php foreach ($availableRegions as $region): ?>
                        <a
                            class="region-pill <?= $region === $selectedRegion ? 'is-active' : '' ?>"
                            href="carte.php?region=<?= urlencode($region) ?>#carte-regions"
                            data-region-link="<?= htmlspecialchars($region, ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <?= htmlspecialchars($region, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <h3>
                    Région sélectionnée :
                    <?= $selectedRegion !== '' ? htmlspecialchars($selectedRegion, ENT_QUOTES, 'UTF-8') : '—' ?>
                </h3>

                <div class="products-grid" id="products-by-region">
                    <?php foreach ($products as $product): ?>
                        <article
                            class="product-card <?= $product['exception'] ? 'is-exception' : '' ?>"
                            data-product-region="<?= htmlspecialchars($selectedRegion, ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <h3><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="product-meta">
                                <?= htmlspecialchars($product['type'], ENT_QUOTES, 'UTF-8') ?> •
                                <?= htmlspecialchars($product['volume'], ENT_QUOTES, 'UTF-8') ?> •
                                <?= htmlspecialchars($product['abv'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p><strong>Région :</strong> <?= htmlspecialchars($product['region'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>Cépage :</strong> <?= htmlspecialchars($product['grape'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p><?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?></p>

                            <?php if ($isProfessional): ?>
                                <p class="product-price">Tarif pro HT : €<?= number_format((float)$product['price'], 2) ?></p>
                            <?php else: ?>
                                <p class="product-price locked">Prix réservé aux professionnels</p>
                            <?php endif; ?>

                            <?php if ($isProfessional): ?>
                                <form method="post" class="add-form">
                                    <input type="hidden" name="action" value="add_to_cart">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="region" value="<?= htmlspecialchars($selectedRegion, ENT_QUOTES, 'UTF-8') ?>">
                                    <label>
                                        Qté
                                        <input type="number" name="qty" min="1" value="1">
                                    </label>
                                    <button type="submit">Ajouter à la sélection</button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <p id="no-products-message" style="display:<?= count($products) === 0 ? 'block' : 'none' ?>;">
                    Aucun produit disponible pour cette région.
                </p>
            </div>
        </div>
    </section>
</main>

<script>
(() => {
  const mapObject = document.getElementById('france-map-object');
  const regionInput = document.getElementById('selected-region-input');
  const submitBtn = document.getElementById('region-submit-btn');
  const regionLinks = document.querySelectorAll('[data-region-link]');
  const productCards = document.querySelectorAll('[data-product-region]');
  const noResults = document.getElementById('no-products-message');

  const normalize = (value) =>
    (value || '')
      .toString()
      .trim()
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');

  const regionAliases = {
    champagne: ['champagne', 'grand est', 'marne', 'aube', 'haute-marne', 'ardennes', '51', '10', '52', '08'],
    bourgogne: ['bourgogne', 'bourgogne-franche-comte', 'cote-dor', 'yonne', 'saone-et-loire', 'nievre', '21', '89', '71', '58'],
    bordeaux: ['bordeaux', 'nouvelle-aquitaine', 'gironde', 'dordogne', 'lot-et-garonne', '33', '24', '47'],
    spiritueux: ['spiritueux', 'cognac', 'charente', 'charente-maritime', '16', '17']
  };

  const departmentToRegion = {
    '08': 'champagne', ardennes: 'champagne',
    '10': 'champagne', aube: 'champagne',
    '51': 'champagne', marne: 'champagne',
    '52': 'champagne', 'haute-marne': 'champagne',

    '21': 'bourgogne', 'cote-dor': 'bourgogne',
    '58': 'bourgogne', nievre: 'bourgogne',
    '71': 'bourgogne', 'saone-et-loire': 'bourgogne',
    '89': 'bourgogne', yonne: 'bourgogne',

    '24': 'bordeaux', dordogne: 'bordeaux',
    '33': 'bordeaux', gironde: 'bordeaux',
    '47': 'bordeaux', 'lot-et-garonne': 'bordeaux',

    '16': 'spiritueux', charente: 'spiritueux',
    '17': 'spiritueux', 'charente-maritime': 'spiritueux'
  };

  const displayNames = {
    champagne: 'Champagne',
    bourgogne: 'Bourgogne',
    bordeaux: 'Bordeaux',
    spiritueux: 'Spiritueux'
  };

  const matchRegion = (rawName) => {
    const key = normalize(rawName);

    if (departmentToRegion[key]) return departmentToRegion[key];
    if (departmentToRegion[key.replace(/^d/, '')]) return departmentToRegion[key.replace(/^d/, '')];

    for (const [target, aliases] of Object.entries(regionAliases)) {
      if (aliases.includes(key)) return target;
    }
    return null;
  };

  const updateListFilter = (selected) => {
    let visibleCount = 0;
    productCards.forEach((card) => {
      const cardRegion = normalize(card.getAttribute('data-product-region'));
      const visible = selected === '' || cardRegion === selected;
      card.style.display = visible ? '' : 'none';
      if (visible) visibleCount += 1;
    });

    if (noResults) {
      noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    regionLinks.forEach((link) => {
      const linkRegion = normalize(link.getAttribute('data-region-link'));
      link.classList.toggle('is-active', linkRegion === selected);
    });
  };

  const activateRegion = (regionName) => {
    const normalized = normalize(regionName);
    if (!normalized || !regionInput) return;
    regionInput.value = regionName;
    updateListFilter(normalized);
    if (submitBtn) submitBtn.click();
  };

  const bindSvgInteractions = (svgDoc) => {
    if (!svgDoc) return;

    const svgRoot = svgDoc.documentElement;
    if (svgRoot) {
      svgRoot.setAttribute('preserveAspectRatio', 'xMidYMid meet');
      svgRoot.style.width = '100%';
      svgRoot.style.height = '100%';
      svgRoot.style.display = 'block';
    }

    const style = document.createElementNS('http://www.w3.org/2000/svg', 'style');
    style.textContent = `
      path[data-wine-region], g[data-wine-region], path.wine-clickable, g.wine-clickable {
        cursor: pointer;
        transition: fill .2s ease, opacity .2s ease, stroke .2s ease;
      }
      path[data-wine-region]:hover, g[data-wine-region]:hover,
      path.wine-clickable:hover, g.wine-clickable:hover {
        fill: #d8c3ab !important;
        stroke: #6e4530 !important;
      }
      .wine-active {
        fill: #6e4530 !important;
        stroke: #4c2f1f !important;
      }
    `;
    svgDoc.documentElement.appendChild(style);

    const candidates = svgDoc.querySelectorAll('path, g');
    candidates.forEach((zone) => {
      const raw =
        zone.getAttribute('data-name') ||
        zone.getAttribute('inkscape:label') ||
        zone.getAttribute('title') ||
        zone.getAttribute('id') ||
        '';

      const mapped = matchRegion(raw);
      if (!mapped) return;

      zone.setAttribute('data-wine-region', mapped);
      zone.classList.add('wine-clickable');

      zone.addEventListener('click', (e) => {
        e.preventDefault();
        const selectedName = displayNames[mapped] || mapped;
        activateRegion(selectedName);

        svgDoc.querySelectorAll('.wine-active').forEach((el) => el.classList.remove('wine-active'));
        zone.classList.add('wine-active');
      });
    });
  };

  const params = new URLSearchParams(window.location.search);
  const current = normalize(params.get('region') || '');
  if (current) {
    updateListFilter(current);
  }

  if (mapObject) {
    mapObject.addEventListener('load', () => {
      const svgDoc = mapObject.contentDocument;
      bindSvgInteractions(svgDoc);
    });
  }

  regionLinks.forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const value = link.getAttribute('data-region-link') || '';
      activateRegion(value);
    });
  });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
