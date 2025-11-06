/**
 * 🚀 APLICACIÓN PRINCIPAL - SUPERMERCADO ONLINE
 * 
 * Este archivo inicializa y coordina todos los módulos de la aplicación.
 * Mantiene la compatibilidad con el sistema existente mientras proporciona
 * una estructura modular y mantenible.
 */

import { Auth } from './modules/auth.js';
import { MenuLateral } from './components/menu-lateral.js';
import { GestionRoles } from './modules/gestion-roles.js';
import { Carrusel } from './components/carrusel.js';
import { ProductsLoader } from './modules/products-loader.js';

/**
 * Clase principal de la aplicación
 */
class SupermercadoApp {
    constructor() {
        this.baseURL = this.detectarBaseURL();
        this.modulos = {};
        this.init();
    }

    /**
     * Detecta la URL base automáticamente
     */
    detectarBaseURL() {
        const baseEl = document.querySelector('base');
        if (baseEl) {
            let href = baseEl.getAttribute('href') || '/';
            return href.endsWith('/') ? href : href + '/';
        }

        const parts = window.location.pathname.split('/').filter(Boolean);
        if (parts.length > 0) {
            return '/' + parts[0] + '/';
        }
        return '/';
    }

    /**
     * Inicializa la aplicación
     */
    async init() {
        console.log('🚀 Inicializando Supermercado App...');
        console.log('📍 Base URL detectada:', this.baseURL);

        try {
            // Inicializar módulos en orden
            await this.inicializarModulos();
            
            // Configurar eventos globales
            this.configurarEventosGlobales();
            
            // Configurar la funcionalidad de productos si existe el contenedor
            this.configurarProductos();
            
            console.log('✅ Aplicación inicializada correctamente');
            this.mostrarEstadisticas();
            
        } catch (error) {
            console.error('❌ Error al inicializar la aplicación:', error);
        }
    }

    /**
     * Inicializa todos los módulos
     */
    async inicializarModulos() {
        console.log('🔧 Inicializando módulos...');

        // Autenticación (siempre primero)
        this.modulos.auth = new Auth(this.baseURL);
        
        // Menú lateral
        this.modulos.menuLateral = new MenuLateral();
        
        // Gestión de roles (depende de auth)
        this.modulos.gestionRoles = new GestionRoles();
        
        // Carrusel (si existe)
        if (document.querySelector('.carrusel-container') || document.getElementById('carrusel-dinamico-container')) {
            this.modulos.carrusel = new Carrusel();
        }
        
        // Loader de productos (si existe el contenedor)
        if (document.getElementById('carrusel-dinamico-container')) {
            this.modulos.productsLoader = new ProductsLoader(this.baseURL);
        }

        console.log('📦 Módulos inicializados:', Object.keys(this.modulos));
    }

    /**
     * Configura eventos globales de la aplicación
     */
    configurarEventosGlobales() {
        // Evento cuando cambia la sesión
        document.addEventListener('sessionChanged', (e) => {
            const { rol, nombre, logged_in } = e.detail;
            console.log('🔐 Sesión actualizada:', { rol, nombre, logged_in });
            
            // Aquí se pueden agregar acciones globales cuando cambia la sesión
            this.onSesionCambiada(e.detail);
        });

        // Evento cuando se actualizan productos
        document.addEventListener('productosActualizados', (e) => {
            console.log('📦 Productos actualizados');
            this.onProductosActualizados(e.detail);
        });

        // Manejo de errores globales
        window.addEventListener('error', (e) => {
            console.error('🐛 Error global capturado:', e.error);
        });

        // Manejo de promesas rechazadas
        window.addEventListener('unhandledrejection', (e) => {
            console.error('🚫 Promesa rechazada:', e.reason);
        });
    }

    /**
     * Configura la funcionalidad específica de productos
     */
    configurarProductos() {
        if (this.modulos.productsLoader) {
            // Cargar productos iniciales
            this.modulos.productsLoader.cargarProductos().then(() => {
                console.log('📦 Productos iniciales cargados');
            });
        }
    }

    /**
     * Maneja cambios de sesión
     */
    onSesionCambiada(detalles) {
        const { logged_in, rol } = detalles;
        
        // Recargar carrito si hay sesión activa
        if (logged_in && window.carrito) {
            window.carrito.getEvents().verificarSesionYCargar();
        }
        
        // Otras acciones según el rol...
        if (rol === 'admin') {
            console.log('👑 Usuario admin detectado');
        } else if (rol === 'empleado') {
            console.log('👷 Usuario empleado detectado');
        }
    }

    /**
     * Maneja actualizaciones de productos
     */
    onProductosActualizados(detalles) {
        // Reinicializar carrusel si existe
        if (this.modulos.carrusel) {
            setTimeout(() => {
                this.modulos.carrusel.reiniciar();
            }, 100);
        }
    }

    /**
     * Obtiene un módulo específico
     */
    getModulo(nombre) {
        return this.modulos[nombre];
    }

    /**
     * Obtiene todos los módulos
     */
    getModulos() {
        return this.modulos;
    }

    /**
     * Obtiene la URL base
     */
    getBaseURL() {
        return this.baseURL;
    }

    /**
     * Muestra estadísticas de la aplicación
     */
    mostrarEstadisticas() {
        console.log('📊 Estadísticas de la aplicación:');
        console.log('- Base URL:', this.baseURL);
        console.log('- Módulos activos:', Object.keys(this.modulos).length);
        console.log('- Carrito disponible:', !!window.carrito);
        console.log('- Catálogo disponible:', !!window.catalogo);
    }

    /**
     * Recargar toda la aplicación
     */
    async recargar() {
        console.log('🔄 Recargando aplicación...');
        
        // Recargar productos si existe el loader
        if (this.modulos.productsLoader) {
            await this.modulos.productsLoader.recargar();
        }
        
        // Verificar sesión
        if (this.modulos.auth) {
            await this.modulos.auth.verificarSesion();
        }
        
        console.log('✅ Aplicación recargada');
    }

    /**
     * Información de depuración
     */
    debug() {
        return {
            baseURL: this.baseURL,
            modulos: Object.keys(this.modulos),
            carrito: window.carrito ? 'disponible' : 'no disponible',
            catalogo: window.catalogo ? 'disponible' : 'no disponible',
            usuario: this.modulos.auth?.getUsuarioActual() || null
        };
    }
}

// Auto-inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', () => {
    // Crear instancia global de la aplicación
    window.supermercadoApp = new SupermercadoApp();
    
    // Exponer utilidades para debug
    window.__SUPERMERCADO_DEBUG = {
        app: window.supermercadoApp,
        getDebugInfo: () => window.supermercadoApp.debug(),
        recargar: () => window.supermercadoApp.recargar()
    };
});

// Exportar para uso como módulo si es necesario
export default SupermercadoApp;