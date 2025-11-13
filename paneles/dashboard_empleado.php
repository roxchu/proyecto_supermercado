<?php
// dashboard_empleado.php

// Mantenemos ob_start() para la robustez en la gestión de headers
ob_start();

// 1. INICIAR LA SESIÓN
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../carrito/db.php';
require_once __DIR__ . '/../login/verificar_rol.php';
// 3. ÚNICA LLAMADA Y DEFINITIVA para verificar el rol
// Permite el acceso a 'admin' Y 'empleado'.
verificar_rol(['admin', 'empleado']); 

// A partir de aquí, el acceso está garantizado
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['nombre_usuario'] ?? 'Usuario';

// Consulta para obtener ventas pendientes
try {
    $sql = "SELECT 
                v.id_venta, 
                v.fecha_venta, 
                v.total_venta,  
                v.estado,
                u.nombre_usuario AS nombre_cliente 
            FROM 
                venta v
            JOIN 
                usuario u ON v.id_usuario = u.id_usuario -- Usa la nueva FK
            WHERE 
                v.estado = 1  -- Se asume que '1' es el ID para el estado 'Pendiente'
            ORDER BY 
                v.fecha_venta ASC
            LIMIT 10";
            
    $stmt_ventas = $pdo->query($sql);
    $ventas_pendientes = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error DB en dashboard_empleado.php: " . $e->getMessage());
    $error_db = "Error al cargar ventas: " . $e->getMessage();
    $ventas_pendientes = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Empleado - Gestión de Pedidos</title>
    <link rel="stylesheet" href="../paneles/dashboard_empleado.css"> 
    
    <style>
        /* Estilos para el nuevo widget de stock */
        .gestion-stock .table-responsive {
            margin-top: 20px;
        }

        .gestion-stock table {
            width: 100%;
            border-collapse: collapse;
        }

        .gestion-stock th, .gestion-stock td {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .gestion-stock th {
            background-color: #f9f9f9;
        }
        
        /* Input para la cantidad */
        .input-cantidad {
            width: 80px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            text-align: center;
        }

        /* Botón secundario para "Renovar" */
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .btn-secondary:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        /* Stock actual (rojo si es 0) */
        .stock-actual {
            font-weight: bold;
        }
        .stock-actual.agotado {
            color: #d9534f; /* Rojo */
        }
        .stock-actual.bajo {
            color: #f0ad4e; /* Naranja */
        }

        /* Mensajes de feedback */
        #stock-loading {
            margin: 15px 0;
            font-weight: bold;
        }
        #stock-message {
            margin: 15px 0;
            padding: 10px;
            border-radius: 4px;
            display: none; /* Oculto por defecto */
        }
        #stock-message.success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        #stock-message.error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        
        /* Animación flash para fila actualizada */
        @keyframes flash-success {
            0% { background-color: #dff0d8; }
            100% { background-color: transparent; }
        }
        .flash-success {
            animation: flash-success 1.5s ease-out;
        }
    </style>
    </head>
<body>
    
    <div class="sidebar">
        <div class="sidebar-header">
            Panel Empleado
        </div>
        <ul>
            <li><a href="dashboard_empleado.php" class="active">Gestión de Pedidos</a></li>
            <li><a href="#gestion-stock">Gestión de Stock</a></li> 
            <li><a href="../login/logout.php">Cerrar Sesión</a></li>
            <li><a href="../index.html">Volver al Inicio</a></li>
        </ul>
    </div>

    <div class="main-content">
        <header class="main-header">
            <h1>Gestión de Empleados</h1>
            <p>Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?></p>
        </header>

        <section class="widget pedidos-pendientes">
            <h3><i class="fas fa-boxes"></i> Pedidos en Proceso</h3>
            
            <button id="btn-cargar-pedidos" class="btn-primary">Cargar Pedidos</button>
            
            <div id="pedidos-loading" style="display:none; text-align: center; padding: 1rem;">
                <i class="fas fa-spinner fa-spin"></i> Cargando pedidos...
            </div>
            <div id="pedidos-message" class="message"></div>
            
            <div id="pedidos-list-container" class="table-responsive">
                <p style="text-align: center; color: #999;">Haz clic en "Cargar Pedidos" para ver la lista</p>
            </div>
        </section>

        <section class="widget gestion-stock" id="gestion-stock">
            <h3>Renovar Stock - Productos Agotados</h3>
            <p>Aquí puedes renovar el stock de productos que están agotados (stock menor a 20).</p>

            <button id="btn-cargar-stock" class="btn-primary">Cargar Productos Sin Stock</button>
            
            <div id="stock-loading" style="display:none;">Cargando...</div>
            <div id="stock-message" class="message"></div>
            
            <div id="stock-list-container" class="table-responsive">
                </div>

        </section>


<!-- INICIO: Bloque Lector de Códigos (insertado automáticamente) -->
<section class="widget" id="lector-stock-widget" style="margin-top:1.5em;">
    <h3>Incrementar Stock con Lector</h3>
    <p>Escaneá el <strong>ID del producto</strong> con el lector (P-130)</p>

    <input type="text" id="lector_id" placeholder="Escaneá el ID del producto..." autofocus
           style="padding:10px;font-size:1.05em;width:100%;max-width:420px;border:1px solid #ccc;border-radius:5px;">
    <div id="lector_msg" style="margin-top:10px;font-weight:bold;"></div>
</section>

<script>
// Lector de código: escucha Enter en el input y envía al backend para incrementar stock (+1)
(function(){
    const input = document.getElementById('lector_id');
    const msg = document.getElementById('lector_msg');
    if (!input) return;

    // Asegurar foco al cargar y al hacer click en la página
    function focusInput(){ input.focus(); }
    document.addEventListener('DOMContentLoaded', focusInput);
    document.addEventListener('click', focusInput);

    input.addEventListener('keypress', async function(e){
        if (e.key !== 'Enter') return;
        const id = input.value.trim();
        if (!id) return;
        // mostrar procesando
        msg.style.color = '#333';
        msg.textContent = 'Procesando...';
        input.value = '';

        try {
            const res = await fetch('empleados_actioons.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'incrementar_stock', id_producto: id })
            });
            const data = await res.json();
            if (data.success) {
                msg.style.color = 'green';
                // Mostrar nuevo stock si viene en la respuesta
                const nuevo = data.nuevo_stock ? (' - Nuevo stock: ' + data.nuevo_stock) : '';
                msg.textContent = '✅ ' + (data.message || ('Producto ' + id + ' actualizado')) + nuevo;
            } else {
                msg.style.color = 'red';
                msg.textContent = '❌ ' + (data.message || 'No se pudo actualizar');
            }
        } catch (err) {
            console.error(err);
            msg.style.color = 'red';
            msg.textContent = '❌ Error de conexión con el servidor.';
        } finally {
            // mantener el foco para el siguiente escaneo
            setTimeout(()=> input.focus(), 200);
        }
    });
})();
</script>
<!-- FIN: Bloque Lector de Códigos -->


        </div> 

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- LÓGICA PARA GESTIÓN DE VENTAS (Tuya) ---
        // (Aquí iría tu función "procesarVenta" si la tienes en un script)
        // Ejemplo de cómo podría ser:
        window.procesarVenta = async function(idVenta, nuevoEstado) {
             // ... tu lógica de fetch para actualizar la venta ...
            console.log(`Procesando Venta ID: ${idVenta} a estado: ${nuevoEstado}`);
            // (Esta es solo una función placeholder)
        }


        // --- LÓGICA PARA GESTIÓN DE STOCK (Nueva) ---
        
        const btnCargarStock = document.getElementById('btn-cargar-stock');
        const stockListContainer = document.getElementById('stock-list-container');
        const stockLoading = document.getElementById('stock-loading');
        const stockMessage = document.getElementById('stock-message');

        // 1. Cargar productos al hacer clic en el botón
        btnCargarStock.addEventListener('click', cargarProductosBajoStock);

        // 2. Función para obtener los productos
        async function cargarProductosBajoStock() {
            mostrarCargando(true);
            mostrarMensaje('', 'success'); // Limpiar mensajes
            
            try {
                // Cargar solo productos con stock = 0 (agotados)
                const response = await fetch('empleados_actioons.php?action=get_productos_sin_stock&umbral=0');
                
                // VERIFICACIÓN CLAVE PARA EVITAR ERROR JSON
                if (!response.ok) {
                    // Si el servidor devuelve un error HTTP (403, 500, etc.)
                    // y el cuerpo no es JSON (es HTML), leemos como texto para evitar el error.
                    const responseText = await response.text();
                    
                    let errorMessage = `HTTP Error ${response.status}: `;
                    
                    // Si el texto empieza con "<!DOCTYPE", es HTML (posiblemente la página de login)
                    if (responseText.startsWith("<!DOCTYPE")) {
                        errorMessage += "Parece que la sesión ha expirado o no tienes permisos (Respuesta HTML inesperada).";
                    } else {
                        // Intentamos parsear como JSON por si es un error 500 con JSON.
                        try {
                            const errorData = JSON.parse(responseText);
                            errorMessage = errorData.message || errorMessage + "Error del servidor.";
                        } catch (e) {
                            errorMessage += "Error desconocido o respuesta no JSON.";
                        }
                    }
                    throw new Error(errorMessage);
                }

                // Si la respuesta fue ok (HTTP 200), asumimos que es JSON válido
                const data = await response.json();

                if (data.success) {
                    mostrarTablaStock(data.productos);
                } else {
                    mostrarMensaje(data.message, 'error');
                }

            } catch (error) {
                console.error('Error al cargar stock:', error);
                // Mostrar el error capturado (ahora más claro)
                mostrarMensaje('Error de conexión: ' + error.message, 'error');
            } finally {
                mostrarCargando(false);
            }
        }

        // 3. Función para mostrar la tabla en el HTML
        function mostrarTablaStock(productos) {
            stockListContainer.innerHTML = ''; // Limpiar contenedor

            if (productos.length === 0) {
                stockListContainer.innerHTML = '<p class="success-message">¡Excelente! No hay productos agotados en este momento. ✅</p>';
                return;
            }

            let html = `
                <table>
                    <thead>
                        <tr>
                            <th>Producto (ID)</th>
                            <th>Stock Actual</th>
                            <th>Nuevo Stock</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            for (const prod of productos) {
                html += `
                    <tr data-id="${prod.id_producto}">
                        <td>${prod.nombre_producto} (${prod.id_producto})</td>
                        <td>
                            <span class="stock-actual agotado" id="stock-actual-${prod.id_producto}">
                                ${prod.stock}
                            </span>
                        </td>
                        <td>
                            <input type="number" min="1" step="1" class="input-cantidad" placeholder="Ej: 25" required>
                        </td>
                        <td>
                            <button class="btn-secondary btn-renovar">Renovar Stock</button>
                        </td>
                    </tr>
                `;
            }

            html += `</tbody></table>`;
            stockListContainer.innerHTML = html;
        }

        // 4. Manejar clics en los botones "Renovar" (Usando delegación de eventos)
        stockListContainer.addEventListener('click', function(e) {
            // Solo nos interesa si se hizo clic en un botón "Renovar"
            if (e.target.classList.contains('btn-renovar')) {
                const tr = e.target.closest('tr');
                const idProducto = tr.dataset.id;
                const input = tr.querySelector('.input-cantidad');
                const nuevoStock = parseInt(input.value, 10);
                
                // Validar el nuevo stock
                if (!nuevoStock || nuevoStock <= 0) {
                    mostrarMensaje('Por favor, ingresa un stock válido (mayor a 0).', 'error');
                    input.focus();
                    return;
                }

                // Deshabilitar el botón para evitar doble clic
                e.target.disabled = true;
                e.target.textContent = '...';

                // Llamar a la función que actualiza
                renovarStock(idProducto, nuevoStock, e.target);
            }
        });

        // 5. Función para renovar el stock (llamar a la API)
        async function renovarStock(idProducto, nuevoStock, boton) {
            try {
                console.log('🔄 Enviando datos:', {
                    action: 'establecer_stock',
                    id_producto: idProducto,
                    nuevo_stock: nuevoStock
                });
                
                const response = await fetch('empleados_actioons.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'establecer_stock',
                        id_producto: idProducto,
                        nuevo_stock: nuevoStock
                    })
                });
                
                console.log(' Response status:', response.status);
                console.log(' Response headers:', response.headers.get('content-type'));
                
                // Manejo de error POST similar al GET
                if (!response.ok) {
                    const responseText = await response.text();
                    let errorMessage = `HTTP Error ${response.status}: `;

                    if (responseText.startsWith("<!DOCTYPE")) {
                        errorMessage += "Parece que la sesión ha expirado o no tienes permisos (Respuesta HTML inesperada).";
                    } else {
                        try {
                            const errorData = JSON.parse(responseText);
                            errorMessage = errorData.message || errorMessage + "Error del servidor.";
                        } catch (e) {
                            errorMessage += "Error desconocido o respuesta no JSON.";
                        }
                    }
                    throw new Error(errorMessage);
                }

                const data = await response.json();

                if (data.success) {
                    mostrarMensaje(data.message, 'success');
                    
                    // Como el producto ya no tiene stock 0, lo removemos de la tabla
                    const tr = boton.closest('tr');
                    tr.style.animation = 'flash-success 1.5s ease-out';
                    
                    setTimeout(() => {
                        tr.remove();
                        
                        // Si no quedan productos, mostrar mensaje de éxito
                        const tbody = stockListContainer.querySelector('tbody');
                        if (!tbody || tbody.children.length === 0) {
                            stockListContainer.innerHTML = '<p class="success-message">¡Excelente! Ya no hay productos agotados. ✅</p>';
                        }
                    }, 1500);

                } else {
                    mostrarMensaje(data.message, 'error');
                }

            } catch (error) {
                console.error('Error al renovar stock:', error);
                mostrarMensaje('Error de conexión al renovar: ' + error.message, 'error');
            } finally {
                // Volver a habilitar el botón
                boton.disabled = false;
                boton.textContent = 'Renovar';
            }
        }
        
        // Funciones auxiliares
        function mostrarCargando(mostrar) {
            stockLoading.style.display = mostrar ? 'block' : 'none';
        }

        function mostrarMensaje(mensaje, tipo = 'success') {
            stockMessage.textContent = mensaje;
            stockMessage.className = `message ${tipo}`; // Asigna 'success' o 'error'
            stockMessage.style.display = mensaje ? 'block' : 'none';

            // Ocultar mensaje después de 5 segundos
            setTimeout(() => {
                stockMessage.style.display = 'none';
            }, 5000);
        }

    });

        // === GESTIÓN DE PEDIDOS (NUEVO) ===
    const btnCargarPedidos = document.getElementById('btn-cargar-pedidos');
    const pedidosListContainer = document.getElementById('pedidos-list-container');
    const pedidosLoading = document.getElementById('pedidos-loading');
    const pedidosMessage = document.getElementById('pedidos-message');

    btnCargarPedidos.addEventListener('click', cargarPedidos);

    async function cargarPedidos() {
        mostrarCargandoPedidos(true);
        mostrarMensajePedidos('', 'success');
        
        try {
            const response = await fetch('empleados_actioons.php?action=get_pedidos');
            
            if (!response.ok) {
                const responseText = await response.text();
                let errorMessage = `HTTP Error ${response.status}`;
                
                if (responseText.startsWith("<!DOCTYPE")) {
                    errorMessage = "Sesión expirada o sin permisos.";
                } else {
                    try {
                        const errorData = JSON.parse(responseText);
                        errorMessage = errorData.message || errorMessage;
                    } catch (e) {
                        errorMessage = "Error desconocido.";
                    }
                }
                throw new Error(errorMessage);
            }

            const data = await response.json();

            if (data.success) {
                mostrarTablaPedidos(data.pedidos);
            } else {
                mostrarMensajePedidos(data.message, 'error');
            }

        } catch (error) {
            console.error('Error al cargar pedidos:', error);
            mostrarMensajePedidos('Error de conexión: ' + error.message, 'error');
        } finally {
            mostrarCargandoPedidos(false);
        }
    }

    function mostrarTablaPedidos(pedidos) {
        pedidosListContainer.innerHTML = '';

        if (pedidos.length === 0) {
            pedidosListContainer.innerHTML = '<p class="success-message">¡Excelente! No hay pedidos pendientes. ✅</p>';
            return;
        }

        let html = `
            <table>
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Cliente</th>
                        <th>Dirección</th>
                        <th>Fecha</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Estado Actual</th>
                        <th>Cambiar Estado</th>
                    </tr>
                </thead>
                <tbody>
        `;

        const estadosDisponibles = {
            'pendiente': 'Preparandose',
            'en_preparacion': 'Enviado',
            'enviado': 'Recibido',
            'recibido': 'Finalizado'
        };

        for (const pedido of pedidos) {
            const tieneProximoEstado = pedido.estado !== 'recibido';

            html += `
                <tr data-id="${pedido.id_pedido}">
                    <td><strong>#${pedido.id_pedido}</strong></td>
                    <td>${pedido.nombre_usuario}</td>
                    <td>${pedido.nombre_direccion} - ${pedido.calle_numero}</td>
                    <td>${new Date(pedido.fecha_pedido).toLocaleDateString('es-AR')}</td>
                    <td>${pedido.cantidad_items}</td>
                    <td>$${parseFloat(pedido.total_final).toFixed(2)}</td>
                    <td>
                        <span class="status-tag status-${pedido.estado}">
                            ${pedido.estado.toUpperCase().replace('_', ' ')}
                        </span>
                    </td>
                    <td>
                        ${tieneProximoEstado ? `
                            <button class="btn-secondary btn-cambiar-estado" data-pedido="${pedido.id_pedido}" data-estado-actual="${pedido.estado}">
                                → ${estadosDisponibles[pedido.estado]}
                            </button>
                        ` : `
                            <span style="color: #999;">Completado</span>
                        `}
                    </td>
                </tr>
            `;
        }

        html += `</tbody></table>`;
        pedidosListContainer.innerHTML = html;

        // Agregar event listeners a los botones
        document.querySelectorAll('.btn-cambiar-estado').forEach(btn => {
            btn.addEventListener('click', function() {
                const pedidoId = this.dataset.pedido;
                const estadoActual = this.dataset.estadoActual;
                cambiarEstadoPedido(pedidoId, estadoActual, this);
            });
        });
    }

    async function cambiarEstadoPedido(pedidoId, estadoActual, boton) {
        const transiciones = {
            'pendiente': 'en_preparacion',
            'en_preparacion': 'enviado',
            'enviado': 'recibido'
        };

        const nuevoEstado = transiciones[estadoActual];
        const textoEstado = {
            'en_preparacion': 'Preparandose',
            'enviado': 'Enviado',
            'recibido': 'Recibido'
        };

        const confirmar = confirm(`¿Cambiar el estado del pedido a "${textoEstado[nuevoEstado]}"?`);
        if (!confirmar) return;

        boton.disabled = true;
        boton.textContent = '...';

        try {
            const response = await fetch('empleados_actioons.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'cambiar_estado_pedido',
                    pedido_id: pedidoId,
                    nuevo_estado: nuevoEstado
                })
            });

            if (!response.ok) {
                const responseText = await response.text();
                throw new Error(responseText.startsWith("<!DOCTYPE") ? "Sesión expirada" : "Error del servidor");
            }

            const data = await response.json();

            if (data.success) {
                mostrarMensajePedidos(
                    `✓ Estado actualizado a ${textoEstado[nuevoEstado]}` + 
                    (data.convertido_a_venta ? ' - Convertido a venta ✓' : ''),
                    'success'
                );
                
                // Recargar tabla
                setTimeout(() => cargarPedidos(), 1500);
            } else {
                mostrarMensajePedidos('Error: ' + data.message, 'error');
                boton.disabled = false;
                boton.textContent = '→ ' + textoEstado[nuevoEstado];
            }

        } catch (error) {
            console.error('Error:', error);
            mostrarMensajePedidos('Error de conexión: ' + error.message, 'error');
            boton.disabled = false;
            boton.textContent = '→ ' + textoEstado[nuevoEstado];
        }
    }

    function mostrarCargandoPedidos(mostrar) {
        pedidosLoading.style.display = mostrar ? 'block' : 'none';
    }

    function mostrarMensajePedidos(mensaje, tipo = 'success') {
        pedidosMessage.textContent = mensaje;
        pedidosMessage.className = `message ${tipo}`;
        pedidosMessage.style.display = mensaje ? 'block' : 'none';

        setTimeout(() => {
            pedidosMessage.style.display = 'none';
        }, 5000);
    }
    </script>
    </body>
</html>