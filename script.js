// ========================================
// script.js — Gestión del supermercado online (REESCRITO Y CORREGIDO)
// ========================================

// 🔹 VARIABLES GLOBALES
let usuarioActual = null;
const backendUrl = './'; // Define la URL base para los archivos PHP

// ========================================
// 🔹 SELECTORES GLOBALES
// ========================================
// (Definiciones de selectores para evitar duplicidad)
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

// Selectores para actualizar la UI (Navegación)
const linkGestion = document.getElementById('link-gestion');
const linkAdmin = document.getElementById('link-admin');
const userInfo = document.getElementById('user-info');
const userGreeting = document.getElementById('user-greeting');


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


// ----------------------------------------------------
// 🔹 FUNCIÓN UNIFICADA: ACTUALIZAR INTERFAZ Y VISIBILIDAD DE ROLES
// ----------------------------------------------------
function actualizarInterfaz(rol, nombre) {
    // 1. Resetear el estado por defecto
    loginLink.style.display = 'block';
    userInfo.style.display = 'none';
    linkGestion.style.display = 'none';
    linkAdmin.style.display = 'none';
    
    // 2. Si hay sesión activa
    if (rol) {
        // Actualiza la variable global que usan otras funciones (como el carrito)
        usuarioActual = { nombre: nombre, rol: rol }; 
        
        // Mostrar saludo y logout, ocultar login
        loginLink.style.display = 'none';
        userInfo.style.display = 'flex'; // Cambiar a 'flex' para que sea visible
        // Nota: Se corrigió la línea para usar el formato deseado
        userGreeting.innerHTML = `<i class="fas fa-user"></i> Hola, ${nombre}!`; 

        // 3. Mostrar enlaces de dashboard según el rol
        if (rol === 'admin') {
            linkAdmin.style.display = 'block';
            linkGestion.style.display = 'block';
            linkGestion.href = 'dashboard_empleado.php';
        } else if (rol === 'empleado') {
            linkGestion.style.display = 'block';
            linkGestion.href = 'dashboard_empleado.php';
        }
    } else {
        usuarioActual = null;
    }
}


// ----------------------------------------------------
// 🔹 MANEJO DEL SUBMIT DEL FORMULARIO DE LOGIN
// ----------------------------------------------------
loginForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const dni = document.getElementById('dni').value;
    
    // Limpiar mensajes anteriores
    loginMessage.textContent = ''; 

    try {
        const response = await fetch(backendUrl + 'login/login.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `dni=${dni}`
        });

        const data = await response.json();

        if (data.success) {
            // Nota: Aquí no se llama a actualizarInterfaz porque la página se va a redirigir
            // Y la nueva página o el checkSession() se encargarán de actualizar la UI
            mostrarNotificacion(`Bienvenido, ${data.nombre}`, 'success');
            
            // 1. Ocultar el modal (si está abierto)
            if (loginModal) {
                 loginModal.style.display = 'none';
            }
            
            // 2. **REDIRECCIÓN AL DASHBOARD DEL ROL** (Usando la URL del servidor)
            window.location.href = data.redirect; 

        } else {
            loginMessage.textContent = data.message;
            mostrarNotificacion(data.message, 'error');
        }
    } catch (error) {
        console.error('Error de red o JSON inválido:', error);
        loginMessage.textContent = 'Error de conexión con el servidor.';
    }
});


// ========================================
// 🔹 SESIÓN (CORREGIDO - Ruta y uso de actualizarInterfaz)
// ========================================
function checkSession() {
    // RUTA CORREGIDA: Se asume que check_session.php está en la raíz, no en /login/
    fetch(backendUrl + 'login/check_session.php') 
        .then(r => r.json())
        .then(data => {
            if (data.logged_in) {
                // Llama a la función unificada
                actualizarInterfaz(data.rol, data.nombre); 
            } else {
                actualizarInterfaz(null, null); // Resetea la UI si no hay sesión
            }
        })
        .catch(err => {
            console.error('Error al verificar sesión:', err);
            actualizarInterfaz(null, null);
        });
}

// Nota: Las funciones updateUI y resetUI han sido eliminadas ya que
// la función actualizarInterfaz cumple su propósito de forma unificada.


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

    // Bloquear si no hay usuario logueado (usa la variable global actualizada)
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
// (Contenido restaurado)
// ========================================

function inicializarCarrusel() {
    // Intentar obtener el track por ID (preferido), si no, por clase
    let track = document.getElementById('carrusel-dinamico-container') || document.querySelector('.carousel-track');
    if (!track) {
        console.warn('Carousel: track no encontrado.');
        return;
    }

    // Buscar el contenedor que lo rodea (para localizar los botones)
    const container = track.closest('.carrusel-container') || track.parentElement;
    const btnPrev = container ? container.querySelector('.prev') : document.querySelector('.prev');
    const btnNext = container ? container.querySelector('.next') : document.querySelector('.next');

    // Asegurar scroll horizontal y smooth
    track.style.overflowX = track.style.overflowX || 'auto';
    track.style.scrollBehavior = track.style.scrollBehavior || 'smooth';

    // Medir ancho de "paso" (una tarjeta)
    const firstCard = track.querySelector('.producto-card') || track.firstElementChild;
    const gap = parseFloat(getComputedStyle(track).gap || 0);
    const cardWidth = firstCard ? Math.ceil(firstCard.getBoundingClientRect().width + gap) : 270;
    const step = cardWidth || 270;

    // Limpiar handlers previos en botones para evitar duplicados
    function clearHandler(el, key) {
        if (!el) return;
        const prev = el[key];
        if (prev && typeof prev === 'function') {
            el.removeEventListener('click', prev);
            el[key] = null;
        }
    }

    if (btnNext) {
        clearHandler(btnNext, '__carouselNext');
        const handlerNext = () => track.scrollBy({ left: step, behavior: 'smooth' });
        btnNext.addEventListener('click', handlerNext);
        btnNext.__carouselNext = handlerNext;
    } else {
        console.warn('Carousel: botón .next no encontrado.');
    }

    if (btnPrev) {
        clearHandler(btnPrev, '__carouselPrev');
        const handlerPrev = () => track.scrollBy({ left: -step, behavior: 'smooth' });
        btnPrev.addEventListener('click', handlerPrev);
        btnPrev.__carouselPrev = handlerPrev;
    } else {
        console.warn('Carousel: botón .prev no encontrado.');
    }

    // Accesibilidad: teclado
    [btnPrev, btnNext].forEach(btn => {
        if (!btn) return;
        btn.setAttribute('tabindex', '0');
        btn.style.cursor = 'pointer';
        btn.style.zIndex = btn.style.zIndex || '20';
        // evitar duplicate keyup listeners: se asume que no existen muchos
        btn.addEventListener('keyup', (e) => {
            if (e.key === 'Enter' || e.key === ' ') btn.click();
        });
    });

    // Deshabilitar botones al llegar a los extremos
    function actualizarEstadoBotones() {
        if (!track) return;
        if (btnNext) btnNext.disabled = (track.scrollLeft + track.clientWidth >= track.scrollWidth - 1);
        if (btnPrev) btnPrev.disabled = (track.scrollLeft <= 1);
    }
    track.addEventListener('scroll', () => requestAnimationFrame(actualizarEstadoBotones));
    // estado inicial
    actualizarEstadoBotones();
}

function cargarProductos() {
    const contPlantilla = document.getElementById('carrusel-dinamico-container');
    if (!contPlantilla) {
        console.error('No se encontró #carrusel-dinamico-container en la plantilla.');
        return;
    }

    fetch('productos.php', { cache: 'no-store' })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.text();
        })
        .then(html => {
            // parsear sin insertar inmediatamente
            const tmp = document.createElement('div');
            tmp.innerHTML = html.trim();

            const fetchedContainer = tmp.querySelector('.carrusel-container');
            const fetchedTrack = tmp.querySelector('.carousel-track');

            if (fetchedContainer) {
                // El servidor devolvió TODO el contenedor (botones + track + tarjetas)
                // Reemplazamos el contenedor de la plantilla por el nuevo contenedor
                const existingContainer = document.querySelector('.carrusel-container');

                // Asegurar que el track nuevo tenga el ID esperado para futuras referencias
                const newTrack = fetchedContainer.querySelector('.carousel-track');
                if (newTrack) newTrack.id = 'carrusel-dinamico-container';
                else {
                    // si por alguna razón no hay track, crear uno y mover las tarjetas
                    const createdTrack = document.createElement('div');
                    createdTrack.className = 'carousel-track';
                    createdTrack.id = 'carrusel-dinamico-container';
                    // mover nodos que parecen tarjetas
                    Array.from(fetchedContainer.children).forEach(ch => {
                        if (ch.classList && ch.classList.contains('producto-card')) createdTrack.appendChild(ch);
                    });
                    fetchedContainer.appendChild(createdTrack);
                }

                if (existingContainer && existingContainer.parentNode) {
                    existingContainer.parentNode.replaceChild(fetchedContainer, existingContainer);
                } else {
                    // fallback: insertar al final del main
                    document.querySelector('main')?.appendChild(fetchedContainer);
                }
            } else {
                // El servidor devolvió solo tarjetas o un track parcial
                if (fetchedTrack) {
                    contPlantilla.innerHTML = fetchedTrack.innerHTML;
                } else {
                    // asumimos que tmp contiene solo <article class="producto-card">...
                    contPlantilla.innerHTML = tmp.innerHTML;
                }
            }

            // Dar tiempo a render y a carga de imágenes, luego inicializar
            setTimeout(() => inicializarCarrusel(), 60);

            // Observer por si hay scripts/imágenes que siguen añadiendo nodos
            const trackNode = document.getElementById('carrusel-dinamico-container');
            if (trackNode) {
                const observer = new MutationObserver((mutations, obs) => {
                    if (trackNode.querySelector('.producto-card') || trackNode.children.length > 0) {
                        inicializarCarrusel();
                        setTimeout(() => obs.disconnect(), 150);
                    }
                });
                observer.observe(trackNode, { childList: true, subtree: true });
            }
        })
        .catch(err => {
            console.error('Error al cargar productos:', err);
            contPlantilla.innerHTML = '<p style="color:red;text-align:center">Error al cargar productos</p>';
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