<?php
// producto.php
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
    // Es mejor devolver un JSON o un HTML simple de error si esto se carga por AJAX
    echo "<div style='color:red;text-align:center'>Error de conexión a la Base de Datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>

<div class="carousel-track" id="carruselProductos">
    <?php if (empty($productos)): ?>
        <p style="text-align:center; color: #888; width: 100%;">No hay productos destacados disponibles.</p>
    <?php else: ?>
        <?php foreach ($productos as $producto): ?>
            <a href="mostrar.php?id=<?= $producto['id'] ?>" class="carrusel-slide-link" style="text-decoration: none; color: inherit;">
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
                                onclick="event.stopPropagation();" 
                                <?= ($producto['stock'] <= 0) ? 'disabled' : '' ?>>
                            <?= ($producto['stock'] > 0) ? 'Agregar' : 'Sin Stock' ?>
                        </button>
                    </div>
                </article>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>