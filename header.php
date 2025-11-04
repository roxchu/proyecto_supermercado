<?php
// header.php - Header modular para el proyecto supermercado
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Supermercado Online' ?></title>
    <link rel="stylesheet" href="<?= isset($base_path) ? $base_path : '' ?>styles.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <?php if (isset($additional_styles)): ?>
        <?= $additional_styles ?>
    <?php endif; ?>
</head>

<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <div class="logo">
                        <a href="<?= isset($base_path) ? $base_path : '' ?>index.html">
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
                 <input type="text" id="buscarProducto" placeholder="Buscar productos..." aria-label="Buscar productos">
                 <button type="button" id="btnBuscar"><i class="fas fa-search"></i></button>
             </div>

             <!-- Contenedor donde mostraremos los resultados -->
             <div id="resultadoBusqueda"></div>

                <div class="user-actions">
                    <a href="#" id="link-gestion" class="employee-only" style="display:none;" title="Gestión">
                        <i class="fas fa-truck-loading"></i>
                        <span>Gestión</span>
                    </a>
                    <a href="<?= isset($base_path) ? $base_path : '' ?>paneles/dashboard_admin.php" id="link-admin" class="admin-only" style="display:none;" title="Administración">
                        <i class="fas fa-tools"></i>
                        <span>Admin</span>
                    </a>

                    <a href="#" id="login-link" title="Iniciar sesión">
                        <i class="fas fa-user"></i>
                        <span>Iniciar sesión</span>
                    </a>

                    <div id="user-info" style="display:none;">
                        <span id="user-greeting"></span>
                        <a href="#" id="logout-link" title="Cerrar sesión">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>

                    <div class="cart" title="Mi Carrito">
                        <i class="fas fa-shopping-cart"></i>
                        <span id="cart-count">0</span>
                    </div>
                </div>
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
                <li><a href="<?= isset($base_path) ? $base_path : '' ?>index.html" class="side-link"><i class="fas fa-home"></i> Inicio</a></li>
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
    
    <!-- Overlay del menú lateral -->
    <div id="menu-overlay" class="menu-overlay"></div>

    <!-- Modal de Login/Registro -->
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

    <main class="main-content container">

    <!-- Test simple del menú lateral -->
    <script>
        // Test inmediato para el menú lateral
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔍 Testing menu lateral...');
            
            const btnCategorias = document.getElementById('btn-categorias');
            const sideMenu = document.getElementById('side-menu');
            const menuOverlay = document.getElementById('menu-overlay');
            const btnCloseMenu = document.getElementById('btn-close-menu');
            
            console.log('Elementos encontrados:');
            console.log('- btnCategorias:', btnCategorias ? '✅' : '❌');
            console.log('- sideMenu:', sideMenu ? '✅' : '❌');
            console.log('- menuOverlay:', menuOverlay ? '✅' : '❌');
            console.log('- btnCloseMenu:', btnCloseMenu ? '✅' : '❌');
            
            if (btnCategorias) {
                btnCategorias.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('🎯 Click en categorías detectado!');
                    
                    if (sideMenu) {
                        sideMenu.classList.add('open');
                        console.log('📂 Menú abierto');
                    }
                    if (menuOverlay) {
                        menuOverlay.classList.add('active');
                        console.log('🌚 Overlay activado');
                    }
                });
                console.log('✅ Event listener de categorías agregado');
            }
            
            // Función para cerrar menú
            function cerrarMenu() {
                console.log('❌ Cerrando menú...');
                if (sideMenu) sideMenu.classList.remove('open');
                if (menuOverlay) menuOverlay.classList.remove('active');
            }
            
            if (btnCloseMenu) {
                btnCloseMenu.addEventListener('click', cerrarMenu);
                console.log('✅ Event listener de cerrar agregado');
            }
            
            if (menuOverlay) {
                menuOverlay.addEventListener('click', cerrarMenu);
                console.log('✅ Event listener de overlay agregado');
            }
        });
    </script>