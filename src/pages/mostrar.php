<?php
// mostrar.php - Página de detalle de producto LIMPIA

// Muestra errores de PHP (útil para depurar, quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuración de base de datos
require __DIR__ . '/../../config/database.php';

$producto = null;
$opiniones = [];
$error = null;
$producto_id = null;
$categoria_nombre = '';
$imagenes_producto = [];

// 1. Verificar si se ha enviado un ID de producto
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $producto_id = (int)$_GET['id'];
} else {
    $error = "ID de producto no válido.";
}

// 2. Si el ID es válido, buscar el producto y sus opiniones
if ($producto_id !== null) {
    try {
        // --- Obtener datos del producto con imagen principal ---
        $sql_producto = "SELECT p.*, c.Nombre_Categoria, pi.url_imagen as imagen_principal
                         FROM producto p
                         LEFT JOIN categoria c ON p.Id_Categoria = c.Id_Categoria
                         LEFT JOIN producto_imagenes pi ON p.Id_Producto = pi.Id_Producto AND pi.orden = 1
                         WHERE p.Id_Producto = ?";

        $stmt_producto = $pdo->prepare($sql_producto);

        if ($stmt_producto) {
            $stmt_producto->bindParam(1, $producto_id, PDO::PARAM_INT);
            $stmt_producto->execute();

            $producto = $stmt_producto->fetch();

            if ($producto) {
                $categoria_nombre = $producto['Nombre_Categoria'] ?? 'Sin categoría';

                // Obtener todas las imágenes del producto
                $sql_imagenes = "SELECT url_imagen, orden FROM producto_imagenes WHERE Id_Producto = ? ORDER BY orden";
                $stmt_imagenes = $pdo->prepare($sql_imagenes);
                $stmt_imagenes->execute([$producto_id]);
                $imagenes_producto = $stmt_imagenes->fetchAll();

                // Calcular descuento
                if (!empty($producto['precio_anterior']) && $producto['precio_anterior'] > 0 && $producto['precio_anterior'] > $producto['precio_actual']) {
                    $descuento = $producto['precio_anterior'] - $producto['precio_actual'];
                    $descuento_porcentaje = round(($descuento / $producto['precio_anterior']) * 100);
                }

                // --- Buscar opiniones del producto ---
                $sql_opiniones = "SELECT u.nombre_usuario as Nombre_Usuario, po.Calificacion, po.Comentario, po.Fecha_Opinion
                                  FROM producto_opiniones po
                                  INNER JOIN usuario u ON po.id_usuario = u.id_usuario
                                  WHERE po.Id_Producto = ?
                                  ORDER BY po.Fecha_Opinion DESC";

                $stmt_opiniones = $pdo->prepare($sql_opiniones);
                $stmt_opiniones->execute([$producto_id]);
                $opiniones = $stmt_opiniones->fetchAll();

            } else {
                $error = "Producto no encontrado.";
            }
        } else {
            $error = "Error en la consulta del producto.";
        }
    } catch (PDOException $e) {
        $error = "Error de base de datos: " . $e->getMessage();
    }
}

// Configurar página
$page_title = $producto ? htmlspecialchars($producto['Nombre_Producto']) . ' - Supermercado Online' : 'Producto no encontrado - Supermercado Online';

// Estilos adicionales específicos para esta página
$additional_styles = '<link rel="stylesheet" href="assets/css/mostrar-producto.css">';

// Scripts básicos
$additional_scripts = '<script>console.log("Página mostrar.php cargada");</script>';

// Configurar rutas para el header
$base_path = '';

// Incluir el header
include '../components/header.php';
?> 

<?php if ($error): ?>
    <div style="text-align: center; padding: 40px;">
        <h2>Error</h2>
        <p class="error"><?= htmlspecialchars($error) ?></p>
        <a href="<?= $base_path ?>">Volver al inicio</a>
    </div>
<?php elseif ($producto): ?>
    <div class="producto-principal">
        <div class="producto-imagen">
            <div class="imagen-carrusel-container">
                <img id="imagen-principal"
                    src="<?= htmlspecialchars($producto['imagen_principal'] ?: 'https://via.placeholder.com/400x400') ?>"
                    alt="<?= htmlspecialchars($producto['Nombre_Producto']) ?>">
                
                <?php if (!empty($imagenes_producto) && count($imagenes_producto) > 1): ?>
                <div class="imagen-indicators">
                    <?php foreach ($imagenes_producto as $index => $imagen): ?>
                        <span class="imagen-indicator <?= $index === 0 ? 'active' : '' ?>"></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="producto-detalles">
            <div class="producto-categoria">
                <?= htmlspecialchars($categoria_nombre) ?>
            </div>

            <h1><?= htmlspecialchars($producto['Nombre_Producto']) ?></h1>

            <div class="producto-precio">
                <?php if ($producto['precio_anterior'] && $producto['precio_anterior'] > $producto['precio_actual']): ?>
                    <s class="precio-anterior">$<?= number_format((float)$producto['precio_anterior'], 2, ',', '.') ?></s>
                <?php endif; ?>
                <span class="precio-actual">$<?= number_format((float)$producto['precio_actual'], 2, ',', '.') ?></span>
            </div>

            <div class="producto-stock">
                <?php if ($producto['Stock'] > 0): ?>
                    <span class="stock-disponible">✓ Disponible (<?= $producto['Stock'] ?> en stock)</span>
                <?php else: ?>
                    <span class="stock-agotado">✗ Producto agotado</span>
                <?php endif; ?>
            </div>

            <div class="producto-acciones">
                <div class="producto" data-id="<?= $producto['Id_Producto'] ?>" data-nombre="<?= htmlspecialchars($producto['Nombre_Producto']) ?>">
                    <button class="boton-agregar"
                            <?= ($producto['Stock'] <= 0) ? 'disabled' : '' ?>>
                        <?= ($producto['Stock'] > 0) ? 'Agregar al Carrito' : 'Sin Stock' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="producto-descripcion">
        <h2>Descripción</h2>
        <p><?= nl2br(htmlspecialchars($producto['Descripcion'] ?? 'Sin descripción disponible.')) ?></p>
    </div>

    <div class="producto-opiniones">
        <h2>Opiniones de clientes</h2>
        <?php if (!empty($opiniones)): ?>
            <?php foreach ($opiniones as $opinion): ?>
                <div class="opinion">
                    <div class="opinion-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?= ($i <= $opinion['Calificacion']) ? '★' : '☆' ?>
                        <?php endfor; ?>
                    </div>
                    <p><strong><?= htmlspecialchars($opinion['Nombre_Usuario']) ?></strong></p>
                    <p><?= nl2br(htmlspecialchars($opinion['Comentario'])) ?></p>
                    <p><small><?= htmlspecialchars($opinion['Fecha_Opinion']) ?></small></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay opiniones para este producto aún.</p>
        <?php endif; ?>
    </div>

<?php else: ?>
    <div style="text-align: center; padding: 40px;">
        <h2>Producto no encontrado</h2>
        <p>El producto que buscas no existe o ha sido eliminado.</p>
        <a href="<?= $base_path ?>">Volver al inicio</a>
    </div>
<?php endif; ?>

<?php include '../components/footer.php'; ?>