<?php
// Asegura que solo se envíe HTML puro, sin headers de página
header('Content-Type: text/html; charset=utf-8');

// --- 1. CONFIGURACIÓN DE CONEXIÓN ---
$host = 'localhost';
$db   = 'Supermercado';
$user = 'root'; // Usuario por defecto de XAMPP
$pass = '';     // Contraseña por defecto de XAMPP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // --- 2. CONSULTA DE PRODUCTOS ---
    $stmt = $pdo->query("SELECT * FROM productos WHERE es_destacado = 1 ORDER BY id LIMIT 6");
    $productos = $stmt->fetchAll();

} catch (\PDOException $e) {
    // Si la conexión falla, se devuelve un mensaje de error claro al front-end
    echo '<div style="color:red;padding:20px;text-align:center;">
            Error al conectar o consultar la base de datos: ' . $e->getMessage() . 
         '</div>';
    exit;
}
?>

<div id="carruselProductos" class="carrusel-container">
    
    <button class="carrusel-btn prev"><i class="fas fa-chevron-left"></i></button>
    
    <div class="carousel-track">
    
        <?php foreach ($productos as $producto): ?>
            <article class="producto-card carrusel-slide">
                
                <?php if ($producto['etiqueta_especial'] == 'EXCLUSIVO ONLINE'): ?>
                    <span class="etiqueta-exclusiva"><?php echo htmlspecialchars($producto['etiqueta_especial']); ?></span>
                <?php elseif ($producto['etiqueta_especial'] == 'LARGA VIDA'): ?>
                    <span class="etiqueta-caracteristica-negra"><?php echo htmlspecialchars($producto['etiqueta_especial']); ?></span>
                <?php endif; ?>
                
                <button class="btn-favorito"><i class="far fa-heart"></i></button>
                <img src="<?php echo htmlspecialchars($producto['imagen_url']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="producto-imagen">
                
                <div class="producto-info">
                    
                    <?php if ($producto['precio_anterior'] && $producto['descripcion_corta']): ?>
                        <span class="etiqueta-descuento verde"><?php echo htmlspecialchars($producto['descripcion_corta']); ?></span>
                        <p class="precio-tachado">$<?php echo number_format($producto['precio_anterior'], 3, ',', '.'); ?></p>
                    <?php endif; ?>
                    
                    <h3 class="producto-nombre"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                    <p class="precio-final">$<?php echo number_format($producto['precio_actual'], 3, ',', '.'); ?></p>
                    <button class="boton-agregar rojo-vibrante" data-id="<?php echo $producto['id']; ?>">Agregar</button>
                </div>
            </article>
        <?php endforeach; ?>

    </div>
    
    <button class="carrusel-btn next"><i class="fas fa-chevron-right"></i></button>
</div>