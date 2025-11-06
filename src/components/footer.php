    </main>

    <?php if (isset($additional_scripts_before)): ?>
        <?= $additional_scripts_before ?>
    <?php endif; ?>

    <script src="<?= isset($base_path) ? $base_path : '../../' ?>assets/js/legacy/script.js"></script>
    <script src="<?= isset($base_path) ? $base_path : '../../' ?>assets/js/carrito.js"></script>

    <?php if (isset($additional_scripts)): ?>
        <?= $additional_scripts ?>
    <?php endif; ?>
</body>
</html>