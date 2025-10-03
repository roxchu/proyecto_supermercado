<?php
include 'conexion.php'; 
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
   <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>caca</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'FH/header.php'; ?>
    <main>
        <section id="ofertas-carrusel">
            <div class="section-title banner-rojo">
                <h2 class="title-main">¡Especial Desayuno!</h2>
                <p class="title-subtitle">Mirá la Selección de Ofertas que tenemos para vos</p>
                <a href="#" class="ver-mas">Ver Más</a>
            </div>

            <div id="carrusel-dinamico-container">
                <p style="text-align:center; padding: 50px;">Cargando productos...</p>
            </div>
            
        </section>
    </main>
    <?php include 'FH/footer.php'; ?> 
    <script src="script.js"></script>
</body>
</html>