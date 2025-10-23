<?php
// mostrar.php

// Muestra errores de PHP (útil para depurar, quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Aseguramos la ruta del archivo de conexión db.php (debe estar en la subcarpeta carrito)
// Aquí se crea la conexión $pdo
require __DIR__ . '/carrito/db.php'; 

$producto = null;
$opiniones = []; 
$error = null;
$producto_id = null;
$categoria_nombre = '';
$descuento_porcentaje = 0;

// 1. Obtener y validar el ID del producto desde la URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $producto_id = intval($_GET['id']);
} else {
    $error = "ID de producto inválido o no proporcionado.";
}

// 2. Si el ID es válido, buscar el producto y sus opiniones
if ($producto_id !== null) {
    // --- Obtener datos del producto ---
    $sql_producto = "SELECT p.*, c.Nombre_Categoria
                     FROM producto p
                     LEFT JOIN categoria c ON p.Id_Categoria = c.Id_Categoria
                     WHERE p.Id_Producto = ?";
                     
    // *** CAMBIO: Usar $pdo->prepare() ***
    $stmt_producto = $pdo->prepare($sql_producto);

    if ($stmt_producto) {
        // Vinculamos el ID y ejecutamos la consulta
        // Utilizamos PDO::PARAM_INT para asegurar el tipo de dato
        $stmt_producto->bindParam(1, $producto_id, PDO::PARAM_INT);
        $stmt_producto->execute();
        
        // Obtenemos el único resultado (fetch)
        $producto = $stmt_producto->fetch();

        if ($producto) {
            $categoria_nombre = $producto['Nombre_Categoria'] ?? 'Sin categoría';

            // Calcular descuento
            if (!empty($producto['precio_anterior']) && $producto['precio_anterior'] > 0 && $producto['precio_anterior'] > $producto['precio_actual']) {
                 $descuento = $producto['precio_anterior'] - $producto['precio_actual'];
                 $descuento_porcentaje = round(($descuento / $producto['precio_anterior']) * 100);
            }

            // --- Si el producto existe, buscar sus opiniones ---
            $sql_opiniones = "SELECT Nombre_Usuario, Calificacion, Comentario, Fecha_Opinion
                              FROM producto_opiniones
                              WHERE Id_Producto = ?
                              ORDER BY Fecha_Opinion DESC"; 
            
            // *** CAMBIO: Usar $pdo->prepare() ***
            $stmt_opiniones = $pdo->prepare($sql_opiniones);
            
            if ($stmt_opiniones) {
                // Vinculamos el ID y ejecutamos la consulta
                $stmt_opiniones->bindParam(1, $producto_id, PDO::PARAM_INT);
                $stmt_opiniones->execute();
                
                // Obtenemos todos los resultados (fetchAll)
                $opiniones = $stmt_opiniones->fetchAll();
                
            } else {
                // error_log es para escribir en el log de PHP, útil para errores silenciosos
                error_log("Error al preparar consulta de opiniones: " . $pdo->errorInfo()[2]);
            }

        } else {
            $error = "Producto no encontrado.";
        }
    } else {
        error_log("Error al preparar la consulta de producto: " . $pdo->errorInfo()[2]);
        $error = "Ocurrió un error al buscar el producto.";
    }
}

// Nota: Con PDO, no es necesario cerrar la conexión ($pdo->close()) al final del script.
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $producto ? htmlspecialchars($producto['Nombre_Producto']) : 'Producto no encontrado' ?> - Mi Supermercado</title>
    <link rel="stylesheet" href="style.css"> 
    <style>
        /* Estilos básicos para la lupa, asumiendo que no están en style.css */
        .imagen-lupa-contenedor {
            position: relative;
            overflow: hidden; /* Oculta el zoom fuera del contenedor */
            cursor: crosshair;
            display: inline-block;
        }
        .lupa-zoom-area {
            position: absolute;
            top: 0;
            left: 100%; /* Inicia fuera de la imagen */
            width: 250px; /* Tamaño del área de la lupa */
            height: 250px;
            border: 1px solid #ccc;
            background-repeat: no-repeat;
            pointer-events: none; /* Asegura que no interfiera con eventos del ratón */
            opacity: 0;
            transition: opacity 0.2s;
            transform: translate(20px, 0); /* Mueve el área de zoom a la derecha */
            z-index: 10;
        }
        /* Estilos para el contenido de producto y opiniones */
        .producto-principal { display: flex; gap: 30px; margin-bottom: 30px; }
        .producto-imagen { flex-shrink: 0; }
        .producto-detalles { flex-grow: 1; }
        .producto-precio { margin: 10px 0; }
        .precio-anterior { text-decoration: line-through; color: #888; margin-right: 10px; }
        .precio-actual { font-size: 1.5em; color: #d9534f; font-weight: bold; }
        .descuento { background: #d9534f; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.9em; margin-left: 10px; }
        .etiqueta { padding: 3px 6px; border-radius: 3px; font-size: 0.8em; font-weight: bold; margin-right: 5px; color: white; }
        .destacado { background-color: #5cb85c; }
        .especial { background-color: #f0ad4e; }
        .producto-acciones button { padding: 10px 20px; margin-right: 10px; cursor: pointer; }
        .boton-carrito { background-color: #337ab7; color: white; border: none; }
        .boton-comprar { background-color: #5cb85c; color: white; border: none; }
        .opinion { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; }
        .opinion-rating { color: gold; font-size: 1.2em; }
        .opinion-meta { font-size: 0.9em; color: #555; margin-left: 10px; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>

    <div class="container">
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php elseif ($producto): ?>
            <div class="producto-principal">
                <div class="producto-imagen">
                    <div class="imagen-lupa-contenedor">
                        <img id="imagen-principal"
                             src="<?= htmlspecialchars($producto['imagen_url'] ?: 'https://via.placeholder.com/400x400') ?>"
                             alt="<?= htmlspecialchars($producto['Nombre_Producto']) ?>"
                             data-zoom-src="<?= htmlspecialchars($producto['imagen_url'] ?: 'https://via.placeholder.com/800x800') /* URL para el zoom */ ?>">
                        <div id="lupa-zoom" class="lupa-zoom-area"></div>
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

                        <?php if ($descuento_porcentaje > 0): ?>
                             <span class="descuento">
                                 <?= $producto['descuento_texto'] ? htmlspecialchars($producto['descuento_texto']) : $descuento_porcentaje . '% OFF' ?>
                             </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($producto['es_destacado'] == 1): ?>
                        <span class="etiqueta destacado">DESTACADO</span>
                    <?php endif; ?>
                    <?php if (!empty($producto['etiqueta_especial'])): ?>
                        <span class="etiqueta especial"><?= htmlspecialchars($producto['etiqueta_especial']) ?></span>
                    <?php endif; ?>

                     <div class="producto-stock">
                         <?= ($producto['Stock'] > 0) ? 'Stock disponible' : 'Sin stock' ?>
                     </div>

                    <div class="producto-envio">
                        <p><span class="envio-gratis">Llega gratis mañana</span></p>
                        <a href="#">Más detalles y formas de entrega</a>
                    </div>

                    <div class="producto-cantidad">
                        <label for="cantidad">Cantidad:</label>
                        <select name="cantidad" id="cantidad" <?= ($producto['Stock'] <= 0) ? 'disabled' : '' ?> >
                            <option value="1">1 unidad</option>
                            <?php if ($producto['Stock'] > 1): ?>
                                <?php
                                $max_cantidad = min(10, $producto['Stock']);
                                ?>
                                <?php for ($i = 2; $i <= $max_cantidad; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?> unidades</option>
                                <?php endfor; ?>
                            <?php endif; ?>
                        </select>
                        <?php if ($producto['Stock'] > 0): ?>
                           <span class="disponible">(<?= $producto['Stock'] ?> disponibles)</span>
                        <?php endif; ?>
                    </div>

                    <div class="producto-acciones">
                         <?php if ($producto['Stock'] > 0): ?>
                             <button class="boton-comprar">Comprar ahora</button>
                             <button class="boton-carrito">Agregar al carrito</button>
                         <?php else: ?>
                             <button class="boton-sin-stock" disabled>Sin stock</button>
                         <?php endif; ?>
                    </div>

                </div>
            </div>

            <div class="producto-descripcion">
                <h2>Descripción</h2>
                <p><?= nl2br(htmlspecialchars($producto['Descripcion'])) ?></p>
            </div>

            <div class="producto-opiniones">
                 <h2>Opiniones del producto</h2>
                 <?php if (!empty($opiniones)): ?>
                     <?php foreach ($opiniones as $opinion): ?>
                         <div class="opinion">
                             <div class="opinion-header">
                                 <span class="opinion-rating">
                                     <?php // Asegurarse que Calificacion sea un número entre 0 y 5
                                     $calif = max(0, min(5, $opinion['Calificacion']));
                                     ?>
                                     <?php for($i = 1; $i <= 5; $i++): ?>
                                         <?= ($i <= $calif) ? '★' : '☆' // ★ ☆ Estrellas llenas y vacías ?>
                                     <?php endfor; ?>
                                 </span>
                                 <span class="opinion-meta">
                                     Por <?= htmlspecialchars($opinion['Nombre_Usuario']) ?> -
                                     <?= date('d/m/Y', strtotime($opinion['Fecha_Opinion'])) // Formatea la fecha ?>
                                 </span>
                             </div>
                             <?php if (!empty($opinion['Comentario'])): // Mostrar solo si hay comentario ?>
                                 <p class="opinion-cuerpo"><?= nl2br(htmlspecialchars($opinion['Comentario'])) ?></p>
                             <?php endif; ?>
                         </div>
                     <?php endforeach; ?>
                 <?php else: ?>
                     <p>Este producto todavía no tiene opiniones.</p>
                 <?php endif; ?>
             </div>
        <?php else: ?>
           <p class="error">El producto solicitado no existe o no está disponible.</p>
        <?php endif; ?>
    </div><script>
        // --- Script de la Lupa (sin cambios) ---
        const contenedor = document.querySelector('.imagen-lupa-contenedor');
        const imgPrincipal = document.getElementById('imagen-principal');
        const lupa = document.getElementById('lupa-zoom');

        if (contenedor && imgPrincipal && lupa) {
            const zoomSrc = imgPrincipal.getAttribute('data-zoom-src');
            lupa.style.backgroundImage = `url('${zoomSrc}')`;
            const factorZoom = 2; // Puedes ajustar esto (ej: 1.5, 2.5)

            const actualizarTamanioFondo = () => {
                if (imgPrincipal.naturalWidth > 0 && imgPrincipal.naturalHeight > 0) {
                     lupa.style.backgroundSize = `${imgPrincipal.naturalWidth * factorZoom}px ${imgPrincipal.naturalHeight * factorZoom}px`;
                } else {
                    setTimeout(actualizarTamanioFondo, 100);
                }
            };

            const moverLupa = (e) => {
                const rect = imgPrincipal.getBoundingClientRect();
                let x = e.clientX - rect.left;
                let y = e.clientY - rect.top;
                x = Math.max(0, Math.min(x, rect.width));
                y = Math.max(0, Math.min(y, rect.height));

                 // Corregido el cálculo para centrar el zoom en el cursor
                 const bgPosX = -(x * factorZoom - lupa.offsetWidth / 2);
                 const bgPosY = -(y * factorZoom - lupa.offsetHeight / 2);

                lupa.style.backgroundPosition = `${bgPosX}px ${bgPosY}px`;
            };

            const mostrarLupa = () => {
                actualizarTamanioFondo(); // Asegura tamaño correcto al mostrar
                lupa.style.opacity = '1';
                contenedor.addEventListener('mousemove', moverLupa); // Activar movimiento solo cuando está visible
            };

            const ocultarLupa = () => {
                lupa.style.opacity = '0';
                contenedor.removeEventListener('mousemove', moverLupa); // Desactivar para optimizar
            };

            // Event Listeners
            contenedor.addEventListener('mouseenter', mostrarLupa);
            contenedor.addEventListener('mouseleave', ocultarLupa);

            // Carga inicial
            if (imgPrincipal.complete && imgPrincipal.naturalWidth > 0) {
                 actualizarTamanioFondo();
            } else {
                imgPrincipal.addEventListener('load', actualizarTamanioFondo);
            }
        }
    </script>

</body>
</html>