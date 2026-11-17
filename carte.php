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

$products = $catalog[$selectedRegion] ?? [];
$isProfessional = true;

$svgMapContent = '';
$svgPath = __DIR__ . '/assets/gemini-svg.svg';
if (is_file($svgPath) && is_readable($svgPath)) {
    $svgMapContent = (string)file_get_contents($svgPath);

    if ($svgMapContent !== '') {
        $svgMapContent = preg_replace('/<\?xml[^>]*\?>/i', '', $svgMapContent) ?? $svgMapContent;
        $svgMapContent = preg_replace('/<!--.*?-->/s', '', $svgMapContent) ?? $svgMapContent;
        $svgMapContent = preg_replace('/\s(?:width|height)="[^"]*"/i', '', $svgMapContent) ?? $svgMapContent;
        $svgMapContent = preg_replace('/\sstyle="[^"]*"/i', '', $svgMapContent) ?? $svgMapContent;
        $svgMapContent = preg_replace('/\sviewBox="[^"]*"/i', '', $svgMapContent) ?? $svgMapContent;

        $svgMapContent = preg_replace(
            '/<svg\b/i',
            '<svg id="france-map-svg" width="100%" height="auto" preserveAspectRatio="xMidYMid meet" role="img"',
            $svgMapContent,
            1
        ) ?? $svgMapContent;
    }
}
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
                <div id="france-map-inline" aria-label="Carte interactive de la France métropolitaine">
                    <?= $svgMapContent ?>
                </div>
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
                    <span id="selected-region-name"><?= $selectedRegion !== '' ? htmlspecialchars($selectedRegion, ENT_QUOTES, 'UTF-8') : '—' ?></span>
                </h3>

                <p id="no-products-message" style="display: none; text-align: center; margin-top: 2rem; font-style: italic;">
                    Pas de vin en ce moment dans cette région.
                </p>

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
            </div>
        </div>
    </section>
</main>

<script>
(() => {
  const inlineMap = document.getElementById('france-map-inline');
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
    bordeaux: ['bordeaux'],
    bourgogne: ['bourgogne'],
    champagne: ['champagne'],
    alsace: ['alsace'],
    lorraine: ['lorraine'],
    jura: ['jura'],
    savoie: ['savoie'],
    beaujolais: ['beaujolais'],
    rhone: ['vallee-du-rhone', 'rhone', 'vallee du rhone'],
    loire: ['val-de-loire', 'loire', 'vallee de la loire'],
    sud_ouest: ['sud-ouest'],
    languedoc: ['languedoc-roussillon', 'languedoc'],
    provence: ['provence'],
    corse: ['corse'],
    spiritueux: ['spiritueux', 'cognac']
  };

  const displayNames = {
    bordeaux: 'Bordeaux',
    bourgogne: 'Bourgogne',
    champagne: 'Champagne',
    alsace: 'Alsace',
    lorraine: 'Lorraine',
    jura: 'Jura',
    savoie: 'Savoie',
    beaujolais: 'Beaujolais',
    rhone: 'Vallée du Rhône',
    loire: 'Vallée de la Loire',
    sud_ouest: 'Sud-Ouest',
    languedoc: 'Languedoc-Roussillon',
    provence: 'Provence',
    corse: 'Corse',
    spiritueux: 'Spiritueux'
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
    const titleSpan = document.getElementById('selected-region-name');
    if (titleSpan) titleSpan.textContent = displayNames[normalized] || regionName;
    if (submitBtn) submitBtn.click();
  };

  const bindSvgInteractions = (svgRoot) => {
    if (!svgRoot) return;

    const bbox = svgRoot.getBBox();
    svgRoot.setAttribute('viewBox', `${bbox.x} ${bbox.y} ${bbox.width} ${bbox.height}`);

    svgRoot.setAttribute('preserveAspectRatio', 'xMidYMid meet');
    svgRoot.setAttribute('width', '100%');
    svgRoot.setAttribute('height', 'auto');
    svgRoot.style.width = '100%';
    svgRoot.style.height = 'auto';
    svgRoot.style.display = 'block';
    svgRoot.style.margin = '0 auto';
    svgRoot.style.maxWidth = '800px';

    if (inlineMap) {
      inlineMap.style.display = 'block';
      inlineMap.style.width = '100%';
      inlineMap.style.maxWidth = '800px';
      inlineMap.style.margin = '0 auto';
    }

    const style = document.createElementNS('http://www.w3.org/2000/svg', 'style');
    style.textContent = `
      path, g {
        pointer-events: none;
      }
      [data-wine-region] {
        pointer-events: auto;
        cursor: pointer;
        transition: fill .2s ease, opacity .2s ease, stroke .2s ease;
      }
      [data-wine-region]:hover {
        fill: #d8c3ab !important;
        stroke: #6e4530 !important;
      }
      .wine-active {
        fill: #6e4530 !important;
        stroke: #4c2f1f !important;
      }
    `;
    svgRoot.appendChild(style);

    const wineZones = svgRoot.querySelectorAll('.wine-zone');
    wineZones.forEach((zone) => {
      const raw =
        zone.getAttribute('data-name') ||
        zone.getAttribute('inkscape:label') ||
        zone.getAttribute('title') ||
        zone.getAttribute('id') ||
        '';

      const key = normalize(raw);
      let mapped = null;
      for (const [target, aliases] of Object.entries(regionAliases)) {
        if (aliases.includes(key)) {
          mapped = target;
          break;
        }
      }

      if (!mapped) return;

      zone.setAttribute('data-wine-region', mapped);
      zone.classList.add('wine-clickable');

      zone.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        svgRoot.querySelectorAll('.wine-active').forEach((el) => el.classList.remove('wine-active'));
        svgRoot.querySelectorAll(`[data-wine-region="${mapped}"]`).forEach((el) => el.classList.add('wine-active'));

        const selectedName = displayNames[mapped] || mapped;
        activateRegion(selectedName);
      });
    });
  };

  const params = new URLSearchParams(window.location.search);
  const current = normalize(params.get('region') || '');
  if (current) {
    updateListFilter(current);
  } else {
    updateListFilter('');
  }

  const inlineSvg = inlineMap ? inlineMap.querySelector('svg') : null;
  if (inlineSvg) {
    bindSvgInteractions(inlineSvg);
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
