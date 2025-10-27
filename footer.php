    </main>

    <?php if (isset($additional_scripts_before)): ?>
        <?= $additional_scripts_before ?>
    <?php endif; ?>

    <script src="<?= isset($base_path) ? $base_path : '' ?>script.js"></script>
    <script src="<?= isset($base_path) ? $base_path : '' ?>js/carrito.js"></script>

    <?php if (isset($additional_scripts)): ?>
        <?= $additional_scripts ?>
    <?php endif; ?>
</body>
</html>