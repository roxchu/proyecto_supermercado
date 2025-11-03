<?php
// productos.php
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

    // --- Filtro por categoría (si se envía) ---
    $categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : null;

    if ($categoria) {
        $stmt = $pdo->prepare("
            SELECT p.Id_Producto AS id, p.Nombre_Producto AS nombre, p.Descripcion AS descripcion,
                   p.precio_actual, p.precio_anterior, p.etiqueta_especial,
                   p.descuento_texto, p.Stock AS stock, c.Nombre_Categoria AS categoria,
                   pi.url_imagen AS imagen_url
            FROM producto p
            LEFT JOIN categoria c ON p.Id_Categoria = c.Id_Categoria
            LEFT JOIN producto_imagenes pi ON p.Id_Producto = pi.Id_Producto AND pi.orden = 1
            WHERE c.Nombre_Categoria = ?
            ORDER BY p.Id_Producto
        ");
        $stmt->execute([$categoria]);
    } else {
        // Modo por defecto (productos destacados)
        $stmt = $pdo->query("
            SELECT p.Id_Producto AS id, p.Nombre_Producto AS nombre, p.Descripcion AS descripcion,
                   p.precio_actual, p.precio_anterior, p.etiqueta_especial,
                   p.descuento_texto, p.Stock AS stock, c.Nombre_Categoria AS categoria,
                   pi.url_imagen AS imagen_url
            FROM producto p
            LEFT JOIN categoria c ON p.Id_Categoria = c.Id_Categoria
            LEFT JOIN producto_imagenes pi ON p.Id_Producto = pi.Id_Producto AND pi.orden = 1
            WHERE p.es_destacado = 1
            ORDER BY p.Id_Producto
            LIMIT 12
        ");
    }

    $productos = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "<div style='color:red;text-align:center'>Error de conexión a la Base de Datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>

<div class="carousel-track" id="carruselProductos">
    <?php if (empty($productos)): ?>
        <p style="text-align:center; color: #888; width: 100%;">No hay productos en esta categoría.</p>
    <?php else: ?>
        <?php foreach ($productos as $producto): ?>
            <a href="mostrar.php?id=<?= $producto['id'] ?>" class="carrusel-slide-link" style="text-decoration: none; color: inherit;">
                <article class="producto-card carrusel-slide">
                    <?php if ($producto['etiqueta_especial']): ?>
                        <span class="etiqueta-caracteristica-verde"><?= htmlspecialchars($producto['etiqueta_especial']) ?></span>
                    <?php endif; ?>

                    <img src="<?= htmlspecialchars($producto['imagen_url'] ?: 'https://via.placeholder.com/250x160?text=Sin+Imagen') ?>"
                         alt="<?= htmlspecialchars($producto['nombre']) ?>"
                         class="producto-imagen"
                         onerror="this.src='https://via.placeholder.com/250x160?text=Sin+Imagen'">

                    <div class="producto-info">
                        <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                        <p class="precio-final">$<?= number_format($producto['precio_actual'], 2, ',', '.') ?></p>

                        <div class="producto" data-id="<?= $producto['id'] ?>" data-nombre="<?= htmlspecialchars($producto['nombre']) ?>">
                            <button class="boton-agregar"
                                    onclick="event.stopPropagation();" 
                                    <?= ($producto['stock'] <= 0) ? 'disabled' : '' ?>>
                                <?= ($producto['stock'] > 0) ? 'Agregar' : 'Sin Stock' ?>
                            </button>
                        </div>
                    </div>
                </article>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
