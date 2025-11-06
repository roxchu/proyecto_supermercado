<?php
session_start();
if (!isset($_SESSION['user_id']) && !isset($_SESSION['dni'])) {
    header("Location: ../login/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dirección de Envío - Supermercado Día</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --azul: #007bff;
      --azul-oscuro: #0056b3;
      --blanco: #ffffff;
      --gris: #f7f9fc;
      --texto: #333;
      --borde: #dcdcdc;
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: "Poppins", sans-serif;
      background-color: var(--gris);
      margin: 0;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
    }

    .contenedor {
      background-color: var(--blanco);
      width: 100%;
      max-width: 600px;
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      overflow: hidden;
      animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(20px);}
      to {opacity: 1; transform: translateY(0);}
    }

    .encabezado {
      background-color: var(--azul);
      color: var(--blanco);
      text-align: center;
      padding: 1.5rem;
    }

    .encabezado h2 {
      margin: 0;
      font-size: 1.8rem;
      font-weight: 600;
    }

    form {
      padding: 2rem;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    label {
      font-weight: 600;
      color: var(--texto);
    }

    input, select, textarea {
      padding: 0.8rem;
      border: 1px solid var(--borde);
      border-radius: 8px;
      font-size: 1rem;
      width: 100%;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    input:focus, select:focus, textarea:focus {
      border-color: var(--azul);
      box-shadow: 0 0 0 3px rgba(0,123,255,0.2);
      outline: none;
    }

    textarea {
      resize: none;
    }

    .form-row {
      display: flex;
      gap: 1rem;
    }

    .form-row > div {
      flex: 1;
    }

    .boton {
      background-color: var(--azul);
      color: var(--blanco);
      border: none;
      padding: 0.9rem;
      font-size: 1.1rem;
      border-radius: 10px;
      cursor: pointer;
      transition: background-color 0.2s, transform 0.1s;
    }

    .boton:hover {
      background-color: var(--azul-oscuro);
      transform: scale(1.02);
    }

    .volver {
      text-align: center;
      margin-top: 1rem;
    }

    .volver a {
      color: var(--azul);
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }

    .volver a:hover {
      color: var(--azul-oscuro);
    }
  </style>
</head>
<body>
  <div class="contenedor">
    <div class="encabezado">
      <h2><i class="fas fa-map-marker-alt"></i> Dirección de Envío</h2>
    </div>

    <form action="guardar_direccion.php" method="POST">
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
          </select>
        </div>
      </div>

      <label for="Codigo_postal">Código Postal</label>
      <input type="text" id="Codigo_postal" name="Codigo_postal" placeholder="Ej: B1878" required>

      <label for="Referencia">Referencia adicional</label>
      <textarea id="Referencia" name="Referencia" rows="3" placeholder="Ej: Casa con portón blanco, dejar en portería"></textarea>

      <button type="submit" class="boton"><i class="fas fa-save"></i> Guardar dirección</button>

      <div class="volver">
        <a href="../index.html"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
      </div>
    </form>
  </div>
</body>
</html>
