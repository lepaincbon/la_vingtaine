<?php
declare(strict_types=1);

$loadThreeScene = $loadThreeScene ?? false;
?>
<footer class="footer">
    <p>Livraison sous 3 à 5 jours ouvrés.</p>
    <p>
        <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer">Instagram</a>
        •
        <a href="cgu.php">CGU</a>
    </p>
    <p>© <?= date('Y') ?> La Vingtaine — B2B uniquement</p>
</footer>

<?php if ($loadThreeScene): ?>
<script type="importmap">
{
  "imports": {
    "three": "https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js"
  }
}
</script>
<script type="module" src="assets/js/main.js" async></script>
<?php endif; ?>
<script>
(() => {
  const toggle = document.getElementById('nav-toggle');
  const nav = document.querySelector('.nav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const isOpen = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      toggle.classList.toggle('is-active', isOpen);
    });
    nav.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.classList.remove('is-active');
      });
    });
  }
})();
</script>
</body>
</html>
