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
    $sql_producto = "SELECT p.*, c.Nombre_Categoria, pi.url_imagen AS imagen_url
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
$additional_styles = '<link rel="stylesheet" href="css/mostrar-producto.css">';

// Scripts adicionales específicos para esta página
$additional_scripts = '
<script>
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
            // Verificar múltiples fuentes de información de sesión
            const userInfo = document.getElementById(\'user-info\');
            const loginLink = document.getElementById(\'login-link\');
            const userGreeting = document.getElementById(\'user-greeting\');
            
            // Si user-info está visible y login-link está oculto, el usuario está logueado
            const estaLogueado = (userInfo && userInfo.style.display !== \'none\' && userInfo.style.display !== \'\') ||
                                (loginLink && loginLink.style.display === \'none\') ||
                                (userGreeting && userGreeting.textContent && userGreeting.textContent.trim() !== \'\') ||
                                (window.__MYAPP && window.__MYAPP.usuarioActual);
            
            if (estaLogueado) {
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
                // Obtener el ID del producto desde el DOM
                const productoElement = document.querySelector(\'.producto[data-id]\');
                const productoId = productoElement ? productoElement.getAttribute(\'data-id\') : \'<?= $producto_id ?>\';
                
                if (!productoId || productoId === \'\') {
                    mostrarMensaje(\'Error: No se puede identificar el producto\', \'error\');
                    return;
                }
                
                const formData = new FormData();
                formData.append(\'accion\', \'agregar_resena\');
                formData.append(\'producto_id\', productoId);
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
        
        // Verificar sesión inicial con el servidor
        async function verificarSesionInicial() {
            try {
                const response = await fetch(\'login/check_session.php\', {
                    method: \'GET\',
                    credentials: \'same-origin\',
                    headers: { \'Accept\': \'application/json\' }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.logged_in) {
                        formResena.style.display = \'block\';
                        loginMessage.style.display = \'none\';
                        return;
                    }
                }
            } catch (error) {
                console.log(\'Error verificando sesión:\', error);
            }
            
            // Si falla la verificación del servidor, usar verificación del DOM
            verificarSesionParaResena();
        }
        
        // Escuchar cambios en el estado de sesión
        document.addEventListener(\'sessionChanged\', function(event) {
            verificarSesionParaResena();
            // Mostrar/ocultar información de admin
            mostrarInfoAdmin(event.detail);
        });
        
        // Función para mostrar información solo a admins
        function mostrarInfoAdmin(sessionData) {
            const adminOnlyElements = document.querySelectorAll(\'.admin-only-info\');
            
            if (sessionData && sessionData.rol === \'admin\') {
                // Mostrar elementos solo para admin
                adminOnlyElements.forEach(element => {
                    element.classList.add(\'show-admin\');
                    element.style.display = \'block\';
                });
            } else {
                // Ocultar elementos para no-admin
                adminOnlyElements.forEach(element => {
                    element.classList.remove(\'show-admin\');
                    element.style.display = \'none\';
                });
            }
        }
        
        // Verificar sesión inicial para admin info
        async function verificarSesionInicialAdmin() {
            try {
                const response = await fetch(\'login/check_session.php\', {
                    method: \'GET\',
                    credentials: \'same-origin\',
                    headers: { \'Accept\': \'application/json\' }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    mostrarInfoAdmin(data);
                }
            } catch (error) {
                console.log(\'Error verificando sesión para admin:\', error);
            }
        }
        
        // También escuchar cambios en user-info
        const userInfo = document.getElementById(\'user-info\');
        if (userInfo) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === \'attributes\' && mutation.attributeName === \'style\') {
                        verificarSesionParaResena();
                    }
                });
            });
            observer.observe(userInfo, { attributes: true });
        }
        
        // Verificar sesión inicial
        verificarSesionInicial();
        verificarSesionInicialAdmin();
        
        // Re-verificar cuando cambie la sesión (cada 2 segundos es suficiente)
        setInterval(verificarSesionParaResena, 2000);
    });
</script>';

// Incluir el header
include 'header.php';
?> <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php elseif ($producto): ?>

            <div class="producto-principal">
                <div class="producto-imagen">
                    <img id="imagen-principal"
                        src="<?= htmlspecialchars($producto['imagen_url'] ?: 'https://via.placeholder.com/400x400') ?>"
                        alt="<?= htmlspecialchars($producto['Nombre_Producto']) ?>">
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
                        <div class="producto-cantidad admin-only-info" style="display:none;">
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
            </div>

            <!-- Secciones debajo del producto -->
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