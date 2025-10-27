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
            $sql_opiniones = "SELECT u.nombre_usuario as Nombre_Usuario, po.Calificacion, po.Comentario, po.Fecha_Opinion
                              FROM producto_opiniones po
                              INNER JOIN usuario u ON po.id_usuario = u.id_usuario
                              WHERE po.Id_Producto = ?
                              ORDER BY po.Fecha_Opinion DESC";

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

// Variables para el header
$page_title = $producto ? htmlspecialchars($producto['Nombre_Producto']) . ' - Supermercado Online' : 'Producto no encontrado - Supermercado Online';

// Estilos adicionales específicos para esta página
$additional_styles = '
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

    /* Estilos para la sección de agregar reseñas */
    .agregar-resena {
        background: var(--color-blanco);
        padding: var(--espacio-lg);
        border-radius: var(--radio-md);
        box-shadow: var(--sombra-sm);
        margin-top: var(--espacio-lg);
        border: 1px solid var(--color-gris-200);
    }

    .agregar-resena h2 {
        color: var(--color-gris-800);
        margin-bottom: var(--espacio-md);
        font-size: var(--texto-xl);
    }

    .login-message {
        background: var(--color-secundario-light);
        padding: var(--espacio-md);
        border-radius: var(--radio-md);
        text-align: center;
        margin-bottom: var(--espacio-md);
    }

    .login-message p {
        margin-bottom: var(--espacio-sm);
        color: var(--color-secundario-hover);
    }

    .btn-login {
        background: var(--color-secundario);
        color: var(--color-blanco);
        padding: var(--espacio-sm) var(--espacio-md);
        border-radius: var(--radio-md);
        border: none;
        cursor: pointer;
        transition: var(--transicion-rapida);
    }

    .btn-login:hover {
        background: var(--color-secundario-hover);
        transform: translateY(-1px);
    }

    .form-resena {
        display: flex;
        flex-direction: column;
        gap: var(--espacio-md);
    }

    .rating-input label,
    .comment-input label {
        font-weight: var(--peso-medium);
        color: var(--color-gris-700);
        margin-bottom: var(--espacio-sm);
        display: block;
    }

    .star-rating {
        display: flex;
        gap: 4px;
        margin-top: var(--espacio-sm);
    }

    .star {
        font-size: 2em;
        color: var(--color-gris-300);
        cursor: pointer;
        transition: all var(--transicion-rapida);
        user-select: none;
    }

    .star:hover,
    .star.active {
        color: #ffd700;
        transform: scale(1.1);
    }

    .star.hover {
        color: #ffed4e;
    }

    .comment-input textarea {
        width: 100%;
        padding: var(--espacio-md);
        border: 2px solid var(--color-gris-300);
        border-radius: var(--radio-md);
        font-family: inherit;
        font-size: var(--texto-base);
        resize: vertical;
        min-height: 100px;
        transition: var(--transicion-rapida);
    }

    .comment-input textarea:focus {
        outline: none;
        border-color: var(--color-primario);
        box-shadow: 0 0 0 3px var(--color-primario-light);
    }

    .form-actions {
        display: flex;
        align-items: center;
        gap: var(--espacio-md);
    }

    .btn-enviar-resena {
        background: linear-gradient(135deg, var(--color-primario), var(--color-primario-hover));
        color: var(--color-blanco);
        padding: var(--espacio-md) var(--espacio-lg);
        border: none;
        border-radius: var(--radio-md);
        font-weight: var(--peso-medium);
        cursor: pointer;
        transition: all var(--transicion-rapida);
        display: flex;
        align-items: center;
        gap: var(--espacio-sm);
    }

    .btn-enviar-resena:hover {
        transform: translateY(-2px);
        box-shadow: var(--sombra-md);
    }

    .btn-enviar-resena:disabled {
        background: var(--color-gris-400);
        cursor: not-allowed;
        transform: none;
    }

    .loading {
        color: var(--color-primario);
        font-style: italic;
    }

    .message {
        padding: var(--espacio-md);
        border-radius: var(--radio-md);
        margin-top: var(--espacio-md);
    }

    .message.success {
        background: var(--color-exito-light);
        color: var(--color-exito);
        border: 1px solid var(--color-exito);
    }

    .message.error {
        background: var(--color-peligro-light);
        color: var(--color-peligro);
        border: 1px solid var(--color-peligro);
    }
</style>';

// Scripts adicionales específicos para esta página
$additional_scripts = '
<script>
    // --- Script de la Lupa (mantenido) ---
    const contenedor = document.querySelector(\'.imagen-lupa-contenedor\');
    const imgPrincipal = document.getElementById(\'imagen-principal\');
    const lupa = document.getElementById(\'lupa-zoom\');

    if (contenedor && imgPrincipal && lupa) {
        const zoomSrc = imgPrincipal.getAttribute(\'data-zoom-src\');
        lupa.style.backgroundImage = `url(\'${zoomSrc}\')`;
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
            lupa.style.opacity = \'1\';
            contenedor.addEventListener(\'mousemove\', moverLupa);
        };

        const ocultarLupa = () => {
            lupa.style.opacity = \'0\';
            contenedor.removeEventListener(\'mousemove\', moverLupa);
        };

        // Event Listeners
        contenedor.addEventListener(\'mouseenter\', mostrarLupa);
        contenedor.addEventListener(\'mouseleave\', ocultarLupa);

        // Carga inicial
        if (imgPrincipal.complete && imgPrincipal.naturalWidth > 0) {
            actualizarTamanioFondo();
        } else {
            imgPrincipal.addEventListener(\'load\', actualizarTamanioFondo);
        }
    }

    // --- Script para las Reseñas ---
    document.addEventListener(\'DOMContentLoaded\', function() {
        const formResena = document.getElementById(\'form-resena\');
        const loginMessage = document.getElementById(\'login-required-message\');
        const stars = document.querySelectorAll(\'.star\');
        const ratingValue = document.getElementById(\'rating-value\');
        const resenaMessage = document.getElementById(\'resena-message\');
        const loadingSpan = document.getElementById(\'resena-loading\');
        
        // Verificar si el usuario está logueado
        function verificarSesionParaResena() {
            if (window.__MYAPP && window.__MYAPP.usuarioActual) {
                formResena.style.display = \'block\';
                loginMessage.style.display = \'none\';
            } else {
                formResena.style.display = \'none\';
                loginMessage.style.display = \'block\';
            }
        }
        
        // Manejar el sistema de estrellas
        stars.forEach((star, index) => {
            star.addEventListener(\'click\', function() {
                const rating = this.getAttribute(\'data-rating\');
                ratingValue.value = rating;
                
                // Actualizar estrellas visualmente
                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.classList.add(\'active\');
                        s.textContent = \'★\';
                    } else {
                        s.classList.remove(\'active\');
                        s.textContent = \'☆\';
                    }
                });
            });
            
            // Efecto hover
            star.addEventListener(\'mouseenter\', function() {
                const rating = this.getAttribute(\'data-rating\');
                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.classList.add(\'hover\');
                    } else {
                        s.classList.remove(\'hover\');
                    }
                });
            });
        });
        
        // Quitar efecto hover al salir del área de estrellas
        document.querySelector(\'.star-rating\').addEventListener(\'mouseleave\', function() {
            stars.forEach(s => s.classList.remove(\'hover\'));
        });
        
        // Manejar envío del formulario
        formResena.addEventListener(\'submit\', async function(e) {
            e.preventDefault();
            
            const rating = ratingValue.value;
            const comentario = document.getElementById(\'comentario\').value;
            
            if (rating == 0) {
                mostrarMensaje(\'Por favor selecciona una calificación\', \'error\');
                return;
            }
            
            const submitBtn = formResena.querySelector(\'.btn-enviar-resena\');
            submitBtn.disabled = true;
            loadingSpan.style.display = \'inline\';
            
            try {
                const formData = new FormData();
                formData.append(\'accion\', \'agregar_resena\');
                formData.append(\'producto_id\', \'<?= $producto_id ?>\');
                formData.append(\'calificacion\', rating);
                formData.append(\'comentario\', comentario);
                
                const response = await fetch(\'agregar_resena.php\', {
                    method: \'POST\',
                    body: formData,
                    credentials: \'same-origin\'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    mostrarMensaje(\'¡Reseña agregada exitosamente!\', \'success\');
                    formResena.reset();
                    ratingValue.value = 0;
                    stars.forEach(s => {
                        s.classList.remove(\'active\');
                        s.textContent = \'☆\';
                    });
                    
                    // Recargar la página después de 2 segundos para mostrar la nueva reseña
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    mostrarMensaje(result.message || \'Error al agregar la reseña\', \'error\');
                }
                
            } catch (error) {
                console.error(\'Error:\', error);
                mostrarMensaje(\'Error al enviar la reseña\', \'error\');
            } finally {
                submitBtn.disabled = false;
                loadingSpan.style.display = \'none\';
            }
        });
        
        function mostrarMensaje(texto, tipo) {
            resenaMessage.textContent = texto;
            resenaMessage.className = `message ${tipo}`;
            resenaMessage.style.display = \'block\';
            
            setTimeout(() => {
                resenaMessage.style.display = \'none\';
            }, 5000);
        }
        
        // Verificar sesión inicial
        verificarSesionParaResena();
        
        // Re-verificar cuando cambie la sesión
        setInterval(verificarSesionParaResena, 1000);
    });
</script>';

// Incluir el header
include 'header.php';
?> <?php if ($error): ?>
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
                        <p>Este producto todavía no tiene opiniones. ¡Sé el primero en opinar!</p>
                    <?php endif; ?>
                </div>

                <!-- Sección para agregar nueva reseña -->
                <div class="agregar-resena">
                    <h2>Agregar tu opinión</h2>
                    
                    <div id="login-required-message" class="login-message" style="display:none;">
                        <p><i class="fas fa-info-circle"></i> Debes iniciar sesión para dejar una reseña.</p>
                        <button onclick="document.getElementById('login-link').click()" class="btn-login">Iniciar Sesión</button>
                    </div>

                    <form id="form-resena" class="form-resena" style="display:none;">
                        <div class="rating-input">
                            <label>Calificación:</label>
                            <div class="star-rating">
                                <span class="star" data-rating="1">☆</span>
                                <span class="star" data-rating="2">☆</span>
                                <span class="star" data-rating="3">☆</span>
                                <span class="star" data-rating="4">☆</span>
                                <span class="star" data-rating="5">☆</span>
                            </div>
                            <input type="hidden" id="rating-value" name="calificacion" value="0" required>
                        </div>

                        <div class="comment-input">
                            <label for="comentario">Comentario:</label>
                            <textarea id="comentario" name="comentario" rows="4" 
                                    placeholder="Comparte tu experiencia con este producto..."></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-enviar-resena">
                                <i class="fas fa-paper-plane"></i> Enviar Reseña
                            </button>
                            <span id="resena-loading" class="loading" style="display:none;">
                                <i class="fas fa-spinner fa-spin"></i> Enviando...
                            </span>
                        </div>
                    </form>

                    <div id="resena-message" class="message" style="display:none;"></div>
                </div>
            <?php else: ?>
                <p class="error">El producto solicitado no existe o no está disponible.</p>
            <?php endif; ?>

<?php
// Incluir el footer
include 'footer.php';
?>