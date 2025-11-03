// auth.js - Módulo de autenticación y manejo de sesiones

/**
 * Manejador de autenticación y sesiones
 */
class AuthManager {
    constructor() {
        this.usuario = null;
        this.isLoggedIn = false;
        this.modal = null;
        this.init();
    }

    /**
     * Inicializa el sistema de autenticación
     */
    init() {
        this.findElements();
        this.bindEvents();
        this.checkSession();
        console.log('🔐 AuthManager inicializado');
    }

    /**
     * Encuentra elementos del DOM
     */
    findElements() {
        this.modal = Utils.safeQuery('#loginModal');
        this.loginLink = Utils.safeQuery('#login-link');
        this.logoutLink = Utils.safeQuery('#logoutLink');
        this.userInfo = Utils.safeQuery('#user-info');
        this.userGreeting = Utils.safeQuery('#user-greeting');
        
        // Formularios
        this.loginForm = Utils.safeQuery('#login-form-dni');
        this.registerForm = Utils.safeQuery('#register-form');
        
        // Enlaces de cambio
        this.showRegisterLink = Utils.safeQuery('#show-register');
        this.showLoginLink = Utils.safeQuery('#show-login');
        
        // Mensajes
        this.loginMessage = Utils.safeQuery('#login-message');
        this.registerMessage = Utils.safeQuery('#register-message');
        
        // Botón cerrar modal
        this.closeModalBtn = this.modal?.querySelector('.close-btn');
    }

    /**
     * Vincula eventos
     */
    bindEvents() {
        // Abrir modal de login
        if (this.loginLink) {
            this.loginLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.openLoginModal();
            });
        }

        // Cerrar modal
        if (this.closeModalBtn) {
            this.closeModalBtn.addEventListener('click', () => this.closeModal());
        }

        // Cerrar modal al hacer clic fuera
        if (this.modal) {
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.closeModal();
                }
            });
        }

        // Cambiar entre login y registro
        if (this.showRegisterLink) {
            this.showRegisterLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.showRegisterForm();
            });
        }

        if (this.showLoginLink) {
            this.showLoginLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.showLoginForm();
            });
        }

        // Submit forms
        if (this.loginForm) {
            this.loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        if (this.registerForm) {
            this.registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }

        // Logout
        if (this.logoutLink) {
            this.logoutLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.logout();
            });
        }

        // Escape para cerrar modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isModalOpen()) {
                this.closeModal();
            }
        });
    }

    /**
     * Abre el modal de login
     */
    openLoginModal() {
        if (this.modal) {
            this.modal.style.display = 'flex';
            this.showLoginForm();
            this.clearMessages();
            
            // Focus en primer input
            const firstInput = this.loginForm?.querySelector('input');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    }

    /**
     * Cierra el modal
     */
    closeModal() {
        if (this.modal) {
            this.modal.style.display = 'none';
            this.clearMessages();
        }
    }

    /**
     * Verifica si el modal está abierto
     */
    isModalOpen() {
        return this.modal && this.modal.style.display === 'flex';
    }

    /**
     * Muestra el formulario de login
     */
    showLoginForm() {
        if (this.loginForm) this.loginForm.style.display = 'block';
        if (this.registerForm) this.registerForm.style.display = 'none';
        
        const modalTitle = Utils.safeQuery('#modal-title');
        if (modalTitle) modalTitle.textContent = 'Iniciar Sesión';
    }

    /**
     * Muestra el formulario de registro
     */
    showRegisterForm() {
        if (this.loginForm) this.loginForm.style.display = 'none';
        if (this.registerForm) this.registerForm.style.display = 'block';
        
        const modalTitle = Utils.safeQuery('#modal-title');
        if (modalTitle) modalTitle.textContent = 'Crear Cuenta';
        
        // Focus en primer input del registro
        const firstInput = this.registerForm?.querySelector('input');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }

    /**
     * Maneja el login
     */
    async handleLogin(e) {
        e.preventDefault();
        
        const formData = new FormData(this.loginForm);
        const dni = formData.get('dni');
        
        if (!dni) {
            this.showLoginMessage('Por favor ingresa tu DNI', 'error');
            return;
        }

        try {
            this.showLoginMessage('Iniciando sesión...', 'info');
            
            const data = await Utils.fetchSeguro(
                SUPERMERCADO_CONFIG.BASE_URL + SUPERMERCADO_CONFIG.API_ENDPOINTS.LOGIN,
                {
                    method: 'POST',
                    body: JSON.stringify({ dni })
                }
            );

            if (data.success) {
                this.onLoginSuccess(data);
            } else {
                this.showLoginMessage(data.message || 'Error al iniciar sesión', 'error');
            }
            
        } catch (error) {
            console.error('Error en login:', error);
            this.showLoginMessage('Error de conexión', 'error');
        }
    }

    /**
     * Maneja el registro
     */
    async handleRegister(e) {
        e.preventDefault();
        
        const formData = new FormData(this.registerForm);
        const datos = {
            dni: formData.get('dni'),
            nombre: formData.get('nombre'),
            correo: formData.get('correo')
        };

        // Validaciones básicas
        if (!datos.dni || !datos.nombre || !datos.correo) {
            this.showRegisterMessage('Todos los campos son obligatorios', 'error');
            return;
        }

        if (!/^\d{7,8}$/.test(datos.dni)) {
            this.showRegisterMessage('DNI debe tener 7-8 dígitos', 'error');
            return;
        }

        if (!/\S+@\S+\.\S+/.test(datos.correo)) {
            this.showRegisterMessage('Email inválido', 'error');
            return;
        }

        try {
            this.showRegisterMessage('Creando cuenta...', 'info');
            
            const data = await Utils.fetchSeguro(
                SUPERMERCADO_CONFIG.BASE_URL + SUPERMERCADO_CONFIG.API_ENDPOINTS.REGISTRO,
                {
                    method: 'POST',
                    body: JSON.stringify(datos)
                }
            );

            if (data.success) {
                this.showRegisterMessage('¡Cuenta creada exitosamente!', 'success');
                setTimeout(() => {
                    this.showLoginForm();
                    this.clearMessages();
                }, 2000);
            } else {
                this.showRegisterMessage(data.message || 'Error al crear cuenta', 'error');
            }
            
        } catch (error) {
            console.error('Error en registro:', error);
            this.showRegisterMessage('Error de conexión', 'error');
        }
    }

    /**
     * Maneja el éxito del login
     */
    onLoginSuccess(data) {
        this.usuario = data.usuario;
        this.isLoggedIn = true;
        
        // Actualizar UI
        this.updateUI();
        
        // Cerrar modal
        this.closeModal();
        
        // Mostrar notificación
        mostrarNotificacion(`¡Bienvenido ${this.usuario.nombre}!`, 'success');
        
        // Actualizar permisos según rol
        this.updateRoleBasedElements();
        
        console.log('✅ Login exitoso:', this.usuario);
    }

    /**
     * Logout
     */
    async logout() {
        try {
            await Utils.fetchSeguro(
                SUPERMERCADO_CONFIG.BASE_URL + SUPERMERCADO_CONFIG.API_ENDPOINTS.LOGOUT,
                { method: 'POST' }
            );
        } catch (error) {
            console.error('Error en logout:', error);
        }
        
        this.usuario = null;
        this.isLoggedIn = false;
        this.updateUI();
        this.hideRoleBasedElements();
        
        mostrarNotificacion('Sesión cerrada', 'info');
        console.log('✅ Logout exitoso');
    }

    /**
     * Verifica la sesión actual
     */
    async checkSession() {
        try {
            const response = await fetch(SUPERMERCADO_CONFIG.BASE_URL + 'login/check_session.php');
            const data = await response.json();
            
            if (data.loggedIn) {
                this.usuario = data.usuario;
                this.isLoggedIn = true;
                this.updateUI();
                this.updateRoleBasedElements();
            }
        } catch (error) {
            console.error('Error verificando sesión:', error);
        }
    }

    /**
     * Actualiza la interfaz según el estado de login
     */
    updateUI() {
        if (this.isLoggedIn && this.usuario) {
            // Mostrar info de usuario
            if (this.userInfo) this.userInfo.style.display = 'block';
            if (this.loginLink) this.loginLink.style.display = 'none';
            if (this.userGreeting) {
                this.userGreeting.textContent = `Hola, ${this.usuario.nombre}`;
            }
        } else {
            // Mostrar link de login
            if (this.userInfo) this.userInfo.style.display = 'none';
            if (this.loginLink) this.loginLink.style.display = 'block';
        }
    }

    /**
     * Actualiza elementos basados en roles
     */
    updateRoleBasedElements() {
        if (!this.isLoggedIn || !this.usuario) return;

        const rol = this.usuario.rol?.toLowerCase();
        
        // Elementos para empleados
        const empleadoElements = document.querySelectorAll('.employee-only');
        empleadoElements.forEach(el => {
            el.style.display = (rol === 'empleado' || rol === 'administrador' || rol === 'dueño') ? 'block' : 'none';
        });

        // Elementos para administradores
        const adminElements = document.querySelectorAll('.admin-only');
        adminElements.forEach(el => {
            el.style.display = (rol === 'administrador' || rol === 'dueño') ? 'block' : 'none';
        });
    }

    /**
     * Oculta elementos basados en roles
     */
    hideRoleBasedElements() {
        const roleElements = document.querySelectorAll('.employee-only, .admin-only');
        roleElements.forEach(el => {
            el.style.display = 'none';
        });
    }

    /**
     * Muestra mensaje en el formulario de login
     */
    showLoginMessage(message, type = 'info') {
        if (this.loginMessage) {
            this.loginMessage.textContent = message;
            this.loginMessage.className = `message ${type}`;
            this.loginMessage.style.display = 'block';
        }
    }

    /**
     * Muestra mensaje en el formulario de registro
     */
    showRegisterMessage(message, type = 'info') {
        if (this.registerMessage) {
            this.registerMessage.textContent = message;
            this.registerMessage.className = `message ${type}`;
            this.registerMessage.style.display = 'block';
        }
    }

    /**
     * Limpia mensajes
     */
    clearMessages() {
        [this.loginMessage, this.registerMessage].forEach(el => {
            if (el) {
                el.textContent = '';
                el.style.display = 'none';
            }
        });
    }

    /**
     * Obtiene el usuario actual
     */
    getCurrentUser() {
        return this.usuario;
    }

    /**
     * Verifica si el usuario tiene un rol específico
     */
    hasRole(role) {
        return this.isLoggedIn && this.usuario?.rol?.toLowerCase() === role.toLowerCase();
    }

    /**
     * Verifica si el usuario está autenticado
     */
    isAuthenticated() {
        return this.isLoggedIn;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Utils !== 'undefined' && typeof SUPERMERCADO_CONFIG !== 'undefined') {
        window.authManager = new AuthManager();
    } else {
        console.error('AuthManager: Dependencias no encontradas (Utils, SUPERMERCADO_CONFIG)');
    }
});