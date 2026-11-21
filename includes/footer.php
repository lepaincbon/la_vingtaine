<?php
declare(strict_types=1);

$loadThreeScene = $loadThreeScene ?? false;
?>
<footer class="footer">
    <p class="health-warning" role="note">
        <strong>L'abus d'alcool est dangereux pour la santé, à consommer avec modération.</strong>
    </p>
    <p>Les prix sont affichés à titre indicatif.</p>
    <p>
        <a href="https://www.instagram.com/_lavingtaine/" target="_blank" rel="noopener noreferrer">Instagram</a>
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
<script type="module" src="<?= htmlspecialchars(assetUrl('assets/js/main.js'), ENT_QUOTES, 'UTF-8') ?>" async></script>
<?php endif; ?>
</body>
</html>
