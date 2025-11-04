<?php
session_start();
// Tu comprobación de sesión original.
if (!isset($_SESSION['user_id']) && !isset($_SESSION['dni'])) {
    header("Location: ../login/login.php");
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
      gap: 2rem;
      width: 100%;
      max-width: 1200px; /* Aumentamos el ancho para dos columnas */
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
      flex: 1; /* Cada contenedor toma la mitad del espacio */
      display: flex;
      flex-direction: column;
    }

    .encabezado {
      background-color: var(--azul);
      color: var(--blanco);
      text-align: center;
      padding: 1.5rem;
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
      gap: 1.2rem; /* Aumentamos el espacio entre campos */
      height: 100%; /* Hacemos que el form ocupe el espacio */
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

    /* Responsive */
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
      <form action="guardar_direccion.php" method="POST">
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
      <form action="guardarmetododepago.php" method="POST">
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

  </div>

  <div class="volver-container">
    <a href="../index.html"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
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
  </script>

</body>
</html>
