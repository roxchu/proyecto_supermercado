// ========================================
// script.js — Gestión del supermercado online
// ========================================

// 🔹 VARIABLES GLOBALES
let usuarioActual = null;

// ========================================
// 🔹 MENÚ LATERAL (CATEGORÍAS)
// ========================================
const btnCategorias = document.getElementById('btn-categorias');
const btnCloseMenu = document.getElementById('btn-close-menu');
const sideMenu = document.getElementById('side-menu');
const menuOverlay = document.getElementById('menu-overlay');

function cerrarMenu() {
    sideMenu.classList.remove('open');
    menuOverlay.classList.remove('active');
}

btnCategorias?.addEventListener('click', () => {
    sideMenu.classList.add('open');
    menuOverlay.classList.add('active');
});
btnCloseMenu?.addEventListener('click', cerrarMenu);
menuOverlay?.addEventListener('click', cerrarMenu);

// ========================================
// 🔹 MODAL LOGIN / REGISTRO
// ========================================
const loginModal = document.getElementById('loginModal');
const loginLink = document.getElementById('login-link');
const logoutLink = document.getElementById('logout-link');
const closeModal = loginModal?.querySelector('.close-btn');
const loginForm = document.getElementById('login-form-dni');
const registerForm = document.getElementById('register-form');
const loginMessage = document.getElementById('login-message');
const registerMessage = document.getElementById('register-message');
const showRegisterLink = document.getElementById('show-register');
const showLoginLink = document.getElementById('show-login');

// Mostrar modal de login
loginLink?.addEventListener('click', (e) => {
    e.preventDefault();
    loginModal.style.display = 'block';
    loginForm.style.display = 'block';
    registerForm.style.display = 'none';
    document.getElementById('modal-title').textContent = 'Iniciar Sesión';
});

// Cerrar modal
closeModal?.addEventListener('click', () => (loginModal.style.display = 'none'));
window.addEventListener('click', (e) => {
    if (e.target === loginModal) loginModal.style.display = 'none';
});

// Cambiar a formulario de registro
showRegisterLink?.addEventListener('click', (e) => {
    e.preventDefault();
    loginForm.style.display = 'none';
    registerForm.style.display = 'block';
    document.getElementById('modal-title').textContent = 'Crear Cuenta';
});

// Cambiar a formulario de login
showLoginLink?.addEventListener('click', (e) => {
    e.preventDefault();
    registerForm.style.display = 'none';
    loginForm.style.display = 'block';
    document.getElementById('modal-title').textContent = 'Iniciar Sesión';
});

// ========================================
// 🔹 LOGIN / REGISTRO / LOGOUT
// ========================================
loginForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    const formData = new FormData(loginForm);
    fetch('login/login.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                usuarioActual = { nombre: data.nombre, rol: data.rol };
                updateUI(data.nombre, data.rol);
                loginModal.style.display = 'none';
                mostrarNotificacion(`Bienvenido ${data.nombre}`, 'success');
            } else {
                loginMessage.textContent = data.message || 'Error al iniciar sesión';
                loginMessage.style.color = 'red';
            }
        })
        .catch(() => mostrarNotificacion('Error de conexión', 'error'));
});

registerForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    const formData = new FormData(registerForm);
    fetch('login/registro.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                usuarioActual = { nombre: data.nombre, rol: data.rol };
                updateUI(data.nombre, data.rol);
                loginModal.style.display = 'none';
                mostrarNotificacion(`Cuenta creada, ${data.nombre}`, 'success');
            } else {
                registerMessage.textContent = data.message;
                registerMessage.style.color = 'red';
            }
        })
        .catch(() => mostrarNotificacion('Error de conexión', 'error'));
});

logoutLink?.addEventListener('click', (e) => {
    e.preventDefault();
    fetch('login/logout.php').then(() => {
        usuarioActual = null;
        mostrarNotificacion('Sesión cerrada correctamente.', 'info');
        resetUI();
    });
});

// ========================================
// 🔹 SESIÓN
// ========================================
function checkSession() {
    fetch('login/check_session.php')
        .then(r => r.json())
        .then(data => {
            if (data.logged_in) {
                usuarioActual = { nombre: data.nombre, rol: data.rol };
                updateUI(data.nombre, data.rol);
            } else resetUI();
        })
        .catch(() => resetUI());
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
// 🔹 CARRITO
// ========================================
function actualizarContadorCarrito() {
    fetch('carrito/obtener_carrito.php')
        .then(r => r.json())
        .then(d => {
            document.getElementById('cart-count').textContent = d.total_items || 0;
        })
        .catch(() => {
            document.getElementById('cart-count').textContent = 0;
        });
}

// Click en "Agregar al carrito"
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.boton-agregar');
    if (!btn) return;

    // Bloquear si no hay usuario logueado
    if (!usuarioActual) {
        mostrarNotificacion('Debes iniciar sesión para agregar productos 🧑‍💻', 'error');
        loginModal.style.display = 'block';
        return;
    }

    const id = btn.dataset.id;
    const nombre = btn.dataset.nombre;

    fetch('carrito/agregar_carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_producto: id, cantidad: 1 })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacion(`${nombre} agregado al carrito ✅`, 'success');
                actualizarContadorCarrito();
            } else {
                mostrarNotificacion(data.message || 'Error al agregar producto', 'error');
            }
        })
        .catch(() => mostrarNotificacion('Error de conexión', 'error'));
});

// ========================================
// 🔹 CARRUSEL + CARGA DE PRODUCTOS
// ========================================

function inicializarCarrusel() {
    const track = document.querySelector('.carousel-track');
    const btnPrev = document.querySelector('.prev');
    const btnNext = document.querySelector('.next');

    if (!track) {
        console.warn('Carousel: .carousel-track no encontrado.');
        return;
    }

    // Asegurarse de que el track permita scroll horizontal
    track.style.overflowX = track.style.overflowX || 'auto';
    track.style.scrollBehavior = track.style.scrollBehavior || 'smooth';

    // Obtener ancho de paso (primera tarjeta) — fallback a 270
    const firstCard = track.querySelector('.producto-card') || track.firstElementChild;
    const cardWidth = firstCard ? Math.ceil(firstCard.getBoundingClientRect().width + parseFloat(getComputedStyle(track).gap || 0)) : 270;
    const step = cardWidth || 270;

    // Helper para quitar listeners previos (evita duplicados)
    function clearHandler(el, propName) {
        if (!el) return;
        const prev = el.__carouselHandler;
        if (prev && typeof prev === 'function') {
            el.removeEventListener('click', prev);
        }
    }

    // Attach handlers using named functions saved on the element
    if (btnNext) {
        clearHandler(btnNext, '__carouselNext');
        const handlerNext = () => {
            track.scrollBy({ left: step, behavior: 'smooth' });
        };
        btnNext.addEventListener('click', handlerNext);
        btnNext.__carouselHandler = handlerNext;
    } else {
        console.warn('Carousel: botón .next no encontrado.');
    }

    if (btnPrev) {
        clearHandler(btnPrev, '__carouselPrev');
        const handlerPrev = () => {
            track.scrollBy({ left: -step, behavior: 'smooth' });
        };
        btnPrev.addEventListener('click', handlerPrev);
        btnPrev.__carouselHandler = handlerPrev;
    } else {
        console.warn('Carousel: botón .prev no encontrado.');
    }

    // Accessibility: permitir teclado en los botones
    [btnPrev, btnNext].forEach(btn => {
        if (!btn) return;
        btn.setAttribute('tabindex', '0');
        btn.style.cursor = 'pointer';
        btn.style.zIndex = btn.style.zIndex || '20'; // asegúrate no tapar con overlay
        btn.addEventListener('keyup', (e) => {
            if (e.key === 'Enter' || e.key === ' ') btn.click();
        });
    });

    // Opcional: si se llega al final, deshabilitar el botón next, y similar para prev.
    function actualizarEstadoBotones() {
        if (!track) return;
        if (btnNext) btnNext.disabled = (track.scrollLeft + track.clientWidth >= track.scrollWidth - 1);
        if (btnPrev) btnPrev.disabled = (track.scrollLeft <= 1);
    }
    track.addEventListener('scroll', () => {
        // Debounce mínimo
        window.requestAnimationFrame(actualizarEstadoBotones);
    });
    actualizarEstadoBotones();
}

/**
 * Carga productos desde productos.php y asegura inicializar el carrusel.
 * Además usa MutationObserver en caso de que productos.php inyecte nodos asíncronamente.
 */
function cargarProductos() {
    const cont = document.getElementById('carrusel-dinamico-container');
    if (!cont) {
        console.error('No se encontró #carrusel-dinamico-container');
        return;
    }

    fetch('productos.php')
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.text();
        })
        .then(html => {
            cont.innerHTML = html;

            // Pequeño timeout para asegurar render; además se usa MutationObserver abajo
            setTimeout(() => inicializarCarrusel(), 80);

            // Si el contenido se sigue modificando (imágenes que cargan o script interno), observamos cambios
            const observer = new MutationObserver((mutations, obs) => {
                // si ya contiene al menos una tarjeta, inicializamos y desconectamos
                if (cont.querySelector('.producto-card') || cont.children.length > 0) {
                    inicializarCarrusel();
                    // esperamos un frame extra para seguridad visual y luego desconectamos
                    setTimeout(() => obs.disconnect(), 150);
                }
            });
            observer.observe(cont, { childList: true, subtree: true });
        })
        .catch(err => {
            console.error('Error al cargar productos:', err);
            cont.innerHTML = '<p style="color:red;text-align:center">Error al cargar productos</p>';
        });
}


// ========================================
// 🔹 NOTIFICACIONES
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
// 🔹 INICIALIZACIÓN
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    checkSession();
    cargarProductos();
    actualizarContadorCarrito();
});
