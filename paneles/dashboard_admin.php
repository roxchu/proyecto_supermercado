<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../login/control_acceso.php'; // <-- ruta corregida
require_once __DIR__ . '/../db.php';

// SOLO permite admin
verificar_rol('admin');

try {
    // --- 1. LEER DATOS PARA EL DASHBOARD (MÉTRICAS) ---
    $stmt_user_count = $pdo->query("SELECT COUNT(*) FROM usuario");
    $user_count = $stmt_user_count->fetchColumn();

    $stmt_prod_count = $pdo->query("SELECT COUNT(*) FROM producto");
    $prod_count = $stmt_prod_count->fetchColumn();

    // Contamos productos con stock menor a 20 como "Bajo Stock"
    $stmt_stock_count = $pdo->query("SELECT COUNT(*) FROM producto WHERE Stock < 20");
    $low_stock_count = $stmt_stock_count->fetchColumn();

    $stmt_sales_count = $pdo->query("SELECT COUNT(*) FROM venta WHERE fecha_venta >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $sales_count = $stmt_sales_count->fetchColumn();


    // --- 2. LEER DATOS PARA LAS TABLAS ---
    $stmt_usuarios = $pdo->query("SELECT u.id_usuario, u.DNI, u.nombre_usuario, u.correo, r.nombre_rol, u.id_rol 
                                  FROM usuario u 
                                  JOIN rol r ON u.id_rol = r.id_rol 
                                  ORDER BY u.id_usuario");
    $usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

    $stmt_roles = $pdo->query("SELECT * FROM rol ORDER BY id_rol");
    $roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

    $stmt_categorias = $pdo->query("SELECT * FROM categoria ORDER BY Nombre_Categoria");
    $categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

    // --- 3. LEER PRODUCTOS Y SUS IMÁGENES SECUNDARIAS ---
    
    // Obtenemos todas las imágenes secundarias (orden > 1)
    $stmt_imagenes = $pdo->query("SELECT Id_Producto, url_imagen FROM producto_imagenes WHERE orden > 1 ORDER BY orden");
    $imagenes_secundarias_map = [];
    foreach ($stmt_imagenes->fetchAll(PDO::FETCH_ASSOC) as $img) {
        $imagenes_secundarias_map[$img['Id_Producto']][] = $img['url_imagen'];
    }

    // Obtenemos los productos
    $stmt_productos = $pdo->query("SELECT p.Id_Producto, p.imagen_url, p.Nombre_Producto, p.precio_actual, p.precio_anterior, p.Stock, p.Descripcion, p.Id_Categoria, c.Nombre_Categoria 
                                   FROM producto p 
                                   LEFT JOIN categoria c ON p.Id_Categoria = c.Id_Categoria 
                                   ORDER BY p.Id_Producto");
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

    // Añadimos las imágenes secundarias a cada array de producto
    foreach ($productos as $i => $producto) {
        $prod_id = $producto['Id_Producto'];
        // Aseguramos que imagen_url (principal) no sea null para el JS
        $productos[$i]['imagen_url'] = $producto['imagen_url'] ?? ''; 
        $productos[$i]['imagenes_secundarias'] = $imagenes_secundarias_map[$prod_id] ?? [];
    }

} catch (PDOException $e) {
    // Por seguridad, en producción deberías usar un mensaje más genérico
    die("Error al conectar o consultar la base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Supermercado</title>
    <link rel="stylesheet" href="dashboard_admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            Panel Admin
        </div>
        <ul>
            <li><a href="#dashboard" class="nav-link active" data-target="section-dashboard"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="#usuarios" class="nav-link" data-target="section-usuarios"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
            <li><a href="#roles" class="nav-link" data-target="section-roles"><i class="fas fa-user-tag"></i> Gestionar Roles</a></li>
            <li><a href="#productos" class="nav-link" data-target="section-productos"><i class="fas fa-boxes"></i> Gestionar Productos</a></li>
            <li><a href="../index.html"><i class="fas fa-store"></i> Volver a la Tienda</a></li>
        </ul>
    </aside>

    <main class="main-content">
        
        <header class="admin-header">
            <h1>Bienvenido al Panel de Administración</h1>
        </header>

        <section id="section-dashboard" class="content-section active-section">
            <h2>Métricas Principales</h2>
            <div class="metrics-grid">
                <div class="metric-card red-accent">
                    <i class="fas fa-users"></i>
                    <h3>Usuarios Registrados</h3>
                    <p><?php echo $user_count; ?></p>
                </div>
                <div class="metric-card">
                    <i class="fas fa-boxes"></i>
                    <h3>Productos Totales</h3>
                    <p><?php echo $prod_count; ?></p>
                </div>
                <div class="metric-card">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Pedidos (Últ. 30 días)</h3>
                    <p><?php echo $sales_count; ?></p>
                </div>
                <div class="metric-card alert-accent">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Bajo Stock (&lt; 20)</h3>
                    <p><?php echo $low_stock_count; ?></p>
                </div>
            </div>
        </section>

        <section id="section-usuarios" class="content-section">
            <h2>Gestionar Usuarios</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>DNI</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['id_usuario']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['DNI']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['correo']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre_rol']); ?></td>
                            <td>
                                <button class="btn btn-edit-role" 
                                        data-modal="modal-editar-rol"
                                        data-id="<?php echo $usuario['id_usuario']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>"
                                        data-rol-id="<?php echo $usuario['id_rol']; ?>">
                                    <i class="fas fa-user-tag"></i> Rol
                                </button>
                                <button class="btn btn-delete btn-delete-item"
                                        data-id="<?php echo $usuario['id_usuario']; ?>"
                                        data-tipo="usuario"
                                        data-nombre="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="section-roles" class="content-section">
            <h2>Gestionar Roles</h2>
            <button class="btn btn-primary float-right" data-modal="modal-rol"><i class="fas fa-plus"></i> Agregar Rol</button>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID Rol</th>
                            <th>Nombre del Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $rol): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rol['id_rol']); ?></td>
                            <td><?php echo htmlspecialchars($rol['nombre_rol']); ?></td>
                            <td>
                                <button class="btn btn-edit btn-edit-rol"
                                        data-modal="modal-rol"
                                        data-id="<?php echo $rol['id_rol']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($rol['nombre_rol']); ?>">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="btn btn-delete btn-delete-item"
                                        data-id="<?php echo $rol['id_rol']; ?>"
                                        data-tipo="rol"
                                        data-nombre="<?php echo htmlspecialchars($rol['nombre_rol']); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="section-productos" class="content-section">
            <h2>Gestionar Productos</h2>
            <button class="btn btn-primary float-right" data-modal="modal-producto"><i class="fas fa-plus"></i> Agregar Producto</button>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Categoría</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($producto['Id_Producto']); ?></td>
                            <td><img src="<?php echo htmlspecialchars($producto['imagen_url']); ?>" alt="Producto" width="60" height="60"></td>
                            <td><?php echo htmlspecialchars($producto['Nombre_Producto']); ?></td>
                            <td>$<?php echo number_format($producto['precio_actual'], 2); ?></td>
                            <td <?php if ($producto['Stock'] < 20) echo 'class="low-stock-alert"'; ?>>
                                <?php echo htmlspecialchars($producto['Stock']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($producto['Nombre_Categoria'] ?? 'N/A'); ?></td>
                            <td>
                                <button class="btn btn-edit btn-edit-producto" 
                                        data-modal="modal-producto"
                                        data-json="<?php echo htmlspecialchars(json_encode($producto), ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fas fa-pencil-alt"></i> Editar
                                </button>
                                <button class="btn btn-delete btn-delete-item"
                                        data-id="<?php echo $producto['Id_Producto']; ?>"
                                        data-tipo="producto"
                                        data-nombre="<?php echo htmlspecialchars($producto['Nombre_Producto']); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div id="modal-editar-rol" class="modal-bg" style="display: none;">
        <div class="modal-content">
            <h3>Editar Rol de Usuario</h3>
            <form id="form-edit-role"> 
                <input type="hidden" id="edit-user-id" name="id_usuario">
                <input type="hidden" name="action" value="update_user_role">
                
                <label for="user-name">Usuario:</label>
                <input type="text" id="user-name" class="full-width-input" readonly>
                
                <label for="user-role">Nuevo Rol:</label>
                <select id="user-role" name="id_rol" class="full-width-input">
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?php echo $rol['id_rol']; ?>">
                            <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel-modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-rol" class="modal-bg" style="display: none;">
        <div class="modal-content">
            <h3 id="modal-rol-titulo">Agregar Rol</h3>
            <form id="form-rol">
                <input type="hidden" id="rol-id" name="id_rol">
                <input type="hidden" id="rol-action" name="action" value="add_role">
                
                <label for="rol-nombre">Nombre del Rol:</label>
                <input type="text" id="rol-nombre" name="nombre_rol" class="full-width-input" required>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel-modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-producto" class="modal-bg" style="display: none;">
        <div class="modal-content large-modal">
            <h3 id="modal-producto-titulo">Agregar Producto</h3>
            <form id="form-producto">
                <input type="hidden" id="prod-id" name="id_producto">
                <input type="hidden" name="action" value="save_product">
                
                <label for="prod-nombre">Nombre:</label>
                <input type="text" id="prod-nombre" name="nombre" class="full-width-input" required>
                
                <label for="prod-descripcion">Descripción:</label>
                <textarea id="prod-descripcion" name="descripcion" class="full-width-input" rows="3"></textarea>
                
                <label for="prod-precio">Precio Actual:</label>
                <input type="number" id="prod-precio" name="precio_actual" class="full-width-input" step="0.01" required>
                
                <label for="prod-precio-anterior">Precio Anterior (Opcional):</label>
                <input type="number" id="prod-precio-anterior" name="precio_anterior" class="full-width-input" step="0.01">

                <label for="prod-stock">Stock:</label>
                <input type="number" id="prod-stock" name="stock" class="full-width-input" required>
                
                <label for="prod-categoria">Categoría:</label>
                <select id="prod-categoria" name="id_categoria" class="full-width-input" required>
                    <option value="">Seleccione una categoría...</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['Id_Categoria']; ?>">
                            <?php echo htmlspecialchars($categoria['Nombre_Categoria']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <hr>
                <h4>Imágenes del Producto</h4>
                
                <label for="prod-img-principal">Imagen Principal (URL de tabla 'producto'):</label>
                <input type="url" id="prod-img-principal" name="imagen_url_principal" class="full-width-input" placeholder="https://..." required>
                
                <label>Imágenes Secundarias (URLs de tabla 'producto_imagenes'):</label>
                <div id="lista-imagenes-secundarias">
                </div>
                <button type="button" id="add-image-url" class="btn"><i class="fas fa-plus"></i> Añadir URL</button>

                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel-modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>


    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('.sidebar .nav-link');
        const sections = document.querySelectorAll('.content-section');
        const modalButtons = document.querySelectorAll('[data-modal]');
        const cancelButtons = document.querySelectorAll('.btn-cancel-modal');
        const modalProducto = document.getElementById('modal-producto');
        const imageListContainer = modalProducto.querySelector('#lista-imagenes-secundarias');
        const addImageBtn = document.getElementById('add-image-url');

        // Navegación de la Sidebar
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('data-target');
                const targetSection = document.getElementById(targetId);
                
                navLinks.forEach(nav => nav.classList.remove('active'));
                this.classList.add('active');
                
                sections.forEach(sec => sec.classList.remove('active-section'));
                if (targetSection) {
                    targetSection.classList.add('active-section');
                }
            });
        });

        // Abrir Modales
        modalButtons.forEach(button => {
            button.addEventListener('click', function() {
                const modalId = this.getAttribute('data-modal');
                const modal = document.getElementById(modalId);
                
                if (modal) {
                    // Lógica específica para el modal de ROL DE USUARIO
                    if (modalId === 'modal-editar-rol') {
                        const userId = this.getAttribute('data-id');
                        const userName = this.getAttribute('data-nombre');
                        const userRolId = this.getAttribute('data-rol-id');
                        
                        modal.querySelector('#edit-user-id').value = userId;
                        modal.querySelector('#user-name').value = userName;
                        modal.querySelector('#user-role').value = userRolId;
                    }

                    // Lógica para el modal de ROL (Agregar/Editar)
                    if (modalId === 'modal-rol') {
                        const modalTitle = modal.querySelector('#modal-rol-titulo');
                        const form = modal.querySelector('#form-rol');
                        
                        if (this.classList.contains('btn-edit-rol')) {
                            // MODO EDITAR ROL
                            modalTitle.innerText = 'Editar Rol';
                            form.querySelector('#rol-id').value = this.getAttribute('data-id');
                            form.querySelector('#rol-nombre').value = this.getAttribute('data-nombre');
                            form.querySelector('#rol-action').value = 'update_role';
                        } else {
                            // MODO AGREGAR ROL
                            modalTitle.innerText = 'Agregar Rol';
                            form.reset();
                            form.querySelector('#rol-id').value = '';
                            form.querySelector('#rol-action').value = 'add_role';
                        }
                    }

                    // Lógica específica para el modal de PRODUCTO
                    if (modalId === 'modal-producto') {
                        const modalTitle = modal.querySelector('#modal-producto-titulo');
                        const form = modal.querySelector('#form-producto');
                        
                        // Limpiar imágenes secundarias anteriores
                        imageListContainer.innerHTML = '';
                        
                        if (this.classList.contains('btn-edit-producto')) {
                            // MODO EDITAR: Rellenar el formulario
                            modalTitle.innerText = 'Editar Producto';
                            const data = JSON.parse(this.getAttribute('data-json'));
                            
                            form.querySelector('#prod-id').value = data.Id_Producto;
                            form.querySelector('#prod-nombre').value = data.Nombre_Producto;
                            form.querySelector('#prod-descripcion').value = data.Descripcion;
                            form.querySelector('#prod-precio').value = data.precio_actual;
                            // Asegura que precio_anterior no sea null, si es null o 0, lo deja vacío
                            form.querySelector('#prod-precio-anterior').value = data.precio_anterior && data.precio_anterior != 0 ? data.precio_anterior : '';
                            form.querySelector('#prod-stock').value = data.Stock;
                            form.querySelector('#prod-categoria').value = data.Id_Categoria;
                            form.querySelector('#prod-img-principal').value = data.imagen_url;

                            // Cargar imágenes secundarias existentes
                            if (data.imagenes_secundarias && data.imagenes_secundarias.length > 0) {
                                data.imagenes_secundarias.forEach(url => {
                                    addDynamicImageUrl(url);
                                });
                            }

                        } else {
                            // MODO AGREGAR: Limpiar el formulario
                            modalTitle.innerText = 'Agregar Producto';
                            form.reset();
                            form.querySelector('#prod-id').value = '';
                        }
                    }
                    
                    modal.style.display = 'flex';
                }
            });
        });

        // Cerrar Modales
        cancelButtons.forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.modal-bg').style.display = 'none';
            });
        });

        // --- Lógica del Modal de Producto (Imágenes) ---

        // Función para añadir un campo de URL
        function addDynamicImageUrl(url = '') {
            const count = imageListContainer.children.length + 2;
            const newImageGroup = document.createElement('div');
            newImageGroup.className = 'image-url-group';
            
            // Usamos name="imagenes_secundarias[]" para que PHP lo reciba como un array
            newImageGroup.innerHTML = `
                <input type="url" name="imagenes_secundarias[]" class="full-width-input" value="${url}" placeholder="https://... (URL Imagen ${count})">
                <button type="button" class="btn btn-delete remove-image-url"><i class="fas fa-times"></i></button>
            `;
            imageListContainer.appendChild(newImageGroup);
        }

        // Botón "Añadir URL"
        if (addImageBtn) {
            addImageBtn.addEventListener('click', function() {
                addDynamicImageUrl(); // Añade un campo vacío
            });
        }

        // Delegación de eventos para botones "Eliminar URL"
        if (imageListContainer) {
            imageListContainer.addEventListener('click', function(e) {
                const removeButton = e.target.closest('.remove-image-url');
                if (removeButton) {
                    removeButton.parentElement.remove();
                }
            });
        }


        // -----------------------------------------------------------------
        // LÓGICA DE ENVÍO DE FORMULARIOS (SUBMIT) Y DELETE
        // -----------------------------------------------------------------

        const backendUrl = 'admin_actions.php';

        // Función genérica para manejar envíos de formularios simples (AJAX)
        async function handleFormSubmit(form) {
            const formData = new FormData(form);
            
            try {
                const response = await fetch(backendUrl, {
                    method: 'POST',
                    body: formData
                });

                // Intentamos parsear la respuesta como JSON
                const result = await response.json();

                if (response.ok && result.success) {
                    alert(result.message || 'Acción completada con éxito.');
                    location.reload(); // Recargamos la página para ver los cambios
                } else {
                    // Si no es exitoso (status 200 con success: false o status != 200)
                    throw new Error(result.message || 'Ocurrió un error desconocido al procesar la solicitud.');
                }
            } catch (error) {
                console.error('Error en el fetch:', error);
                alert('Error: ' + error.message);
            }
        }

        // 1. Manejador para el formulario de ROL DE USUARIO
        const formEditRole = document.getElementById('form-edit-role');
        if (formEditRole) {
            formEditRole.addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmit(this);
            });
        }

        // 2. Manejador para el formulario de ROL (Agregar/Editar)
        const formRol = document.getElementById('form-rol');
        if (formRol) {
            formRol.addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmit(this);
            });
        }

        // 3. Manejador para el formulario de PRODUCTO
        const formProducto = document.getElementById('form-producto');
        if (formProducto) {
            formProducto.addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmit(this);
            });
        }

        // 4. Manejador para TODOS los botones de ELIMINAR
        document.querySelectorAll('.btn-delete-item').forEach(button => {
            button.addEventListener('click', async function() {
                const id = this.getAttribute('data-id');
                const tipo = this.getAttribute('data-tipo');
                const nombre = this.getAttribute('data-nombre');

                if (confirm(`¿Está seguro que desea eliminar "${nombre}" (Tipo: ${tipo}, ID: ${id})?`)) {
                    const formData = new FormData();
                    formData.append('action', 'delete_item');
                    formData.append('id', id);
                    formData.append('tipo', tipo);

                    try {
                        const response = await fetch(backendUrl, {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();

                        if (response.ok && result.success) {
                            alert(result.message || 'Elemento eliminado.');
                            location.reload();
                        } else {
                            throw new Error(result.message || 'Error al eliminar.');
                        }
                    } catch (error) {
                        console.error('Error al eliminar:', error);
                        alert('Error: ' + error.message);
                    }
                }
            });
        });

    });
    </script>

</body>
</html>