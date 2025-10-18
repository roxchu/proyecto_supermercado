<?php
// productos.php - Obtiene y muestra productos destacados
header('Content-Type: text/html; charset=utf-8');

// Configuración de conexión
$host = 'localhost';
$db   = 'supermercado';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Consulta de productos destacados
    $stmt = $pdo->query("
        SELECT 
            p.Id_Producto as id,
            p.Nombre_Producto as nombre,
            p.Descripcion as descripcion,
            p.imagen_url,
            p.precio_actual,
            p.precio_anterior,
            p.etiqueta_especial,
            p.descuento_texto,
            p.Stock as stock,
            c.Nombre_Categoria as categoria
        FROM producto p
        LEFT JOIN categoria c ON p.Id_Categoria = c.Id_Categoria
        WHERE p.es_destacado = 1 
        ORDER BY p.Id_Producto 
        LIMIT 12
    ");
    $productos = $stmt->fetchAll();

    if (empty($productos)) {
        echo '<div style="padding:40px;text-align:center;color:#666;">
                <i class="fas fa-box-open" style="font-size:3em;margin-bottom:10px;"></i>
                <p>No hay productos destacados disponibles.</p>
                <p style="font-size:0.9em;">Ejecuta el script SQL para agregar productos de ejemplo.</p>
              </div>';
        exit;
    }

} catch (PDOException $e) {
    echo '<div style="color:red;padding:20px;text-align:center;">
            <i class="fas fa-exclamation-triangle"></i> 
            Error al conectar con la base de datos: ' . htmlspecialchars($e->getMessage()) . 
         '</div>';
    exit;
}
?>

<div id="carruselProductos" class="carrusel-container">
    
    <button class="carrusel-btn prev"><i class="fas fa-chevron-left"></i></button>
    
    <div class="carousel-track">
    
        <?php foreach ($productos as $producto): ?>
            <article class="producto-card carrusel-slide">
                
                <?php if (!empty($producto['etiqueta_especial'])): ?>
                    <?php if ($producto['etiqueta_especial'] == 'EXCLUSIVO ONLINE'): ?>
                        <span class="etiqueta-exclusiva"><?php echo htmlspecialchars($producto['etiqueta_especial']); ?></span>
                    <?php elseif ($producto['etiqueta_especial'] == 'LARGA VIDA'): ?>
                        <span class="etiqueta-caracteristica-negra"><?php echo htmlspecialchars($producto['etiqueta_especial']); ?></span>
                    <?php else: ?>
                        <span class="etiqueta-caracteristica-verde"><?php echo htmlspecialchars($producto['etiqueta_especial']); ?></span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <button class="btn-favorito" title="Agregar a favoritos"><i class="far fa-heart"></i></button>
                
                <img src="<?php echo htmlspecialchars($producto['imagen_url']); ?>" 
                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                     class="producto-imagen"
                     onerror="this.src='https://via.placeholder.com/250x160?text=Sin+Imagen'">
                
                <div class="producto-info">
                    
                    <?php if (!empty($producto['descuento_texto']) && !empty($producto['precio_anterior'])): ?>
                        <span class="etiqueta-descuento"><?php echo htmlspecialchars($producto['descuento_texto']); ?></span>
                        <p class="precio-tachado">$<?php echo number_format($producto['precio_anterior'], 2, ',', '.'); ?></p>
                    <?php endif; ?>
                    
                    <h3 class="producto-nombre" title="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <?php echo htmlspecialchars($producto['nombre']); ?>
                    </h3>
                    
                    <p class="precio-final">$<?php echo number_format($producto['precio_actual'], 2, ',', '.'); ?></p>
                    
                    <button class="boton-agregar" 
                            data-id="<?php echo $producto['id']; ?>"
                            data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                            data-precio="<?php echo $producto['precio_actual']; ?>"
                            <?php echo ($producto['stock'] <= 0) ? 'disabled' : ''; ?>>
                        <?php echo ($producto['stock'] > 0) ? 'Agregar' : 'Sin Stock'; ?>
                    </button>
                </div>
            </article>
        <?php endforeach; ?>

    </div>
    
    <button class="carrusel-btn next"><i class="fas fa-chevron-right"></i></button>
</div>

<script>
// Script del carrusel (se ejecuta cuando se carga productos.php)
(function() {
    const track = document.querySelector('#carruselProductos .carousel-track');
    const prevBtn = document.querySelector('#carruselProductos .prev');
    const nextBtn = document.querySelector('#carruselProductos .next');
    const slides = document.querySelectorAll('#carruselProductos .carrusel-slide');
    
    if (!track || slides.length === 0) return;
    
    let currentIndex = 0;
    const slideWidth = 265; // 250px de ancho + 15px de gap
    const slidesToShow = 4;
    const maxIndex = Math.max(0, slides.length - slidesToShow);
    
    function updateCarousel() {
        const offset = -currentIndex * slideWidth;
        track.style.transform = `translateX(${offset}px)`;
        
        prevBtn.style.opacity = currentIndex === 0 ? '0.3' : '0.7';
        nextBtn.style.opacity = currentIndex >= maxIndex ? '0.3' : '0.7';
        prevBtn.style.cursor = currentIndex === 0 ? 'default' : 'pointer';
        nextBtn.style.cursor = currentIndex >= maxIndex ? 'default' : 'pointer';
    }
    
    prevBtn.addEventListener('click', () => {
        if (currentIndex > 0) {
            currentIndex--;
            updateCarousel();
        }
    });
    
    nextBtn.addEventListener('click', () => {
        if (currentIndex < maxIndex) {
            currentIndex++;
            updateCarousel();
        }
    });
    
    updateCarousel();
})();
</script>