// scripts.js – 

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
  // Funcionalidad del carrito de compras
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

  // SELECTORES DE PANELES Y GESTIÓN
  const linkGestion = document.getElementById('link-gestion');     // Header (Camión)
  const linkAdmin = document.getElementById('link-admin');         // Header (Herramientas)
  const userInfo = document.getElementById('user-info');
  const userGreeting = document.getElementById('user-greeting');
  const sideLinkGestion = document.querySelector('.side-nav li.empleado-only'); 
  const sideLinkAdmin = document.querySelector('.side-nav li.admin-only');   // SideMenu Admin

  const btnCategorias = document.getElementById('btn-categorias');
  const btnCloseMenu = document.getElementById('btn-close-menu');
  const sideMenu = document.getElementById('side-menu');
  const menuOverlay = document.getElementById('menu-overlay');

  // Debug - verificar que los elementos existen
  console.log('Elementos del menú:');
  console.log('btnCategorias:', btnCategorias);
  console.log('btnCloseMenu:', btnCloseMenu);
  console.log('sideMenu:', sideMenu);
  console.log('menuOverlay:', menuOverlay);

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
    console.log('Cerrando menú lateral'); // Debug
    sideMenu?.classList.remove('open');
    menuOverlay?.classList.remove('active');
  }
  
  function abrirMenu() {
    console.log('Abriendo menú lateral'); // Debug
    sideMenu?.classList.add('open');
    menuOverlay?.classList.add('active');
  }
  
  btnCategorias?.addEventListener('click', (e) => {
    e.preventDefault();
    console.log('Click en botón categorías'); // Debug
    abrirMenu();
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
        // Pasamos null para limpiar la interfaz
        actualizarInterfaz(null, null); 
        setTimeout(() => window.location.reload(), 700);
      })
      .catch(err => {
        console.error('Error al cerrar sesión:', err);
        mostrarNotificacion('Error al cerrar sesión', 'error');
      });
  });

  // ---------------------------
  // ACTUALIZAR INTERFAZ SEGÚN ROL (Lógica Central de Visibilidad)
  // ---------------------------
  function actualizarInterfaz(rol, nombre) {
    // 1. Ocultar todos por defecto
    if (loginLink) loginLink.style.display = 'block';
    if (userInfo) userInfo.style.display = 'none';
    
    // Remover clases show y ocultar elementos por defecto
    if (linkGestion) {
        linkGestion.classList.remove('show');
        linkGestion.style.display = 'none';
    }
    if (linkAdmin) {
        linkAdmin.classList.remove('show');
        linkAdmin.style.display = 'none';
    }
    if (sideLinkGestion) sideLinkGestion.style.display = 'none';
    if (sideLinkAdmin) sideLinkAdmin.style.display = 'none';

    if (rol) {
        usuarioActual = { nombre: nombre, rol: rol };
        if (loginLink) loginLink.style.display = 'none';
        if (userInfo) userInfo.style.display = 'flex'; // Muestra el contenedor de usuario
        if (userGreeting) userGreeting.innerHTML = `<i class="fas fa-user"></i> Hola, ${nombre}!`;

        // 2. Mostrar/Ocultar basado en el ROL
        
        // El enlace de Gestión (Camión) es para Admin Y Empleado
        if (rol === 'admin' || rol === 'empleado') {
            if (linkGestion) {
                linkGestion.classList.add('show');
                linkGestion.style.display = 'flex'; 
            }
            if (sideLinkGestion) sideLinkGestion.style.display = 'block'; 
        }
        
        // El enlace de Admin (Herramientas) es SOLO para Admin
        if (rol === 'admin') {
            if (linkAdmin) {
                linkAdmin.classList.add('show');
                linkAdmin.style.display = 'flex'; 
            }
            if (sideLinkAdmin) sideLinkAdmin.style.display = 'block'; 
        }

    } else {
        usuarioActual = null;
        if (userGreeting) userGreeting.textContent = '';
        
        // Ocultar todos los paneles si no hay sesión
        if (linkGestion) {
            linkGestion.classList.remove('show');
            linkGestion.style.display = 'none';
        }
        if (linkAdmin) {
            linkAdmin.classList.remove('show');
            linkAdmin.style.display = 'none';
        }
        if (sideLinkGestion) sideLinkGestion.style.display = 'none';
        if (sideLinkAdmin) sideLinkAdmin.style.display = 'none';
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
        
        // La respuesta ya trae el rol
        const rol = data.rol ? data.rol.toString().toLowerCase() : null;
        actualizarInterfaz(rol, data.nombre);

        // Redirección segura
        if (data.redirect) {
          setTimeout(() => window.location.href = BASE + data.redirect, 300);
        } else {
          setTimeout(() => window.location.reload(), 300);
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
      let data;
      try {
        data = await res.json();
      } catch (e) {
        console.log("Error al parsear JSON de sesión");
        return;
      }
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
  // PROTECCIÓN CLICK EN "GESTIÓN" (Redirige, pero el dashboard.php tiene el control final)
  // ---------------------------
  // Este listener evita el 404 si el usuario intenta acceder al panel sin JS activo,
  // pero el control final lo tiene verificar_rol() en el lado del servidor.
  linkGestion?.addEventListener('click', (e) => {
      // Si el enlace está visible, permitimos la navegación sin preventDefault()
      // Si está oculto, el JS no debería permitir el click.
      // Como el dashboard.php tiene la protección final (verificar_rol),
      // eliminamos la verificación AJAX redundante aquí.
      // Si por algún bug el link es visible para un cliente, dashboard.php lo enviará a sin_permiso.
      if (linkGestion.style.display === 'none') {
           e.preventDefault();
      }
  });
  
  // Lo mismo para el admin
  linkAdmin?.addEventListener('click', (e) => {
      if (linkAdmin.style.display === 'none') {
           e.preventDefault();
      }
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
    console.log('🛒 Intentando cargar productos...');
    console.log('BASE URL:', BASE);
    
    const contPlantilla = document.getElementById('carrusel-dinamico-container');
    console.log('Contenedor encontrado:', contPlantilla ? '✅' : '❌');
    
    if (!contPlantilla) {
      console.log('⚠️ No existe el contenedor carrusel-dinamico-container en esta página');
      return;
    }

    console.log('📡 Haciendo fetch a:', BASE + 'productos.php');
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
        console.log('✅ Productos cargados exitosamente');

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
        console.error('❌ Error al cargar productos:', err);
        console.log('URL que falló:', BASE + 'productos.php');
        contPlantilla.innerHTML = '<p style="color:red;text-align:center">Error al cargar productos: ' + err.message + '</p>';
      });
  }


  
  // ---------------------------
  // INICIALIZACIÓN
  // ---------------------------
  // INICIALIZACIÓN AL CARGAR EL DOM
  // ---------------------------
  
  console.log('🚀 Inicializando aplicación...');
  console.log('BASE detectada:', BASE);
  
  // Comprueba sesión y actualiza UI al cargar la página
  console.log('🔐 Verificando sesión...');
  checkSession();
  
  // Carga productos y configura carrusel si existe el contenedor
  console.log('🛒 Iniciando carga de productos...');
  cargarProductos();
  
  // El contador del carrito se actualiza automáticamente en carrito.js
  console.log('✅ Inicialización completada');

  // ---------------------------
  // EXPOSICIÓN PARA DEBUG
  // ---------------------------
  window.__MYAPP = { BASE, checkSession, actualizarInterfaz, usuarioActual };

});