// scripts.js – Versión completa (login, registro, checkSession, carrito, carrusel, UI, protecciones)
// Detecta path base automáticamente y protege rutas relativas para evitar 404.
// Asegúrate de colocar este archivo en la raíz de /proyecto_supermercado/ o ajustar base si hace falta.

(function() {
    // ---------------------------
    // DETECCIÓN DINÁMICA DE BASE
    // ---------------------------
    const detectedBase = (function() {
      const baseEl = document.querySelector('base');
      if (baseEl) {
        let b = baseEl.getAttribute('href') || '/';
        return b.endsWith('/') ? b : b + '/';
      }
      // Si la app vive en /proyecto_supermercado/ detectamos la primera carpeta
      const parts = window.location.pathname.split('/').filter(Boolean);
      if (parts.length > 0) {
        return '/' + parts[0] + '/';
      }
      return '/';
    })();
    const BASE = detectedBase; // ej "/proyecto_supermercado/"
  
    // ---------------------------
    // SELECTORES Y VARIABLES
    // ---------------------------
    let usuarioActual = null;
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
  
    const linkGestion = document.getElementById('link-gestion');
    const linkAdmin = document.getElementById('link-admin');
    const userInfo = document.getElementById('user-info');
    const userGreeting = document.getElementById('user-greeting');
  
    const btnCategorias = document.getElementById('btn-categorias');
    const btnCloseMenu = document.getElementById('btn-close-menu');
    const sideMenu = document.getElementById('side-menu');
    const menuOverlay = document.getElementById('menu-overlay');
  
    // ---------------------------
    // UTILIDADES
    // ---------------------------
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
  
    function safeQuery(sel) {
      try { return document.querySelector(sel); } catch (e) { return null; }
    }
  
    // ---------------------------
    // MENÚ LATERAL
    // ---------------------------
    function cerrarMenu() {
      sideMenu?.classList.remove('open');
      menuOverlay?.classList.remove('active');
    }
    btnCategorias?.addEventListener('click', () => {
      sideMenu?.classList.add('open');
      menuOverlay?.classList.add('active');
    });
    btnCloseMenu?.addEventListener('click', cerrarMenu);
    menuOverlay?.addEventListener('click', cerrarMenu);
  
    // ---------------------------
    // MODAL LOGIN / REGISTRO
    // ---------------------------
    loginLink?.addEventListener('click', (e) => {
      e.preventDefault();
      if (!loginModal) return;
      loginModal.style.display = 'block';
      if (loginForm) loginForm.style.display = 'block';
      if (registerForm) registerForm.style.display = 'none';
      const mt = document.getElementById('modal-title');
      if (mt) mt.textContent = 'Iniciar Sesión';
    });
  
    closeModal?.addEventListener('click', () => (loginModal.style.display = 'none'));
    window.addEventListener('click', (e) => {
      if (e.target === loginModal) loginModal.style.display = 'none';
    });
  
    showRegisterLink?.addEventListener('click', (e) => {
      e.preventDefault();
      if (loginForm) loginForm.style.display = 'none';
      if (registerForm) registerForm.style.display = 'block';
      const mt = document.getElementById('modal-title');
      if (mt) mt.textContent = 'Crear Cuenta';
    });
    showLoginLink?.addEventListener('click', (e) => {
      e.preventDefault();
      if (registerForm) registerForm.style.display = 'none';
      if (loginForm) loginForm.style.display = 'block';
      const mt = document.getElementById('modal-title');
      if (mt) mt.textContent = 'Iniciar Sesión';
    });
  
    // ---------------------------
    // LOGOUT
    // ---------------------------
    logoutLink?.addEventListener('click', (e) => {
      e.preventDefault();
      fetch(BASE + 'login/logout.php', { method: 'GET', credentials: 'same-origin' })
        .then(() => {
          mostrarNotificacion('Sesión cerrada correctamente', 'success');
          actualizarInterfaz(null, null);
          setTimeout(() => window.location.reload(), 700);
        })
        .catch(err => {
          console.error('Error al cerrar sesión:', err);
          mostrarNotificacion('Error al cerrar sesión', 'error');
        });
    });
  
    // ---------------------------
    // ACTUALIZAR INTERFAZ SEGÚN ROL
    // ---------------------------
    function actualizarInterfaz(rol, nombre) {
      if (loginLink) loginLink.style.display = 'block';
      if (userInfo) userInfo.style.display = 'none';
      if (linkGestion) linkGestion.style.display = 'none';
      if (linkAdmin) linkAdmin.style.display = 'none';
  
      document.querySelectorAll('.employee-only').forEach(el => el.style.display = 'none');
      document.querySelectorAll('.admin-only').forEach(el => el.style.display = 'none');
  
      if (rol) {
        usuarioActual = { nombre: nombre, rol: rol };
        if (loginLink) loginLink.style.display = 'none';
        if (userInfo) userInfo.style.display = 'flex';
        if (userGreeting) userGreeting.innerHTML = `<i class="fas fa-user"></i> Hola, ${nombre}!`;
  
        if (rol === 'admin') {
          if (linkAdmin) { linkAdmin.style.display = 'inline-block'; linkAdmin.href = BASE + 'paneles/dashboard_admin.php'; }
          if (linkGestion) { linkGestion.style.display = 'inline-block'; linkGestion.href = BASE + 'paneles/dashboard_empleado.php'; }
          document.querySelectorAll('.employee-only').forEach(el => el.style.display = 'block');
          document.querySelectorAll('.admin-only').forEach(el => el.style.display = 'block');
        } else if (rol === 'empleado') {
          if (linkGestion) { linkGestion.style.display = 'inline-block'; linkGestion.href = BASE + 'paneles/dashboard_empleado.php'; }
          document.querySelectorAll('.employee-only').forEach(el => el.style.display = 'block');
          document.querySelectorAll('.admin-only').forEach(el => el.style.display = 'none');
        } else {
          document.querySelectorAll('.employee-only, .admin-only').forEach(el => el.style.display = 'none');
        }
      } else {
        usuarioActual = null;
        if (userGreeting) userGreeting.textContent = '';
      }
    }
  
    // ---------------------------
    // LOGIN (SUBMIT)
    // ---------------------------
    loginForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const dniEl = document.getElementById('dni');
      const dni = dniEl ? dniEl.value.trim() : '';
      if (!dni) {
        if (loginMessage) loginMessage.textContent = 'Ingrese su DNI';
        return;
      }
      if (loginMessage) loginMessage.textContent = '';
  
      try {
        const res = await fetch(BASE + 'login/login.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `dni=${encodeURIComponent(dni)}`
        });
        const data = await res.json();
  
        if (data.success) {
          mostrarNotificacion(`Bienvenido, ${data.nombre}`, 'success');
          if (loginModal) loginModal.style.display = 'none';
          actualizarInterfaz(data.rol, data.nombre);
  
          // redirigir según rol o instrucción del backend
          if (data.redirect) {
            setTimeout(() => window.location.href = data.redirect, 300);
          } else {
            if (data.rol === 'cliente') {
              setTimeout(() => window.location.reload(), 300);
            } else if (data.rol === 'admin') {
              setTimeout(() => window.location.href = BASE + 'paneles/dashboard_admin.php', 300);
            } else if (data.rol === 'empleado') {
              setTimeout(() => window.location.href = BASE + 'paneles/dashboard_empleado.php', 300);
            }
          }
        } else {
          if (loginMessage) loginMessage.textContent = data.message || 'Credenciales inválidas';
          mostrarNotificacion(data.message || 'Error login', 'error');
        }
      } catch (err) {
        console.error('Error login:', err);
        if (loginMessage) loginMessage.textContent = 'Error de conexión con el servidor.';
        mostrarNotificacion('Error de conexión', 'error');
      }
    });
  
    // ---------------------------
    // REGISTRO (ejemplo simple)
    // ---------------------------
    registerForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const dni = (document.getElementById('reg-dni') || {}).value || '';
      const nombre = (document.getElementById('nombre') || {}).value || '';
      const correo = (document.getElementById('correo') || {}).value || '';
  
      if (!dni || !nombre || !correo) {
        if (registerMessage) registerMessage.textContent = 'Completa todos los campos';
        return;
      }
      if (registerMessage) registerMessage.textContent = '';
  
      try {
        const res = await fetch(BASE + 'login/register.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `dni=${encodeURIComponent(dni)}&nombre=${encodeURIComponent(nombre)}&correo=${encodeURIComponent(correo)}`
        });
        const data = await res.json();
        if (data.success) {
          mostrarNotificacion('Cuenta creada. Ya puedes iniciar sesión', 'success');
          // mostrar form de login
          if (registerForm) registerForm.style.display = 'none';
          if (loginForm) loginForm.style.display = 'block';
          const mt = document.getElementById('modal-title'); if (mt) mt.textContent = 'Iniciar Sesión';
        } else {
          if (registerMessage) registerMessage.textContent = data.message || 'Error al registrar';
          mostrarNotificacion(data.message || 'Error al registrar', 'error');
        }
      } catch (err) {
        console.error('Error register:', err);
        if (registerMessage) registerMessage.textContent = 'Error de conexión';
        mostrarNotificacion('Error de conexión', 'error');
      }
    });
  
    // ---------------------------
    // CHECK SESSION (AJAX)
    // ---------------------------
    async function checkSession() {
      try {
        const res = await fetch(BASE + 'login/check_session.php', {
          method: 'GET',
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (data.logged_in) {
          const rol = data.rol ? data.rol.toString().toLowerCase() : (data.id_rol ? data.id_rol.toString() : null);
          actualizarInterfaz(rol, data.nombre || data.nombre_usuario || 'Usuario');
        } else {
          actualizarInterfaz(null, null);
        }
      } catch (err) {
        console.error('Error al verificar sesión:', err);
        actualizarInterfaz(null, null);
      }
    }
  
    // ---------------------------
    // PROTECCIÓN CLICK EN "GESTIÓN" (evita 404)
    // ---------------------------
    linkGestion?.addEventListener('click', async (e) => {
      e.preventDefault();
      try {
        const res = await fetch(BASE + 'login/check_session.php', {
          method: 'GET',
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (data.logged_in) {
          const rol = (data.rol || '').toString().toLowerCase();
          const idRol = data.id_rol ? data.id_rol.toString() : '';
          if (rol === 'admin' || rol === 'empleado' || idRol === '1' || idRol === '2') {
            window.location.href = BASE + 'paneles/dashboard_empleado.php';
          } else {
            window.location.href = BASE + 'sin_permiso.php';
          }
        } else {
          window.location.href = BASE + 'login/login.html';
        }
      } catch (err) {
        console.error(err);
        mostrarNotificacion('No se pudo comprobar la sesión', 'error');
      }
    });
  
    // ---------------------------
    // CARRITO: contador y agregar
    // ---------------------------
    function actualizarContadorCarrito() {
      fetch(BASE + 'carrito/obtener_carrito.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(d => {
          const cc = document.getElementById('cart-count');
          if (cc) cc.textContent = d.total_items || 0;
        })
        .catch(() => {
          const cc = document.getElementById('cart-count');
          if (cc) cc.textContent = 0;
        });
    }
  
    document.addEventListener('click', (e) => {
      // buscar el botón más cercano con clase .boton-agregar
      let btn = null;
      try {
        btn = e.target.closest('.boton-agregar');
      } catch (err) {
        // algunos navegadores antiguos pueden necesitar fallback
        btn = null;
      }
      if (!btn) return;
  
      if (!usuarioActual) {
        mostrarNotificacion('Debes iniciar sesión para agregar productos', 'error');
        if (loginModal) loginModal.style.display = 'block';
        return;
      }
  
      const id = btn.dataset.id;
      const nombre = btn.dataset.nombre || 'Producto';
  
      fetch(BASE + 'carrito/agregar_carrito.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_producto: id, cantidad: 1 })
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          mostrarNotificacion(`${nombre} agregado al carrito`, 'success');
          actualizarContadorCarrito();
        } else {
          mostrarNotificacion(data.message || 'Error al agregar producto', 'error');
        }
      })
      .catch(() => mostrarNotificacion('Error de conexión', 'error'));
    });
  
    // ---------------------------
    // CARRUSEL Y CARGA DE PRODUCTOS
    // ---------------------------
    function inicializarCarrusel() {
      let track = document.getElementById('carrusel-dinamico-container') || document.querySelector('.carousel-track');
      if (!track) {
        console.warn('Carousel: track no encontrado.');
        return;
      }
  
      const container = track.closest('.carrusel-container') || track.parentElement;
      const btnPrev = container ? container.querySelector('.prev') : document.querySelector('.prev');
      const btnNext = container ? container.querySelector('.next') : document.querySelector('.next');
  
      track.style.overflowX = track.style.overflowX || 'auto';
      track.style.scrollBehavior = track.style.scrollBehavior || 'smooth';
  
      const firstCard = track.querySelector('.producto-card') || track.firstElementChild;
      const gap = parseFloat(getComputedStyle(track).gap || 0);
      const cardWidth = firstCard ? Math.ceil(firstCard.getBoundingClientRect().width + gap) : 270;
      const step = cardWidth || 270;
  
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
      }
      if (btnPrev) {
        clearHandler(btnPrev, '__carouselPrev');
        const handlerPrev = () => track.scrollBy({ left: -step, behavior: 'smooth' });
        btnPrev.addEventListener('click', handlerPrev);
        btnPrev.__carouselPrev = handlerPrev;
      }
  
      // Accesibilidad teclado
      [btnPrev, btnNext].forEach(btn => {
        if (!btn) return;
        btn.setAttribute('tabindex', '0');
        btn.style.cursor = 'pointer';
        btn.addEventListener('keyup', (e) => {
          if (e.key === 'Enter' || e.key === ' ') btn.click();
        });
      });
  
      function actualizarEstadoBotones() {
        if (!track) return;
        if (btnNext) btnNext.disabled = (track.scrollLeft + track.clientWidth >= track.scrollWidth - 1);
        if (btnPrev) btnPrev.disabled = (track.scrollLeft <= 1);
      }
      track.addEventListener('scroll', () => requestAnimationFrame(actualizarEstadoBotones));
      actualizarEstadoBotones();
    }
  
    function cargarProductos() {
      const contPlantilla = document.getElementById('carrusel-dinamico-container');
      if (!contPlantilla) {
        console.error('No se encontró #carrusel-dinamico-container en la plantilla.');
        return;
      }
  
      fetch(BASE + 'productos.php', { cache: 'no-store', credentials: 'same-origin' })
        .then(r => {
          if (!r.ok) throw new Error('HTTP ' + r.status);
          return r.text();
        })
        .then(html => {
          const tmp = document.createElement('div');
          tmp.innerHTML = html.trim();
  
          const fetchedContainer = tmp.querySelector('.carrusel-container');
          const fetchedTrack = tmp.querySelector('.carousel-track');
  
          if (fetchedContainer) {
            const existingContainer = document.querySelector('.carrusel-container');
            const newTrack = fetchedContainer.querySelector('.carousel-track');
            if (newTrack) newTrack.id = 'carrusel-dinamico-container';
            if (existingContainer && existingContainer.parentNode) {
              existingContainer.parentNode.replaceChild(fetchedContainer, existingContainer);
            } else {
              document.querySelector('main')?.appendChild(fetchedContainer);
            }
          } else if (fetchedTrack) {
            contPlantilla.innerHTML = fetchedTrack.innerHTML;
          } else {
            contPlantilla.innerHTML = tmp.innerHTML;
          }
  
          setTimeout(() => inicializarCarrusel(), 60);
  
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
  
    // ---------------------------
    // INICIALIZACIÓN
    // ---------------------------
    document.addEventListener('DOMContentLoaded', () => {
      // Comprueba sesión y actualiza UI
      checkSession();
      // Carga productos y configura carrusel
      cargarProductos();
      // Actualiza contador de carrito
      actualizarContadorCarrito();
    });
  
    // ---------------------------
    // EXPOSICIÓN PARA DEBUG
    // ---------------------------
    window.__MYAPP = { BASE, checkSession, actualizarInterfaz, usuarioActual };
  
  })();