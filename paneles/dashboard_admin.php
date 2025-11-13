<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../login/control_acceso.php';
require_once __DIR__ . '/../carrito/db.php';


// SOLO permite admin (o owner, gracias a control_acceso)
verificar_rol('admin');

// --- MODIFICACIÓN: Identificar si el usuario es OWNER ---
// Asumimos que el login guarda 'rol' (nombre) y 'id_usuario'
$CURRENT_USER_ROL_NAME = strtolower($_SESSION['rol'] ?? '');
$CURRENT_USER_ID = $_SESSION['id_usuario'] ?? 0; 
$IS_OWNER = ($CURRENT_USER_ROL_NAME === 'owner');
// --- FIN MODIFICACIÓN ---

try {
    // --- 1. LEER DATOS PARA EL DASHBOARD (MÉTRICAS) ---
    $stmt_user_count = $pdo->query("SELECT COUNT(*) FROM usuario");
    $user_count = $stmt_user_count->fetchColumn();

    $stmt_prod_count = $pdo->query("SELECT COUNT(*) FROM producto");
    $prod_count = $stmt_prod_count->fetchColumn();

    // Contamos productos con stock menor a 20 como "Bajo Stock"
    $stmt_stock_count = $pdo->query("SELECT COUNT(*) FROM producto WHERE Stock < 20");
    $low_stock_count = $stmt_stock_count->fetchColumn();

    // Verificar si la tabla venta existe antes de consultarla
    $stmt_check_venta = $pdo->query("SHOW TABLES LIKE 'venta'");
    $venta_exists = $stmt_check_venta->rowCount() > 0;
    
    if ($venta_exists) {
        $stmt_sales_count = $pdo->query("SELECT COUNT(*) FROM venta WHERE fecha_venta >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $sales_count = $stmt_sales_count->fetchColumn();
    } else {
        $sales_count = 0; // No hay ventas si la tabla no existe
    }


    // --- 2. LEER DATOS PARA LAS TABLAS ---
    $stmt_usuarios = $pdo->query("SELECT u.id_usuario, u.DNI, u.nombre_usuario, u.correo, r.nombre_rol, u.id_rol 
                                  FROM usuario u 
                                  JOIN rol r ON u.id_rol = r.id_rol 
                                  ORDER BY u.id_usuario");
    $usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

    $stmt_roles = $pdo->query("SELECT * FROM rol ORDER BY id_rol");
    $roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

    $stmt_categorias = $pdo->query("SELECT * FROM categoria ORDER BY nombre_categoria");
    $categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

    // --- 3. LEER PRODUCTOS (SOLO IMAGEN PRINCIPAL) ---
    
    // Obtenemos los productos con imagen principal de producto_imagenes (orden = 1)
    $stmt_productos = $pdo->query("SELECT p.Id_Producto, 
                                          COALESCE(pi.url_imagen, 'https://via.placeholder.com/250x160?text=Sin+Imagen') AS imagen_url,
                                          p.Nombre_Producto, p.precio_actual, p.precio_anterior, p.Stock, p.Descripcion, p.Id_Categoria, c.nombre_categoria 
                                   FROM producto p 
                                   LEFT JOIN categoria c ON p.Id_Categoria = c.id_categoria
                                   LEFT JOIN producto_imagenes pi ON p.Id_Producto = pi.Id_Producto AND pi.orden = 1
                                   ORDER BY p.Id_Producto");
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);    
    
    // Aseguramos que la URL exista en el array de PHP para el JSON (sólo principal)
    foreach ($productos as $i => $producto) {
        $productos[$i]['imagen_url'] = $producto['imagen_url'] ?? ''; 
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
            
            <?php if ($IS_OWNER): ?>
            <li><a href="#roles" class="nav-link" data-target="section-roles"><i class="fas fa-user-tag"></i> Gestionar Roles</a></li>
            <?php endif; ?>
            
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
                        
                        <?php
                            $target_rol_lower = strtolower($usuario['nombre_rol']);
                            $target_id = $usuario['id_usuario'];
                            
                            $can_edit_role = false;
                            $can_delete = false;

                            if ($IS_OWNER) {
                                // Owner puede editar a todos
                                $can_edit_role = true; 
                                if ($CURRENT_USER_ID != $target_id) {
                                    // Owner puede borrar a todos menos a sí mismo
                                    $can_delete = true; 
                                }
                            } elseif ($CURRENT_USER_ID != $target_id) { 
                                // Si SOY ADMIN (no owner) y NO soy yo mismo
                                
                                if ($target_rol_lower !== 'owner') {
                                    // Admin puede editar a OTROS Admins (para degradar)
                                    // y a Empleados/Clientes.
                                    $can_edit_role = true;
                                }

                                if ($target_rol_lower !== 'owner' && $target_rol_lower !== 'admin') {
                                    // Admin solo puede borrar Empleados/Clientes
                                    $can_delete = true;
                                }
                            }
                            // Si SOY ADMIN y el target es 'owner', $can_edit_role y $can_delete son false.
                            // Si SOY ADMIN y el target soy YO, $can_edit_role y $can_delete son false.
                        ?>
                        
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
                                        data-rol-id="<?php echo $usuario['id_rol']; ?>"
                                        <?php if (!$can_edit_role) echo 'disabled'; /* Deshabilitar botón */ ?>
                                        >
                                    <i class="fas fa-user-tag"></i> Rol
                                </button>
                                <button class="btn btn-delete btn-delete-item"
                                        data-id="<?php echo $usuario['id_usuario']; ?>"
                                        data-tipo="usuario"
                                        data-nombre="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>"
                                        <?php if (!$can_delete) echo 'disabled'; /* Deshabilitar botón */ ?>
                                        >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($IS_OWNER): ?>
        <section id="section-roles" class="content-section">
            <h2>Gestionar Roles</h2>
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
                                </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

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
                            <td><?php echo htmlspecialchars($producto['nombre_categoria'] ?? 'N/A'); ?></td>
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
                    
                    <?php foreach ($roles as $rol): 
                        $rol_nombre_lower = strtolower($rol['nombre_rol']);
                        
                        // Si NO soy Owner, solo muestro roles que NO sean 'owner' o 'admin'
                        if (!$IS_OWNER && ($rol_nombre_lower === 'owner' || $rol_nombre_lower === 'admin')) {
                            continue; // Admin no puede ASIGNAR este rol
                        }
                    ?>
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

    <?php if ($IS_OWNER): ?>
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
    <?php endif; ?>

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
                        <option value="<?php echo $categoria['id_categoria']; ?>">
                            <?php echo htmlspecialchars($categoria['nombre_categoria']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <hr>
                <h4>Imagen Principal</h4>
                
                <label for="prod-img-principal">URL de Imagen:</label>
                <input type="url" id="prod-img-principal" name="imagen_url_principal" class="full-width-input" placeholder="https://..." required>
                
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
                // No abrir modal si el botón está deshabilitado
                if (this.disabled) return;
                
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
                            // MODO AGREGAR ROL (ya no existe el botón de agregar rol)
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
                // Aquí 'error.message' puede ser el error de JSON.parse o el mensaje del 'throw new Error'
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

        // 4. Manejador para TODOS los botones de ELIMINAR (Solo Usuario y Producto)
        document.querySelectorAll('.btn-delete-item').forEach(button => {
            button.addEventListener('click', async function() {
                // No hacer nada si está deshabilitado
                if (this.disabled) return;
                
                const id = this.getAttribute('data-id');
                const tipo = this.getAttribute('data-tipo');
                const nombre = this.getAttribute('data-nombre');

                if (tipo === 'rol') {
                    alert('La eliminación de roles está deshabilitada por motivos de seguridad.');
                    return;
                }

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