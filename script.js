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
// MODAL DE LOGIN Y REGISTRO (Coordinación con index.html)
// ========================================
const loginModal = document.getElementById('loginModal');
const loginLink = document.getElementById('login-link');
const logoutLink = document.getElementById('logout-link');
const closeModal = loginModal.querySelector('.close-btn');

// Formularios
const loginForm = document.getElementById('login-form-dni');
const registerForm = document.getElementById('register-form');
const loginMessage = document.getElementById('login-message');
const registerMessage = document.getElementById('register-message');

// Botones de cambio de vista
const showRegisterLink = document.getElementById('show-register');
const showLoginLink = document.getElementById('show-login');

// Mostrar modal
loginLink.addEventListener('click', (e) => {
    e.preventDefault();
    loginModal.style.display = 'block';
    // Asegurar que volvemos al login por defecto
    loginForm.style.display = 'block';
    registerForm.style.display = 'none';
    document.getElementById('modal-title').textContent = 'Iniciar Sesión';
    loginMessage.textContent = '';
    registerMessage.textContent = '';
});

// Cerrar modal
closeModal.addEventListener('click', () => {
    loginModal.style.display = 'none';
});
window.addEventListener('click', (event) => {
    if (event.target === loginModal) {
        loginModal.style.display = 'none';
    }
});


// ----------------------------------------
// LÓGICA DE CAMBIO DE FORMULARIO
// ----------------------------------------

// Cambiar a Registro
showRegisterLink.addEventListener('click', (e) => {
    e.preventDefault();
    loginForm.style.display = 'none';
    registerForm.style.display = 'block';
    document.getElementById('modal-title').textContent = 'Crear Cuenta';
    loginMessage.textContent = '';
});

// Cambiar a Login
showLoginLink.addEventListener('click', (e) => {
    e.preventDefault();
    registerForm.style.display = 'none';
    loginForm.style.display = 'block';
    document.getElementById('modal-title').textContent = 'Iniciar Sesión';
    registerMessage.textContent = '';
});


// ----------------------------------------
// FUNCIÓN NUEVA: Sugerir Registro
// ----------------------------------------
/**
 * Cambia el modal de login a registro y precarga el DNI.
 * @param {string} dni - El DNI que el usuario intentó usar.
 * @param {HTMLElement} messageEl - El elemento <p> del mensaje del login.
 */
function suggestRegistration(dni, messageEl) {
    // 1. Cambia de formulario
    loginForm.style.display = 'none';
    registerForm.style.display = 'block';
    document.getElementById('modal-title').textContent = 'Crear Cuenta';
    
    // 2. Precarga el DNI en el nuevo formulario de registro
    // Asegúrate de que el name del input de registro sea 'dni'
    document.getElementById('reg-dni').value = dni; 
    
    // 3. Muestra un mensaje útil
    messageEl.textContent = '¡Regístrate! Tu DNI no fue encontrado.';
    messageEl.style.color = '#FFA500'; // Naranja
}


// ----------------------------------------
// A. LÓGICA DE LOGIN (fetch a login.php)
// ----------------------------------------
loginForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const dniInput = document.getElementById('dni');
    const dni = dniInput.value;
    
    const formData = new FormData(this); 

    loginMessage.textContent = 'Verificando...';
    loginMessage.style.color = '#333';

    fetch('login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loginMessage.textContent = data.message;
            loginMessage.style.color = 'green';
            mostrarNotificacion(`¡Bienvenido, ${data.nombre}!`, 'success');
            
            setTimeout(() => {
                loginModal.style.display = 'none';
                updateUI(data.nombre, data.rol);
            }, 500);
            
        } else {
            // Manejo de error: Si el código es 'USER_NOT_FOUND', sugerimos el registro
            if (data.code === 'USER_NOT_FOUND') {
                suggestRegistration(dni, loginMessage); // Llama a la nueva función
            } else {
                // Error general 
                loginMessage.textContent = data.message || 'Error desconocido al iniciar sesión.';
                loginMessage.style.color = 'red';
                console.error("Error de login:", data); 
            }
        }
    })
    .catch(error => {
        loginMessage.textContent = 'Error de conexión con el servidor.';
        loginMessage.style.color = 'red';
        console.error('Error de fetch:', error);
    });
});


// ----------------------------------------
// B. LÓGICA DE REGISTRO (fetch a registro.php)
// ----------------------------------------
registerForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // FormData envía los campos 'dni', 'nombre', 'correo' (asumiendo que los name="" están correctos en el HTML)
    const formData = new FormData(this); 

    registerMessage.textContent = 'Registrando...';
    registerMessage.style.color = '#333';

    fetch('registro.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            registerMessage.textContent = data.message;
            registerMessage.style.color = 'green';
            mostrarNotificacion(`¡Registro exitoso! Hola, ${data.nombre}.`, 'success');
            
            // Cierra el modal y actualiza la UI
            setTimeout(() => {
                loginModal.style.display = 'none';
                updateUI(data.nombre, data.rol);
            }, 1000);
            
        } else {
            // Muestra el error que viene de registro.php
            registerMessage.textContent = data.message;
            registerMessage.style.color = 'red';
            // Muestra el error de la DB en la consola si el registro falla (campo 'debug')
            console.error("Error de registro (DEBUG):", data.debug); 
        }
    })
    .catch(error => {
        registerMessage.textContent = 'Error de conexión con el servidor.';
        registerMessage.style.color = 'red';
        console.error('Error de fetch:', error);
    });
});


// ----------------------------------------
// C. CIERRE DE SESIÓN (fetch a logout.php)
// ----------------------------------------
logoutLink.addEventListener('click', (e) => {
    e.preventDefault();
    fetch('logout.php') // Debes tener un archivo logout.php que destruya la sesión
        .then(() => {
            mostrarNotificacion('Sesión cerrada correctamente.', 'info');
            // Recarga la página para restaurar la UI
            location.reload(); 
        })
        .catch(error => {
            console.error('Error al cerrar sesión:', error);
        });
});


// ----------------------------------------
// D. FUNCIONES DE UI Y SESIÓN (Compartidas)
// ----------------------------------------

/**
 * Actualiza los elementos de la interfaz de usuario (UI) según el rol.
 * @param {string} nombre - Nombre del usuario.
 * @param {string} rol - Rol del usuario ('admin', 'employee', 'client').
 */
function updateUI(nombre, rol) {
    const userInfo = document.getElementById('user-info');
    const loginLink = document.getElementById('login-link');
    const userGreeting = document.getElementById('user-greeting');
    // Elementos del menú y header que se ocultan/muestran
    const employeeElements = document.querySelectorAll('.employee-only');
    const adminElements = document.querySelectorAll('.admin-only');

    loginLink.style.display = 'none';
    userInfo.style.display = 'flex'; // Mostrar el contenedor de usuario
    userGreeting.innerHTML = `<i class="fas fa-user"></i> Hola, ${nombre}`;

    // Lógica de visibilidad por rol
    const isEmployee = rol === 'empleado' || rol === 'admin';
    const isAdmin = rol === 'admin';
    
    // Ocultar todos los elementos de acceso restringido
    document.querySelectorAll('.employee-only, .admin-only').forEach(el => el.style.display = 'none');

    // Mostrar lo que corresponda en el header
    if (isEmployee) {
        // Enlaces de gestión en el header
        document.getElementById('link-gestion').style.display = 'flex';
    }
    if (isAdmin) {
        // Enlaces de administración en el header
        document.getElementById('link-admin').style.display = 'flex';
    }
    
    // Mostrar lo que corresponda en el menú lateral (SIDE MENU)
    document.querySelectorAll('#side-menu .employee-only').forEach(el => el.style.display = isEmployee ? 'list-item' : 'none');
    document.querySelectorAll('#side-menu .admin-only').forEach(el => el.style.display = isAdmin ? 'list-item' : 'none');
}

/**
 * Verifica la sesión al cargar la página (fetch a check_session.php).
 */
function checkSession() {
    fetch('check_session.php')
        .then(response => response.json())
        .then(data => {
            if (data.logged_in) {
                // Si hay sesión, actualiza la UI
                updateUI(data.nombre, data.rol);
            } else {
                // Si no hay sesión, asegura que la UI esté en estado de no logueado
                document.getElementById('login-link').style.display = 'block';
                document.getElementById('user-info').style.display = 'none';
                document.querySelectorAll('.employee-only, .admin-only').forEach(el => el.style.display = 'none');
            }
        })
        .catch(error => {
            console.error('Error al verificar la sesión:', error);
            // Fallback en caso de error de conexión
            document.getElementById('login-link').style.display = 'block';
            document.getElementById('user-info').style.display = 'none';
        });
}


// ----------------------------------------
// E. NOTIFICACIONES
// ----------------------------------------
function mostrarNotificacion(mensaje, tipo = 'info') {
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
        transition: transform 0.3s ease-out, opacity 0.3s ease-out;
        transform: translateX(100%);
        opacity: 0;
    `;
    notif.textContent = mensaje;
    
    document.body.appendChild(notif);

    // Animación de entrada
    setTimeout(() => {
        notif.style.transform = 'translateX(0)';
        notif.style.opacity = '1';
    }, 10); // Pequeño retraso para asegurar el inicio de la transición

    // Animación de salida y eliminación
    setTimeout(() => {
        notif.style.transform = 'translateX(100%)';
        notif.style.opacity = '0';
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}


// ----------------------------------------
// F. LÓGICA DE CARGA DE PRODUCTOS (¡NUEVA FUNCIÓN AÑADIDA!)
// ----------------------------------------

function cargarProductos() {
    const contenedor = document.getElementById('carrusel-dinamico-container');

    // El fetch llama al PHP que genera el HTML de los productos.
    fetch('productos.php') 
        .then(response => {
            if (!response.ok) {
                // Si hay un error HTTP (ej. 404 Not Found), lo indicamos
                throw new Error(`Error al obtener productos: ${response.statusText}`);
            }
            // productos.php devuelve HTML, por lo tanto, usamos .text()
            return response.text(); 
        })
        .then(html => {
            // Reemplazamos el contenido de "Cargando productos..." por el HTML real.
            contenedor.innerHTML = html;
        })
        .catch(error => {
            // Muestra un mensaje de error si la comunicación falla
            contenedor.innerHTML = 
                `<div style="text-align:center; color: red; padding: 30px;">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Fallo de conexión al cargar productos. Por favor, revise la consola.
                </div>`;
            console.error('Error al cargar productos:', error);
        });
}
// script.js (Añadir esta nueva sección antes de INICIALIZACIÓN)

// script.js (CORRECCIÓN PARA EL CARRITO)

// ----------------------------------------
// G. LÓGICA DE CARRITO (Delegación de Eventos CORREGIDA)
// ----------------------------------------

/**
 * Función que maneja el clic en cualquier botón de agregar al carrito.
 * Ahora busca la clase .btn-add usada en productos.php
 */
function manejarClickCarrito(event) {
    // 1. Verificar si el elemento clickeado o su padre es el botón con la clase 'btn-add'
    const boton = event.target.closest('.btn-add'); // ⬅️ CAMBIO CRÍTICO: Buscar '.btn-add'
    
    if (boton) {
        event.preventDefault(); 
        
        // 2. Obtener la información del producto
        // ASUMIMOS que el ID del producto está en el atributo 'id' del botón (o 'data-id')
        const productoId = boton.getAttribute('id') || boton.getAttribute('data-id');
        
        // 3. Lógica de agregar producto
        if (!productoId) {
            mostrarNotificacion("Error: ID de producto no encontrado en el botón.", 'error');
            console.error("Botón sin ID. Revise productos.php");
            return;
        }

        // --- SIMULACIÓN DE ÉXITO ---
        // Si llegamos aquí, el botón funcionó y el ID se capturó.
        mostrarNotificacion(`Agregando Producto ID: ${productoId} al carrito.`, 'success');
        
        // Aquí iría tu lógica real de fetch a la API:
        // fetch('agregar_carrito.php', {
        //     method: 'POST',
        //     body: JSON.stringify({ product_id: productoId, cantidad: 1 }),
        //     headers: { 'Content-Type': 'application/json' }
        // })
        // .then(...)
    }
}

// 4. Asegúrate de que el listener esté activo (debe ir una sola vez)
document.addEventListener('click', manejarClickCarrito);

// ========================================
// INICIALIZACIÓN
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    checkSession();
    cargarProductos(); 
});
// ... (todo tu código existente sin tocar hasta antes de INICIALIZACIÓN)




// ==================================================
// script.js - Lógica general + carrito
// ==================================================

// Variables globales
let usuarioActual = null;

// ========================================
// 🔹 Menú lateral
// ========================================
const btnCategorias = document.getElementById('btn-categorias');
const btnCloseMenu = document.getElementById('btn-close-menu');
const sideMenu = document.getElementById('side-menu');
const menuOverlay = document.getElementById('menu-overlay');

btnCategorias.addEventListener('click', () => {
    sideMenu.classList.add('open');
    menuOverlay.classList.add('active');
});
btnCloseMenu.addEventListener('click', cerrarMenu);
menuOverlay.addEventListener('click', cerrarMenu);
function cerrarMenu() {
    sideMenu.classList.remove('open');
    menuOverlay.classList.remove('active');
}

// ========================================
// 🔹 Modal Login / Registro
// ========================================
const loginModal = document.getElementById('loginModal');
const loginLink = document.getElementById('login-link');
const logoutLink = document.getElementById('logout-link');
const closeModal = loginModal.querySelector('.close-btn');
const loginForm = document.getElementById('login-form-dni');
const registerForm = document.getElementById('register-form');
const loginMessage = document.getElementById('login-message');
const registerMessage = document.getElementById('register-message');
const showRegisterLink = document.getElementById('show-register');
const showLoginLink = document.getElementById('show-login');

loginLink.addEventListener('click', (e) => {
    e.preventDefault();
    loginModal.style.display = 'block';
    loginForm.style.display = 'block';
    registerForm.style.display = 'none';
});
closeModal.addEventListener('click', () => (loginModal.style.display = 'none'));
window.addEventListener('click', (e) => { if (e.target === loginModal) loginModal.style.display = 'none'; });
showRegisterLink.addEventListener('click', (e) => {
    e.preventDefault();
    loginForm.style.display = 'none';
    registerForm.style.display = 'block';
});
showLoginLink.addEventListener('click', (e) => {
    e.preventDefault();
    registerForm.style.display = 'none';
    loginForm.style.display = 'block';
});

// ========================================
// 🔹 Login / Registro / Logout
// ========================================
loginForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const formData = new FormData(loginForm);
    fetch('login.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                updateUI(data.nombre, data.rol);
                loginModal.style.display = 'none';
                mostrarNotificacion(`Bienvenido ${data.nombre}`, 'success');
            } else {
                loginMessage.textContent = data.message;
                loginMessage.style.color = 'red';
            }
        })
        .catch(() => mostrarNotificacion('Error de conexión', 'error'));
});

registerForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const formData = new FormData(registerForm);
    fetch('registro.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                updateUI(data.nombre, data.rol);
                loginModal.style.display = 'none';
                mostrarNotificacion(`Cuenta creada, ${data.nombre}`, 'success');
            } else {
                registerMessage.textContent = data.message;
                registerMessage.style.color = 'red';
            }
        });
});

logoutLink.addEventListener('click', (e) => {
    e.preventDefault();
    fetch('logout.php').then(() => location.reload());
});

// ========================================
// 🔹 Sesión
// ========================================
function checkSession() {
    fetch('check_session.php')
        .then(r => r.json())
        .then(data => {
            if (data.logged_in) updateUI(data.nombre, data.rol);
            else resetUI();
        });
}

function updateUI(nombre, rol) {
    document.getElementById('login-link').style.display = 'none';
    const userInfo = document.getElementById('user-info');
    userInfo.style.display = 'flex';
    document.getElementById('user-greeting').innerHTML = `<i class="fas fa-user"></i> Hola, ${nombre}`;
    document.getElementById('link-gestion').style.display = (rol === 'empleado' || rol === 'admin') ? 'flex' : 'none';
    document.getElementById('link-admin').style.display = (rol === 'admin') ? 'flex' : 'none';
}
function resetUI() {
    document.getElementById('login-link').style.display = 'block';
    document.getElementById('user-info').style.display = 'none';
}

// ========================================
// 🔹 Cargar productos dinámicos
// ========================================
function cargarProductos() {
    const cont = document.getElementById('carrusel-dinamico-container');
    fetch('productos.php')
        .then(r => r.text())
        .then(html => (cont.innerHTML = html))
        .catch(() => {
            cont.innerHTML = '<p style="color:red;text-align:center">Error al cargar productos</p>';
        });
}

// ========================================
// 🔹 Carrito
// ========================================
function actualizarContadorCarrito() {
    fetch('obtener_carrito.php')
        .then(r => r.json())
        .then(d => {
            document.getElementById('cart-count').textContent = d.total_items || 0;
        });
}

document.addEventListener('click', (e) => {
    const btn = e.target.closest('.boton-agregar');
    if (!btn) return;
    const id = btn.dataset.id;
    const nombre = btn.dataset.nombre;

    fetch('agregar_carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_producto: id, cantidad: 1 })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacion(`${nombre} agregado al carrito ✅`, 'success');
                actualizarContadorCarrito();
            } else if (data.error === 'NOT_LOGGED_IN') {
                mostrarNotificacion('Iniciá sesión para agregar productos 🧑‍💻', 'error');
                loginModal.style.display = 'block';
            } else {
                mostrarNotificacion(data.message || 'Error al agregar producto', 'error');
            }
        })
        .catch(() => mostrarNotificacion('Error de conexión', 'error'));
});

// ========================================
// 🔹 Notificaciones
// ========================================
function mostrarNotificacion(mensaje, tipo = 'info') {
    const n = document.createElement('div');
    n.className = 'notif';
    n.textContent = mensaje;
    n.style.cssText = `
        position: fixed; top: 80px; right: 20px;
        background: ${tipo === 'success' ? '#4caf50' : tipo === 'error' ? '#f44336' : '#2196f3'};
        color: white; padding: 12px 18px; border-radius: 6px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3); z-index: 9999;
        transition: opacity .4s; opacity: 1;
    `;
    document.body.appendChild(n);
    setTimeout(() => (n.style.opacity = '0'), 2800);
    setTimeout(() => n.remove(), 3200);
}

// ========================================
// 🔹 Inicialización
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    checkSession();
    cargarProductos();
    actualizarContadorCarrito();
});
