/**
 * Menu de navigation mobile (burger).
 * Script classique, sans dépendance : fonctionne même si Three.js
 * ou l'importmap ne sont pas supportés (iOS < 16.4, WebView, etc.).
 */
(function () {
  'use strict';

  var toggle = document.getElementById('nav-toggle');
  var nav = document.getElementById('primary-nav');
  if (!toggle || !nav) return;

  var navLinks = nav.querySelectorAll('a');
  var mq = window.matchMedia('(max-width: 992px)');

  var isOpen = function () {
    return nav.classList.contains('is-open');
  };

  var setState = function (open) {
    nav.classList.toggle('is-open', open);
    toggle.classList.toggle('is-active', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
  };

  var close = function () {
    if (isOpen()) setState(false);
  };

  toggle.addEventListener('click', function (event) {
    event.preventDefault();
    setState(!isOpen());
  });

  // Referme le menu après un clic sur un lien.
  for (var i = 0; i < navLinks.length; i++) {
    navLinks[i].addEventListener('click', close);
  }

  // Fermeture au clavier et au clic en dehors du menu.
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' || event.key === 'Esc') close();
  });

  document.addEventListener('click', function (event) {
    if (!isOpen()) return;
    if (nav.contains(event.target) || toggle.contains(event.target)) return;
    close();
  });

  // Repasse en navigation desktop : on nettoie l'état.
  var onBreakpointChange = function (event) {
    if (!event.matches) close();
  };
  if (typeof mq.addEventListener === 'function') {
    mq.addEventListener('change', onBreakpointChange);
  } else if (typeof mq.addListener === 'function') {
    mq.addListener(onBreakpointChange);
  }

  // iOS / Android : si la page est restaurée depuis le cache (bfcache),
  // on ne garde pas un menu ouvert dans un état incohérent.
  window.addEventListener('pageshow', close);
})();
