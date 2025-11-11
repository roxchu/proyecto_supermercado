<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id']) && !isset($_SESSION['dni'])) {
    header("Location: /proyecto_supermercado/login/login.php");
    exit;
}

// Suponemos que si la sesión existe, $_SESSION['user_id'] está disponible.
$id_usuario_sesion = isset($_SESSION['user_id']) ? htmlspecialchars($_SESSION['user_id']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dirección y Pago - Supermercado Día</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --azul: #007bff;
      --azul-oscuro: #0056b3;
      --blanco: #ffffff;
      --gris-fondo: #f7f9fc;
      --gris-claro: #f0f2f5;
      --texto: #333;
      --borde: #dcdcdc;
      --sombra: 0 8px 20px rgba(0,0,0,0.08);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: "Poppins", sans-serif;
      background-color: var(--gris-fondo);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 2rem;
    }

    .main-container {
      display: flex;
      gap: 1.5rem;
      width: 100%;
      max-width: 1400px; /* Aumentamos para tres columnas */
      animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(20px);}
      to {opacity: 1; transform: translateY(0);}
    }

    .form-container {
  background-color: var(--blanco);
  border-radius: 16px;
  box-shadow: var(--sombra);
  overflow: hidden;
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 2rem 2rem 2rem 2rem;
    }

    .encabezado {
      background-color: var(--azul);
      color: var(--blanco);
      text-align: center;
      padding: 1.5rem;
      margin: -2rem -2rem 1.5rem -2rem;
    }

    .encabezado h2 {
      margin: 0;
      font-size: 1.6rem;
      font-weight: 600;
    }

    form {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
  height: 100%;
  background: none;
    }

    label {
      font-weight: 600;
      color: var(--texto);
      font-size: 0.9rem;
      margin-bottom: -0.5rem; /* Acercamos la etiqueta al input */
    }

    input, select, textarea {
      padding: 0.9rem;
      border: 1px solid var(--borde);
      border-radius: 8px;
      font-size: 1rem;
      width: 100%;
      transition: border-color 0.2s, box-shadow 0.2s;
      background-color: var(--gris-claro);
    }
    
    input:focus, select:focus, textarea:focus {
      border-color: var(--azul);
      box-shadow: 0 0 0 3px rgba(0,123,255,0.2);
      outline: none;
      background-color: var(--blanco);
    }

    textarea {
      resize: none;
      min-height: 80px;
    }

    .form-row {
      display: flex;
      gap: 1rem;
    }

    .form-row > div {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 0.5rem; /* Espacio entre label e input */
    }
    
    /* Contenedor dinámico para campos de tarjeta */
    .campos-tarjeta {
        display: none; /* Oculto por defecto */
        flex-direction: column;
        gap: 1.2rem;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }


    .boton {
      background-color: var(--azul);
      color: var(--blanco);
      border: none;
      padding: 0.9rem;
      font-size: 1.1rem;
      font-weight: 600;
      border-radius: 10px;
      cursor: pointer;
      transition: background-color 0.2s, transform 0.1s;
      margin-top: auto; /* Empuja el botón al final del formulario */
    }

    .boton:hover {
      background-color: var(--azul-oscuro);
      transform: scale(1.02);
    }

    .volver-container {
      text-align: center;
      margin-top: 2rem;
      width: 100%;
      max-width: 1200px;
    }

    .volver-container a {
      color: var(--azul);
      text-decoration: none;
      font-weight: 600;
      font-size: 1rem;
      transition: color 0.2s;
    }

    .volver-container a:hover {
      color: var(--azul-oscuro);
    }

    /* Estilos para el resumen del pedido */
    .resumen-container {
      background-color: var(--blanco);
      border-radius: 16px;
      box-shadow: var(--sombra);
      overflow: hidden;
      min-width: 350px;
      max-width: 400px;
      height: fit-content;
      position: sticky;
      top: 2rem;
    }

    .resumen-header {
      background-color: #28a745;
      color: var(--blanco);
      text-align: center;
      padding: 1.5rem;
    }

    .resumen-content {
      padding: 1.5rem;
    }

    .producto-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.8rem 0;
      border-bottom: 1px solid var(--gris-claro);
    }

    .producto-item:last-child {
      border-bottom: none;
    }

    .producto-info {
      flex: 1;
    }

    .producto-nombre {
      font-weight: 600;
      color: var(--texto);
      font-size: 0.9rem;
      margin-bottom: 0.2rem;
    }

    .producto-cantidad {
      color: #666;
      font-size: 0.8rem;
    }

    .producto-precio {
      font-weight: 600;
      color: var(--azul);
    }

    .subtotal-row, .envio-row, .total-row {
      display: flex;
      justify-content: space-between;
      padding: 0.5rem 0;
      font-weight: 600;
    }

    .envio-row {
      color: #28a745;
      border-bottom: 1px solid var(--gris-claro);
      padding-bottom: 1rem;
      margin-bottom: 1rem;
    }

    .total-row {
      font-size: 1.2rem;
      color: var(--azul);
      border-top: 2px solid var(--azul);
      padding-top: 1rem;
    }

    .btn-finalizar {
      width: 100%;
      background-color: #ff6b35;
      color: white;
      border: none;
      padding: 1rem;
      font-size: 1.1rem;
      font-weight: 600;
      border-radius: 10px;
      cursor: pointer;
      margin-top: 1rem;
      transition: all 0.2s;
    }

    .btn-finalizar:hover {
      background-color: #e55a2b;
      transform: scale(1.02);
    }

    .btn-finalizar:disabled {
      background-color: #ccc;
      cursor: not-allowed;
      transform: none;
    }

    .cargando {
      text-align: center;
      padding: 2rem;
      color: #666;
    }

    .error-carrito {
      text-align: center;
      padding: 2rem;
      color: #dc3545;
      background-color: #f8d7da;
      border-radius: 8px;
      margin: 1rem 0;
    }

    /* Responsive */
    @media (max-width: 1200px) {
      .main-container {
        flex-direction: column;
      }
      .resumen-container {
        min-width: auto;
        max-width: none;
        position: static;
      }
    }

    @media (max-width: 900px) {
      .main-container {
        flex-direction: column;
      }
      body {
        padding: 1rem;
      }
    }
  </style>
</head>
<body>

  <div class="main-container">

    <!-- COLUMNA 1: DIRECCIÓN DE ENVÍO -->
    <div class="form-container" id="direccion-container">
      <div class="encabezado">
        <h2><i class="fas fa-map-marker-alt"></i> Dirección de Envío</h2>
      </div>

      <!-- Formulario de Dirección -->
      <!-- Este formulario envía a guardar_direccion.php -->
      <!-- TODOS los campos 'name' se han mantenido idénticos -->
      <form id="direccion-form" action="guardar_direccion.php" method="POST">
        <!-- Campo oculto para el ID de usuario -->
        <input type="hidden" name="id_usuario" value="<?php echo $id_usuario_sesion; ?>">

        <label for="nombre_direccion">Nombre de la dirección</label>
        <input type="text" id="nombre_direccion" name="nombre_direccion" placeholder="Ej: Casa, Trabajo" required>

        <label for="calle_numero">Calle y número</label>
        <input type="text" id="calle_numero" name="calle_numero" placeholder="Ej: Av. Mitre 1234" required>

        <label for="piso_depto">Piso / Depto (opcional)</label>
        <input type="text" id="piso_depto" name="piso_depto" placeholder="Ej: 2° B">

        <div class="form-row">
          <div>
            <label for="Ciudad">Ciudad</label>
            <input type="text" id="Ciudad" name="Ciudad" placeholder="Ej: Quilmes" required>
          </div>
          <div>
            <label for="Provincia">Provincia</label>
            <select id="Provincia" name="Provincia" required>
              <option value="">Seleccioná...</option>
              <option value="Buenos Aires">Buenos Aires</option>
              <option value="Córdoba">Córdoba</option>
              <option value="Santa Fe">Santa Fe</option>
              <option value="Mendoza">Mendoza</option>
              <option value="Entre Ríos">Entre Ríos</option>
              <!-- Agrega más provincias según sea necesario -->
            </select>
          </div>
        </div>

        <label for="Codigo_postal">Código Postal</label>
        <input type="text" id="Codigo_postal" name="Codigo_postal" placeholder="Ej: B1878" required>

        <label for="Referencia">Referencia adicional</label>
        <textarea id="Referencia" name="Referencia" rows="3" placeholder="Ej: Casa con portón blanco, dejar en portería"></textarea>

    </div>

    <!-- COLUMNA 2: MÉTODO DE PAGO -->
    <div class="form-container" id="pago-container">
      <div class="encabezado">
        <h2><i class="fas fa-credit-card"></i> Método de Pago</h2>
      </div>

      <!-- Formulario de Método de Pago -->
      <!-- Este formulario envía a guardarmetododepago.php -->
      <!-- Los campos 'name' coinciden con tu tabla SQL 'metodo_pago_usuario' -->
      <form id="pago-form" action="guardarmetododepago.php" method="POST" style="display: flex; flex-direction: column; gap: 1.2rem; height: 100%;">
  <div style="display: flex; flex-direction: column; gap: 1.2rem; flex: 1; background: none;">
          <!-- Campo oculto para el ID de usuario -->
          <input type="hidden" name="id_usuario" value="<?php echo $id_usuario_sesion; ?>">

          <label for="alias">Alias del método</label>
          <input type="text" id="alias" name="alias" placeholder="Ej: Mi Visa, Cuenta principal, Efectivo" required>

          <label for="tipo_metodo">Tipo de método</label>
          <select id="tipo_metodo" name="tipo_metodo" required>
            <option value="">Seleccioná un método...</option>
            <option value="Tarjeta de crédito">Tarjeta de crédito</option>
            <option value="Tarjeta de débito">Tarjeta de débito</option>
            <option value="Mercado Pago">Mercado Pago</option>
            <option value="Transferencia">Transferencia</option>
            <option value="Efectivo">Efectivo</option>
          </select>

          <!-- Campos dinámicos para Tarjeta -->
          <div class="campos-tarjeta" id="campos-tarjeta">
            <label for="nombre_titular">Nombre del titular</label>
            <input type="text" id="nombre_titular" name="nombre_titular" placeholder="Como figura en la tarjeta">

            <label for="numero_tarjeta">Número de tarjeta</label>
            <input type="text" id="numero_tarjeta" name="numero_tarjeta" placeholder="•••• •••• •••• ••••" autocomplete="off">

            <label for="vencimiento">Vencimiento</label>
            <input type="text" id="vencimiento" name="vencimiento" placeholder="MM/AAAA">
          </div>
        </div>
        <!-- Puedes agregar aquí un botón de guardar si lo necesitas -->
      </form>
    </div>

    <!-- COLUMNA 3: RESUMEN DEL PEDIDO -->
    <div class="resumen-container">
      <div class="resumen-header">
        <h3><i class="fas fa-shopping-cart"></i> Resumen del Pedido</h3>
      </div>
      <div class="resumen-content" id="resumen-content">
        <div class="cargando">Cargando productos...</div>
      </div>
    </div>

  </div>

  <div class="volver-container">
    <a href="/proyecto_supermercado/index.html"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Configurar carrito de prueba
      setupCarritoPrueba();
      
      // Cargar resumen del pedido
      setTimeout(cargarResumenPedido, 1000); // Dar tiempo para que se configure el carrito

      // Lógica del método de pago
      const tipoMetodoSelect = document.getElementById('tipo_metodo');
      const camposTarjetaContainer = document.getElementById('campos-tarjeta');
      
      // Inputs que son específicos de la tarjeta
      const inputsTarjeta = [
        document.getElementById('nombre_titular'),
        document.getElementById('numero_tarjeta'),
        document.getElementById('vencimiento')
      ];

      tipoMetodoSelect.addEventListener('change', function() {
        const valorSeleccionado = this.value;

        if (valorSeleccionado === 'Tarjeta de crédito' || valorSeleccionado === 'Tarjeta de débito') {
          // Mostrar campos de tarjeta
          camposTarjetaContainer.style.display = 'flex';
          
          // Hacerlos requeridos
          inputsTarjeta.forEach(input => {
            input.required = true;
          });

        } else {
          // Ocultar campos de tarjeta
          camposTarjetaContainer.style.display = 'none';
          
          // Quitar 'required' y limpiar valores
          inputsTarjeta.forEach(input => {
            input.required = false;
            input.value = ''; // Limpiar el campo al ocultar
          });
        }
      });
    });

    // Función para configurar carrito de prueba
    async function setupCarritoPrueba() {
      try {
        const response = await fetch('/proyecto_supermercado/direcciones/setup_carrito.php');
        const text = await response.text();
        console.log('Carrito configurado:', text);
      } catch (error) {
        console.error('Error configurando carrito:', error);
      }
    }

    // Funciones para el resumen del pedido
    async function cargarResumenPedido() {
      try {
        const response = await fetch('/proyecto_supermercado/carrito/obtener_carrito.php');
        const data = await response.json();
        
        if (data.success) {
          mostrarResumenPedido(data.productos);
        } else {
          mostrarErrorCarrito();
        }
      } catch (error) {
        console.error('Error al cargar el carrito:', error);
        mostrarErrorCarrito();
      }
    }

    function mostrarResumenPedido(productos) {
      const resumenContent = document.getElementById('resumen-content');
      
      if (!productos || productos.length === 0) {
        resumenContent.innerHTML = '<div class="error-carrito">No hay productos en el carrito</div>';
        return;
      }

      let subtotal = 0;
      let html = '';

      productos.forEach(producto => {
        const precioTotal = parseFloat(producto.precio || producto.precio_actual) * parseInt(producto.cantidad || producto.Cantidad);
        subtotal += precioTotal;
        
        html += `
          <div class="producto-item">
            <div class="producto-info">
              <div class="producto-nombre">${producto.nombre}</div>
              <div class="producto-cantidad">Cantidad: ${producto.cantidad || producto.Cantidad}</div>
            </div>
            <div class="producto-precio">$${precioTotal.toFixed(2)}</div>
          </div>
        `;
      });

      const envio = 0; // Envío gratis
      const total = subtotal + envio;

      html += `
        <div class="subtotal-row">
          <span>Subtotal:</span>
          <span>$${subtotal.toFixed(2)}</span>
        </div>
        <div class="envio-row">
          <span>Envío:</span>
          <span>GRATIS</span>
        </div>
        <div class="total-row">
          <span>Total:</span>
          <span>$${total.toFixed(2)}</span>
        </div>
        <button type="button" class="btn-finalizar" onclick="finalizarCompra()">
          Finalizar Compra
        </button>
      `;

      resumenContent.innerHTML = html;
    }

    function mostrarErrorCarrito() {
      const resumenContent = document.getElementById('resumen-content');
      resumenContent.innerHTML = '<div class="error-carrito">Error al cargar el carrito</div>';
    }

    function finalizarCompra() {
      // Validar formularios
      const direccionForm = document.getElementById('direccion-form');
      const pagoSelect = document.getElementById('tipo_metodo');
      
      if (!direccionForm.checkValidity()) {
        alert('Por favor completa todos los campos de dirección');
        direccionForm.reportValidity();
        return;
      }
      
      if (!pagoSelect.value) {
        alert('Por favor selecciona un método de pago');
        pagoSelect.focus();
        return;
      }

      // Procesar la compra
      procesarCompra();
    }

    async function procesarCompra() {
      const btnFinalizar = document.querySelector('.btn-finalizar');
      btnFinalizar.disabled = true;
      btnFinalizar.textContent = 'Procesando...';

      try {
        const formData = new FormData();
        
        // Obtener datos del formulario de dirección
        const direccionForm = document.getElementById('direccion-form');
        new FormData(direccionForm).forEach((value, key) => {
          formData.append(key, value);
        });

        // Obtener método de pago
        formData.append('tipo_metodo', document.getElementById('tipo_metodo').value);
        
        // Si es tarjeta, agregar datos de tarjeta
        const tipoMetodo = document.getElementById('tipo_metodo').value;
        if (tipoMetodo === 'Tarjeta de crédito' || tipoMetodo === 'Tarjeta de débito') {
          formData.append('nombre_titular', document.getElementById('nombre_titular').value);
          formData.append('numero_tarjeta', document.getElementById('numero_tarjeta').value);
          formData.append('vencimiento', document.getElementById('vencimiento').value);
        }

        const response = await fetch('procesar_compra.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          // Redirigir a página de éxito
          window.location.href = 'compra_exitosa.php';
        } else {
          alert('Error al procesar la compra: ' + result.message);
          btnFinalizar.disabled = false;
          btnFinalizar.textContent = 'Finalizar Compra';
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar la compra');
        btnFinalizar.disabled = false;
        btnFinalizar.textContent = 'Finalizar Compra';
      }
    }
  </script>

</body>
</html>
