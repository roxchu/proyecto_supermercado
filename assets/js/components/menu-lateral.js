/**
 * Módulo del Menú Lateral
 * Maneja la apertura y cierre del menú de categorías
 */

export class MenuLateral {
    constructor() {
        this.elementos = this.obtenerElementos();
        this.init();
    }

    /**
     * Obtiene referencias a elementos del DOM
     */
    obtenerElementos() {
        return {
            btnCategorias: document.getElementById('btn-categorias'),
            btnCloseMenu: document.getElementById('btn-close-menu'),
            sideMenu: document.getElementById('side-menu'),
            menuOverlay: document.getElementById('menu-overlay')
        };
    }

    /**
     * Inicializa los event listeners
     */
    init() {
        this.configurarEventos();
        this.debug();
    }

    /**
     * Configura todos los eventos del menú
     */
    configurarEventos() {
        const { btnCategorias, btnCloseMenu, menuOverlay } = this.elementos;

        // Abrir menú
        btnCategorias?.addEventListener('click', (e) => {
            e.preventDefault();
            console.log('Click en botón categorías');
            this.abrir();
        });

        // Cerrar menú
        btnCloseMenu?.addEventListener('click', () => this.cerrar());
        menuOverlay?.addEventListener('click', () => this.cerrar());

        // Cerrar con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.estaAbierto()) {
                this.cerrar();
            }
        });
    }

    /**
     * Abre el menú lateral
     */
    abrir() {
        const { sideMenu, menuOverlay } = this.elementos;
        
        console.log('Abriendo menú lateral');
        sideMenu?.classList.add('open');
        menuOverlay?.classList.add('active');
        
        // Evitar scroll del body cuando el menú está abierto
        document.body.style.overflow = 'hidden';
        
        // Enfocar el primer enlace para accesibilidad
        const primerEnlace = sideMenu?.querySelector('a');
        if (primerEnlace) {
            setTimeout(() => primerEnlace.focus(), 100);
        }
    }

    /**
     * Cierra el menú lateral
     */
    cerrar() {
        const { sideMenu, menuOverlay } = this.elementos;
        
        console.log('Cerrando menú lateral');
        sideMenu?.classList.remove('open');
        menuOverlay?.classList.remove('active');
        
        // Restaurar scroll del body
        document.body.style.overflow = 'auto';
    }

    /**
     * Alterna el estado del menú
     */
    alternar() {
        if (this.estaAbierto()) {
            this.cerrar();
        } else {
            this.abrir();
        }
    }

    /**
     * Verifica si el menú está abierto
     */
    estaAbierto() {
        const { sideMenu } = this.elementos;
        return sideMenu?.classList.contains('open') || false;
    }

    /**
     * Debug - verifica que los elementos existan
     */
    debug() {
        const { btnCategorias, btnCloseMenu, sideMenu, menuOverlay } = this.elementos;
        
        console.log('Elementos del menú:');
        console.log('btnCategorias:', btnCategorias ? '✅' : '❌');
        console.log('btnCloseMenu:', btnCloseMenu ? '✅' : '❌');
        console.log('sideMenu:', sideMenu ? '✅' : '❌');
        console.log('menuOverlay:', menuOverlay ? '✅' : '❌');
    }
}