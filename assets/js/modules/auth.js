/**
 * Módulo de Autenticación
 * Maneja login, registro, logout y verificación de sesión
 */

export class Auth {
    constructor(baseURL = '/') {
        this.baseURL = baseURL;
        this.usuarioActual = null;
        this.elementos = this.obtenerElementos();
        this.init();
    }

    /**
     * Obtiene referencias a elementos del DOM
     */
    obtenerElementos() {
        return {
            loginModal: document.getElementById('loginModal'),
            loginLink: document.getElementById('login-link'),
            logoutLink: document.getElementById('logout-link'),
            closeModal: document.querySelector('#loginModal .close-btn'),
            loginForm: document.getElementById('login-form-dni'),
            registerForm: document.getElementById('register-form'),
            loginMessage: document.getElementById('login-message'),
            registerMessage: document.getElementById('register-message'),
            showRegisterLink: document.getElementById('show-register'),
            showLoginLink: document.getElementById('show-login'),
            userInfo: document.getElementById('user-info'),
            userGreeting: document.getElementById('user-greeting')
        };
    }

    /**
     * Inicializa los event listeners
     */
    init() {
        this.configurarEventosModal();
        this.configurarEventosLogin();
        this.configurarEventosLogout();
        this.verificarSesionInicial();
    }

    /**
     * Configura eventos del modal
     */
    configurarEventosModal() {
        const { loginLink, closeModal, loginModal, showRegisterLink, showLoginLink } = this.elementos;

        // Abrir modal de login
        loginLink?.addEventListener('click', (e) => {
            e.preventDefault();
            this.abrirModal('login');
        });

        // Cerrar modal
        closeModal?.addEventListener('click', () => this.cerrarModal());
        
        // Cerrar al hacer click fuera
        window.addEventListener('click', (e) => {
            if (e.target === loginModal) this.cerrarModal();
        });

        // Cambiar entre login y registro
        showRegisterLink?.addEventListener('click', (e) => {
            e.preventDefault();
            this.cambiarFormulario('registro');
        });

        showLoginLink?.addEventListener('click', (e) => {
            e.preventDefault();
            this.cambiarFormulario('login');
        });
    }

    /**
     * Configura eventos de login y registro
     */
    configurarEventosLogin() {
        const { loginForm, registerForm } = this.elementos;

        // Submit del login
        loginForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.procesarLogin(e);
        });

        // Submit del registro
        registerForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.procesarRegistro(e);
        });
    }

    /**
     * Configura eventos de logout
     */
    configurarEventosLogout() {
        const { logoutLink } = this.elementos;

        logoutLink?.addEventListener('click', async (e) => {
            e.preventDefault();
            await this.procesarLogout();
        });
    }

    /**
     * Abre el modal en modo login o registro
     */
    abrirModal(modo = 'login') {
        const { loginModal } = this.elementos;
        if (!loginModal) return;

        loginModal.classList.add('show');
        loginModal.style.display = 'flex';
        this.cambiarFormulario(modo);
    }

    /**
     * Cierra el modal
     */
    cerrarModal() {
        const { loginModal } = this.elementos;
        if (!loginModal) return;

        loginModal.classList.remove('show');
        loginModal.style.display = 'none';
    }

    /**
     * Cambia entre formularios de login y registro
     */
    cambiarFormulario(tipo) {
        const { loginForm, registerForm } = this.elementos;
        const modalTitle = document.getElementById('modal-title');

        if (tipo === 'registro') {
            if (loginForm) loginForm.style.display = 'none';
            if (registerForm) registerForm.style.display = 'block';
            if (modalTitle) modalTitle.textContent = 'Crear Cuenta';
        } else {
            if (registerForm) registerForm.style.display = 'none';
            if (loginForm) loginForm.style.display = 'block';
            if (modalTitle) modalTitle.textContent = 'Iniciar Sesión';
        }
    }

    /**
     * Procesa el login
     */
    async procesarLogin(e) {
        const { loginMessage } = this.elementos;
        const dniEl = document.getElementById('dni');
        const dni = dniEl ? dniEl.value.trim() : '';

        if (!dni) {
            if (loginMessage) loginMessage.textContent = 'Ingrese su DNI';
            return;
        }

        if (loginMessage) loginMessage.textContent = '';

        try {
            const res = await fetch(`${this.baseURL}login/login.php`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `dni=${encodeURIComponent(dni)}`
            });

            const data = await res.json();

            if (data.success) {
                this.mostrarNotificacion(`Bienvenido, ${data.nombre}`, 'success');
                this.cerrarModal();
                
                const rol = data.rol ? data.rol.toString().toLowerCase() : null;
                this.actualizarSesion(rol, data.nombre);
                
                // Disparar evento de login exitoso
                document.dispatchEvent(new CustomEvent('loginSuccess', { 
                    detail: { rol, nombre: data.nombre, usuario: data } 
                }));

                // Redirección si es necesaria
                if (data.redirect) {
                    setTimeout(() => window.location.href = this.baseURL + data.redirect, 300);
                } else {
                    setTimeout(() => window.location.reload(), 300);
                }
            } else {
                if (loginMessage) loginMessage.textContent = data.message || 'Credenciales inválidas';
                this.mostrarNotificacion(data.message || 'Error login', 'error');
            }
        } catch (err) {
            console.error('Error login:', err);
            if (loginMessage) loginMessage.textContent = 'Error de conexión con el servidor.';
            this.mostrarNotificacion('Error de conexión', 'error');
        }
    }

    /**
     * Procesa el registro
     */
    async procesarRegistro(e) {
        const { registerMessage } = this.elementos;
        const dni = (document.getElementById('reg-dni') || {}).value || '';
        const nombre = (document.getElementById('nombre') || {}).value || '';
        const correo = (document.getElementById('correo') || {}).value || '';

        if (!dni || !nombre || !correo) {
            if (registerMessage) registerMessage.textContent = 'Completa todos los campos';
            return;
        }

        if (registerMessage) registerMessage.textContent = '';

        try {
            const res = await fetch(`${this.baseURL}login/register.php`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `dni=${encodeURIComponent(dni)}&nombre=${encodeURIComponent(nombre)}&correo=${encodeURIComponent(correo)}`
            });

            const data = await res.json();

            if (data.success) {
                this.mostrarNotificacion('Cuenta creada. Ya puedes iniciar sesión', 'success');
                this.cambiarFormulario('login');
            } else {
                if (registerMessage) registerMessage.textContent = data.message || 'Error al registrar';
                this.mostrarNotificacion(data.message || 'Error al registrar', 'error');
            }
        } catch (err) {
            console.error('Error register:', err);
            if (registerMessage) registerMessage.textContent = 'Error de conexión';
            this.mostrarNotificacion('Error de conexión', 'error');
        }
    }

    /**
     * Procesa el logout
     */
    async procesarLogout() {
        try {
            await fetch(`${this.baseURL}login/logout.php`, { 
                method: 'GET', 
                credentials: 'same-origin' 
            });

            this.mostrarNotificacion('Sesión cerrada correctamente', 'success');
            this.actualizarSesion(null, null);
            
            // Disparar evento de logout
            document.dispatchEvent(new CustomEvent('logout'));
            
            setTimeout(() => window.location.reload(), 700);
        } catch (err) {
            console.error('Error al cerrar sesión:', err);
            this.mostrarNotificacion('Error al cerrar sesión', 'error');
        }
    }

    /**
     * Verifica la sesión actual
     */
    async verificarSesion() {
        try {
            const res = await fetch(`${this.baseURL}login/check_session.php`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });

            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();

            if (data.logged_in) {
                const rol = data.rol ? data.rol.toString().toLowerCase() : 
                           (data.id_rol ? data.id_rol.toString() : null);
                this.actualizarSesion(rol, data.nombre || data.nombre_usuario || 'Usuario');
                return { loggedIn: true, rol, nombre: data.nombre };
            } else {
                this.actualizarSesion(null, null);
                return { loggedIn: false };
            }
        } catch (err) {
            console.error('Error al verificar sesión:', err);
            this.actualizarSesion(null, null);
            return { loggedIn: false };
        }
    }

    /**
     * Actualiza la interfaz según la sesión
     */
    actualizarSesion(rol, nombre) {
        const { loginLink, userInfo, userGreeting } = this.elementos;

        if (rol && nombre) {
            this.usuarioActual = { nombre, rol };
            if (loginLink) loginLink.style.display = 'none';
            if (userInfo) userInfo.style.display = 'flex';
            if (userGreeting) userGreeting.innerHTML = `<i class="fas fa-user"></i> Hola, ${nombre}!`;
        } else {
            this.usuarioActual = null;
            if (loginLink) loginLink.style.display = 'block';
            if (userInfo) userInfo.style.display = 'none';
            if (userGreeting) userGreeting.textContent = '';
        }

        // Disparar evento de cambio de sesión
        document.dispatchEvent(new CustomEvent('sessionChanged', { 
            detail: { rol, nombre, logged_in: !!rol, usuario: this.usuarioActual } 
        }));
    }

    /**
     * Verifica sesión al inicializar
     */
    async verificarSesionInicial() {
        await this.verificarSesion();
    }

    /**
     * Muestra notificaciones
     */
    mostrarNotificacion(mensaje, tipo = 'info') {
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

    /**
     * Obtiene el usuario actual
     */
    getUsuarioActual() {
        return this.usuarioActual;
    }
}