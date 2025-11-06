/**
 * Módulo de Gestión de Roles
 * Maneja la visibilidad de elementos según el rol del usuario
 */

export class GestionRoles {
    constructor() {
        this.elementos = this.obtenerElementos();
        this.init();
    }

    /**
     * Obtiene referencias a elementos del DOM
     */
    obtenerElementos() {
        return {
            linkGestion: document.getElementById('link-gestion'),
            linkAdmin: document.getElementById('link-admin'),
            sideLinkGestion: document.querySelector('.side-nav li.empleado-only'),
            sideLinkAdmin: document.querySelector('.side-nav li.admin-only')
        };
    }

    /**
     * Inicializa el módulo
     */
    init() {
        this.configurarEventos();
        this.ocultarTodosLosElementos();
    }

    /**
     * Configura los event listeners
     */
    configurarEventos() {
        // Escuchar cambios de sesión
        document.addEventListener('sessionChanged', (e) => {
            const { rol } = e.detail;
            this.actualizarVisibilidad(rol);
        });

        // Protección de clicks en enlaces
        this.protegerEnlaces();
    }

    /**
     * Actualiza la visibilidad según el rol
     */
    actualizarVisibilidad(rol) {
        console.log('🔐 Actualizando visibilidad para rol:', rol);

        // Primero ocultar todos
        this.ocultarTodosLosElementos();

        if (!rol) return;

        // Mostrar elementos según el rol
        if (rol === 'admin' || rol === 'empleado') {
            this.mostrarGestion();
        }

        if (rol === 'admin') {
            this.mostrarAdmin();
        }
    }

    /**
     * Oculta todos los elementos de rol
     */
    ocultarTodosLosElementos() {
        const { linkGestion, linkAdmin, sideLinkGestion, sideLinkAdmin } = this.elementos;

        // Header links
        if (linkGestion) {
            linkGestion.classList.remove('show');
            linkGestion.style.display = 'none';
        }
        if (linkAdmin) {
            linkAdmin.classList.remove('show');
            linkAdmin.style.display = 'none';
        }

        // Sidebar links
        if (sideLinkGestion) sideLinkGestion.style.display = 'none';
        if (sideLinkAdmin) sideLinkAdmin.style.display = 'none';
    }

    /**
     * Muestra elementos de gestión (para admin y empleado)
     */
    mostrarGestion() {
        const { linkGestion, sideLinkGestion } = this.elementos;

        if (linkGestion) {
            linkGestion.classList.add('show');
            linkGestion.style.display = 'flex';
        }
        if (sideLinkGestion) {
            sideLinkGestion.style.display = 'block';
        }

        console.log('✅ Elementos de gestión mostrados');
    }

    /**
     * Muestra elementos de admin (solo para admin)
     */
    mostrarAdmin() {
        const { linkAdmin, sideLinkAdmin } = this.elementos;

        if (linkAdmin) {
            linkAdmin.classList.add('show');
            linkAdmin.style.display = 'flex';
        }
        if (sideLinkAdmin) {
            sideLinkAdmin.style.display = 'block';
        }

        console.log('✅ Elementos de admin mostrados');
    }

    /**
     * Protege los enlaces de acceso no autorizado
     */
    protegerEnlaces() {
        const { linkGestion, linkAdmin } = this.elementos;

        // El control final está en el servidor, pero agregamos protección JS
        linkGestion?.addEventListener('click', (e) => {
            if (linkGestion.style.display === 'none') {
                e.preventDefault();
                console.warn('🚫 Acceso denegado a gestión');
            }
        });

        linkAdmin?.addEventListener('click', (e) => {
            if (linkAdmin.style.display === 'none') {
                e.preventDefault();
                console.warn('🚫 Acceso denegado a admin');
            }
        });
    }

    /**
     * Verifica si un elemento está visible
     */
    estaVisible(elemento) {
        if (!elemento) return false;
        return elemento.style.display !== 'none' && elemento.classList.contains('show');
    }

    /**
     * Obtiene el estado actual de visibilidad
     */
    getEstadoVisibilidad() {
        const { linkGestion, linkAdmin } = this.elementos;
        
        return {
            gestion: this.estaVisible(linkGestion),
            admin: this.estaVisible(linkAdmin)
        };
    }
}