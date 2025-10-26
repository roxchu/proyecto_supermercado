<?php
// mostrar.php

// Muestra errores de PHP (útil para depurar, quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Aseguramos la ruta del archivo de conexión db.php (debe estar en la subcarpeta carrito)
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

    $stmt_producto = $pdo->prepare($sql_producto);

    if ($stmt_producto) {
        $stmt_producto->bindParam(1, $producto_id, PDO::PARAM_INT);
        $stmt_producto->execute();

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

            $stmt_opiniones = $pdo->prepare($sql_opiniones);

            if ($stmt_opiniones) {
                $stmt_opiniones->bindParam(1, $producto_id, PDO::PARAM_INT);
                $stmt_opiniones->execute();

                $opiniones = $stmt_opiniones->fetchAll();
            } else {
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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $producto ? htmlspecialchars($producto['Nombre_Producto']) : 'Producto no encontrado' ?> - Supermercado Online</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* Estilos específicos de la lupa y del layout del producto */
        .imagen-lupa-contenedor {
            position: relative;
            overflow: hidden;
            cursor: crosshair;
            display: inline-block;
        }

        .lupa-zoom-area {
            position: absolute;
            top: 0;
            left: 100%;
            width: 250px;
            height: 250px;
            border: 2px solid var(--color-azul-principal, #007bff);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            background-repeat: no-repeat;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
            transform: translate(20px, 0);
            z-index: 10;
        }

        .producto-imagen img {
            object-fit: contain;
            max-width: 370px;
            height: 370px;
            border: 1px solid #eee;
            border-radius: 4px;
        }

        /* Estilos base de la página de producto */
        .producto-principal {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
        }

        .producto-imagen {
            flex-shrink: 0;
        }

        .producto-detalles {
            flex-grow: 1;
        }

        .opinion-rating {
            color: gold;
            font-size: 1.2em;
        }

        .opinion {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <header class="header">

        <div class="header-left">
            <div class="logo">
                <a href="#" class="logo-link">
                    <h1>
                        <i class="fas fa-store"></i> Supermercado Online
                    </h1>
                </a>
            </div>

            <button class="menu-button" id="btn-categorias" aria-expanded="false">
                <i class="fas fa-bars"></i> Categorías
            </button>
        </div>

        <div class="search-bar">
            <input type="text" placeholder="Buscar productos..." aria-label="Buscar productos">
            <button type="button"><i class="fas fa-search"></i></button>
        </div>

        <div class="user-actions">
            <a href="#" id="link-gestion" class="employee-only" style="display:none;" title="Gestión"><i class="fas fa-truck-loading"></i></a>
            <a href="login/dashboard_admin.php" id="link-admin" class="admin-only" style="display:none;" title="Administración"><i class="fas fa-tools"></i></a>

            <a href="#" id="login-link" title="Iniciar sesión"><i class="fas fa-user"></i> Iniciar sesión</a>

            <div id="user-info" style="display:none;">
                <span id="user-greeting"></span>
                <a href="#" id="logout-link" title="Cerrar sesión"><i class="fas fa-sign-out-alt"></i></a>
            </div>

            <div class="cart" title="Mi Carrito">
                <i class="fas fa-shopping-cart"></i>
                <span id="cart-count">0</span>
            </div>
        </div>
    </header>

    <aside id="side-menu" class="side-menu" aria-hidden="true">

        <div class="side-menu-header">
            <h2><i class="fas fa-list-alt"></i> Todas las Categorías</h2>
            <button id="btn-close-menu" class="close-menu" aria-label="Cerrar Menú">&times;</button>
        </div>

        <nav class="side-nav">
            <ul>
                <li><a href="#" class="side-link"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="#" class="side-link"><i class="fas fa-tags"></i> Ofertas</a></li>
                <li><a href="#" class="side-link"><i class="fas fa-cocktail"></i> Bebidas</a></li>
                <li><a href="#" class="side-link"><i class="fas fa-soap"></i> Limpieza</a></li>
                <li><a href="#" class="side-link"><i class="fas fa-carrot"></i> Frutas y Verduras</a></li>
                <li><a href="#" class="side-link"><i class="fas fa-cookie-bite"></i> Panadería</a></li>
                <li><a href="#" class="side-link"><i class="fas fa-utensils"></i> Congelados</a></li>

                <li><a href="#" class="side-link employee-only" style="display:none;"><i class="fas fa-boxes"></i> Gestión de stock</a></li>
                <li><a href="#" class="side-link admin-only" style="display:none;"><i class="fas fa-cog"></i> Panel de admin</a></li>
            </ul>
        </nav>
    </aside>
    <div id="menu-overlay" class="overlay"></div>
    <main class="main-content container"> <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php elseif ($producto): ?>

            <div class="producto-principal">
                <div class="producto-imagen">
                    <div class="imagen-lupa-contenedor">
                        <img id="imagen-principal"
                            src="<?= htmlspecialchars($producto['imagen_url'] ?: 'https://via.placeholder.com/400x400') ?>"
                            alt="<?= htmlspecialchars($producto['Nombre_Producto']) ?>"
                            data-zoom-src="<?= htmlspecialchars($producto['imagen_url'] ?: 'https://via.placeholder.com/800x800') ?>">
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
                        <span class="<?= ($producto['Stock'] > 0) ? '' : 'agotado' ?>">
                            <?= ($producto['Stock'] > 0) ? 'Stock disponible' : 'Sin stock' ?>
                        </span>
                    </div>

                    <div class="producto-envio">
                        <p><span class="envio-gratis">Llega gratis mañana</span></p>
                    </div>


                    <div class="producto" data-id="<?= $producto['Id_Producto'] ?>" data-nombre="<?= $producto['Nombre_Producto'] ?>">
                        <div class="producto-cantidad">
                            <label for="cantidad">Cantidad:</label>
                            <select name="cantidad" id="cantidad" <?= ($producto['Stock'] <= 0) ? 'disabled' : '' ?>>
                                <option value="1">1 unidad</option>
                                <?php if ($producto['Stock'] > 1): ?>
                                    <?php $max_cantidad = min(10, $producto['Stock']); ?>
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
                                <button id="btn-agregar-carrito" class="boton-carrito">Agregar al carrito</button>
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
                                        <?php
                                                        $calif = max(0, min(5, $opinion['Calificacion']));
                                        ?>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?= ($i <= $calif) ? '★' : '☆' ?>
                                        <?php endfor; ?>
                                    </span>
                                    <span class="opinion-meta">
                                        Por <?= htmlspecialchars($opinion['Nombre_Usuario']) ?> -
                                        <?= date('d/m/Y', strtotime($opinion['Fecha_Opinion'])) ?>
                                    </span>
                                </div>
                                <?php if (!empty($opinion['Comentario'])): ?>
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
    </main>

    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="modal-title">Iniciar Sesión</h2>

            <form id="login-form-dni">
                <input type="text" id="dni" name="dni" placeholder="DNI" required />
                <button type="submit">Ingresar</button>
                <p id="login-message"></p>
                <p>¿No tienes cuenta? <a href="#" id="show-register">Registrate</a></p>
            </form>

            <form id="register-form" style="display:none;">
                <input type="text" id="reg-dni" name="dni" placeholder="DNI" required />
                <input type="text" id="nombre" name="nombre" placeholder="Nombre completo" required />
                <input type="email" id="correo" name="correo" placeholder="Correo electrónico" required />
                <button type="submit">Crear cuenta</button>
                <p id="register-message"></p>
                <p>¿Ya tienes cuenta? <a href="#" id="show-login">Inicia sesión</a></p>
            </form>
        </div>
    </div>

    <script>
        // --- Script de la Lupa (mantenido) ---
        const contenedor = document.querySelector('.imagen-lupa-contenedor');
        const imgPrincipal = document.getElementById('imagen-principal');
        const lupa = document.getElementById('lupa-zoom');

        if (contenedor && imgPrincipal && lupa) {
            const zoomSrc = imgPrincipal.getAttribute('data-zoom-src');
            lupa.style.backgroundImage = `url('${zoomSrc}')`;
            const factorZoom = 2;

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

                const bgPosX = -(x * factorZoom - lupa.offsetWidth / 2);
                const bgPosY = -(y * factorZoom - lupa.offsetHeight / 2);

                lupa.style.backgroundPosition = `${bgPosX}px ${bgPosY}px`;
            };

            const mostrarLupa = () => {
                actualizarTamanioFondo();
                lupa.style.opacity = '1';
                contenedor.addEventListener('mousemove', moverLupa);
            };

            const ocultarLupa = () => {
                lupa.style.opacity = '0';
                contenedor.removeEventListener('mousemove', moverLupa);
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
    <script src="script.js"></script>
    <script src="js/carrito.js"></script>
</body>

</html>