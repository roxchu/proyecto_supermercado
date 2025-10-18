// script.js - Gestión del supermercado online

// ========================================
// VARIABLES GLOBALES
// ========================================
let carritoItems = [];
let usuarioActual = null;

// ========================================
// MENÚ LATERAL (CATEGORÍAS)
// ========================================
const btnCategorias = document.getElementById('btn-categorias');
const btnCloseMenu = document.getElementById('btn-close-menu');
const sideMenu = document.getElementById('side-menu');
const menuOverlay = document.getElementById('menu-overlay');

btnCategorias.addEventListener('click', () => {
    sideMenu.classList.add('open');
    menuOverlay.classList.add('active');
    sideMenu.setAttribute('aria-hidden', 'false');
    btnCategorias.setAttribute('aria-expanded', 'true');
});

function cerrarMenu() {
    sideMenu.classList.remove('open');
    menuOverlay.classList.remove('active');
    sideMenu.setAttribute('aria-hidden', 'true');
    btnCategorias.setAttribute('aria-expanded', 'false');
}

btnCloseMenu.addEventListener('click', cerrarMenu);
menuOverlay.addEventListener('click', cerrarMenu);

// Cerrar menú al hacer clic en un enlace
document.querySelectorAll('.side-link').forEach(link => {
    link.addEventListener('click', cerrarMenu);
});

// ========================================
// MODAL DE LOGIN
// ========================================
const loginModal = document.getElementById('loginModal');
const loginLink = document.getElementById('login-link');
const closeBtn = document.querySelector('.close-btn');
const loginForm = document.getElementById('login-form-dni');
const loginMessage = document.getElementById('login-message');

loginLink.addEventListener('click', (e) => {
    e.preventDefault();
    loginModal.style.display = 'block';
});

closeBtn.addEventListener('click', () => {
    loginModal.style.display = 'none';
    loginMessage.textContent = '';
});

window.addEventListener('click', (e) => {
    if (e.target === loginModal) {
        loginModal.style.display = 'none';
        loginMessage.textContent = '';
    }
});

// ========================================
// PROCESAR LOGIN (Con tu login.php existente)
// ========================================
loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const dni = document.getElementById('dni').value.trim();
    
    if (!dni) {
        loginMessage.textContent = 'Por favor ingresa tu DNI';
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('dni', dni);
        
        const response = await fetch('login.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Guardar datos del usuario
            usuarioActual = {
                nombre: data.nombre,
                rol: data.rol
            };
            
            actualizarInterfazUsuario();
            loginModal.style.display = 'none';
            loginMessage.textContent = '';
            document.getElementById('dni').value = '';
            
            // Mostrar mensaje de bienvenida
            mostrarNotificacion(`¡Bienvenido, ${data.nombre}!`, 'success');
        } else {
            loginMessage.textContent = data.message || 'Error al iniciar sesión';
        }
    } catch (error) {
        console.error('Error:', error);
        loginMessage.textContent = 'Error de conexión. Intenta nuevamente.';
    }
});

// ========================================
// ACTUALIZAR INTERFAZ SEGÚN USUARIO
// ========================================
function actualizarInterfazUsuario() {
    const loginLink = document.getElementById('login-link');
    const userInfo = document.getElementById('user-info');
    const userGreeting = document.getElementById('user-greeting');
    
    if (usuarioActual) {
        // Ocultar link de login y mostrar info de usuario
        loginLink.style.display = 'none';
        userInfo.style.display = 'flex';
        userGreeting.innerHTML = `<i class="fas fa-user"></i> Hola, ${usuarioActual.nombre}`;
        
        // Mostrar/ocultar elementos según rol
        const employeeElements = document.querySelectorAll('.employee-only');
        const adminElements = document.querySelectorAll('.admin-only');
        
        if (usuarioActual.rol === 'admin') {
            // Admin ve todo
            employeeElements.forEach(el => el.style.display = 'block');
            adminElements.forEach(el => el.style.display = 'block');
        } else if (usuarioActual.rol === 'employee') {
            // Employee ve solo sus opciones
            employeeElements.forEach(el => el.style.display = 'block');
            adminElements.forEach(el => el.style.display = 'none');
        } else {
            // Client (o cualquier otro rol) no ve opciones de gestión
            employeeElements.forEach(el => el.style.display = 'none');
            adminElements.forEach(el => el.style.display = 'none');
        }
    } else {
        // No hay sesión: mostrar link de login
        loginLink.style.display = 'inline';
        userInfo.style.display = 'none';
        
        // Ocultar todos los elementos restringidos
        document.querySelectorAll('.employee-only, .admin-only').forEach(el => {
            el.style.display = 'none';
        });
    }
}

// ========================================
// CERRAR SESIÓN (Redirige a tu logout.php)
// ========================================
document.getElementById('logout-link').addEventListener('click', (e) => {
    e.preventDefault();
    
    // Tu logout.php hace redirect automático a index.html
    window.location.href = 'logout.php';
});

// ========================================
// VERIFICAR SESIÓN AL CARGAR (AJAX)
// ========================================
async function verificarSesion() {
    try {
        const response = await fetch('check_session.php');
        const data = await response.json();
        
        if (data.logged_in) {
            usuarioActual = {
                nombre: data.nombre,
                rol: data.rol
            };
            actualizarInterfazUsuario();
        } else {
            usuarioActual = null;
            actualizarInterfazUsuario();
        }
    } catch (error) {
        console.error('Error al verificar sesión:', error);
        usuarioActual = null;
        actualizarInterfazUsuario();
    }
}

// ========================================
// CARGAR PRODUCTOS DESTACADOS
// ========================================
async function cargarProductos() {
    const container = document.getElementById('carrusel-dinamico-container');
    
    try {
        const response = await fetch('productos.php');
        const html = await response.text();
        container.innerHTML = html;
        
        // Agregar event listeners a los botones de agregar
        agregarEventListenersProductos();
        
    } catch (error) {
        console.error('Error al cargar productos:', error);
        container.innerHTML = `
            <div style="padding:40px;text-align:center;color:#d32f2f;">
                <i class="fas fa-exclamation-circle" style="font-size:3em;margin-bottom:10px;"></i>
                <p>Error al cargar los productos</p>
                <p style="font-size:0.9em;">${error.message}</p>
            </div>
        `;
    }
}

// ========================================
// GESTIÓN DEL CARRITO
// ========================================
function agregarEventListenersProductos() {
    document.querySelectorAll('.boton-agregar').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const nombre = this.dataset.nombre;
            const precio = parseFloat(this.dataset.precio);
            
            agregarAlCarrito(id, nombre, precio);
        });
    });
    
    // Event listener para favoritos
    document.querySelectorAll('.btn-favorito').forEach(btn => {
        btn.addEventListener('click', function() {
            this.querySelector('i').classList.toggle('far');
            this.querySelector('i').classList.toggle('fas');
            mostrarNotificacion('Producto agregado a favoritos', 'info');
        });
    });
}

function agregarAlCarrito(id, nombre, precio) {
    const itemExistente = carritoItems.find(item => item.id === id);
    
    if (itemExistente) {
        itemExistente.cantidad++;
    } else {
        carritoItems.push({
            id: id,
            nombre: nombre,
            precio: precio,
            cantidad: 1
        });
    }
    
    actualizarContadorCarrito();
    mostrarNotificacion(`${nombre} agregado al carrito`, 'success');
}

function actualizarContadorCarrito() {
    const contador = document.getElementById('cart-count');
    const total = carritoItems.reduce((sum, item) => sum + item.cantidad, 0);
    contador.textContent = total;
}

// ========================================
// NOTIFICACIONES
// ========================================
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear elemento de notificación
    const notif = document.createElement('div');
    notif.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: ${tipo === 'success' ? '#4CAF50' : tipo === 'error' ? '#f44336' : '#2196F3'};
        color: white;
        padding: 15px 20px;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    notif.textContent = mensaje;
    
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// Agregar estilos para las animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(style);

// ========================================
// INICIALIZACIÓN
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    verificarSesion();
    cargarProductos();
});