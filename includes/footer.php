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
    "three": "./node_modules/three/build/three.module.js"
  }
}
</script>
<script type="module" src="assets/js/main.js"></script>
<?php endif; ?>
</body>
</html>
