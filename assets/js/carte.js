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
    cognac: ['cognac'],
    spiritueux: ['spiritueux']
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
    cognac: 'Cognac',
    spiritueux: 'Spiritueux'
  };

  const matchRegion = (rawName) => {
    const key = normalize(rawName);
    const entries = Object.entries(regionAliases);
    for (const [target, aliases] of entries) {
      if (aliases.includes(key)) return target;
    }
    return null;
  };

  const getCurrentRegion = () => {
    const params = new URLSearchParams(window.location.search);
    return normalize(params.get('region') || '');
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

    const root = svgDoc.documentElement;
    if (!root) return;

    const style = document.createElementNS('http://www.w3.org/2000/svg', 'style');
    style.textContent = `
      path, g {
        pointer-events: none;
      }
      [data-wine-region] {
        pointer-events: auto;
        cursor: pointer;
      }
    `;
    root.appendChild(style);

    const zones = root.querySelectorAll('.wine-zone');
    zones.forEach((zone) => {
      const raw =
        zone.getAttribute('data-name') ||
        zone.getAttribute('inkscape:label') ||
        zone.getAttribute('title') ||
        zone.getAttribute('id') ||
        '';

      const mapped = matchRegion(raw);
      if (!mapped) return;

      zone.setAttribute('data-wine-region', mapped);
      zone.style.cursor = 'pointer';
      zone.style.transition = 'opacity .2s ease, filter .2s ease';

      zone.addEventListener('mouseenter', () => {
        zone.style.opacity = '0.8';
        zone.style.filter = 'brightness(0.95)';
      });

      zone.addEventListener('mouseleave', () => {
        zone.style.opacity = '';
        zone.style.filter = '';
      });

      zone.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        root.querySelectorAll('.wine-active').forEach((el) => el.classList.remove('wine-active'));
        root.querySelectorAll(`[data-wine-region="${mapped}"]`).forEach((el) => el.classList.add('wine-active'));

        const selectedName = displayNames[mapped] || mapped;
        activateRegion(selectedName);
      });
    });
  };

  const initial = getCurrentRegion();
  if (initial) updateListFilter(initial);

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
