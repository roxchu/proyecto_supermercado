<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$db   = 'supermercado';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $stmt = $pdo->query("
        SELECT p.Id_Producto AS id, p.Nombre_Producto AS nombre, p.Descripcion AS descripcion,
               p.imagen_url, p.precio_actual, p.precio_anterior, p.etiqueta_especial,
               p.descuento_texto, p.Stock AS stock, c.Nombre_Categoria AS categoria
        FROM producto p
        LEFT JOIN categoria c ON p.Id_Categoria = c.Id_Categoria
        WHERE p.es_destacado = 1
        ORDER BY p.Id_Producto
        LIMIT 12
    ");
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div style='color:red;text-align:center'>Error DB: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>

<div id="carruselProductos" class="carrusel-container">
    <button class="carrusel-btn prev"><i class="fas fa-chevron-left"></i></button>
    <div class="carousel-track">
        <?php foreach ($productos as $producto): ?>
            <article class="producto-card carrusel-slide">
                <?php if ($producto['etiqueta_especial']): ?>
                    <span class="etiqueta-caracteristica-verde"><?= htmlspecialchars($producto['etiqueta_especial']) ?></span>
                <?php endif; ?>

                <img src="<?= htmlspecialchars($producto['imagen_url']) ?>"
                     alt="<?= htmlspecialchars($producto['nombre']) ?>"
                     class="producto-imagen"
                     onerror="this.src='https://via.placeholder.com/250x160?text=Sin+Imagen'">

                <div class="producto-info">
                    <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                    <p class="precio-final">$<?= number_format($producto['precio_actual'], 2, ',', '.') ?></p>

                    <button class="boton-agregar"
                            data-id="<?= $producto['id'] ?>"
                            data-nombre="<?= htmlspecialchars($producto['nombre']) ?>"
                            data-precio="<?= $producto['precio_actual'] ?>"
                            <?= ($producto['stock'] <= 0) ? 'disabled' : '' ?>>
                        <?= ($producto['stock'] > 0) ? 'Agregar' : 'Sin Stock' ?>
                    </button>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <button class="carrusel-btn next"><i class="fas fa-chevron-right"></i></button>
</div>
