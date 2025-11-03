// utils.js - Utilidades comunes para todo el proyecto

/**
 * Clase principal para utilidades del proyecto
 */
class Utils {
    
    /**
     * Detecta la URL base del proyecto
     * @returns {string} URL base
     */
    static detectarBase() {
        const baseEl = document.querySelector('base');
        if (baseEl) {
            let base = baseEl.getAttribute('href') || '/';
            return base.endsWith('/') ? base : base + '/';
        }
        const parts = window.location.pathname.split('/').filter(Boolean);
        if (parts.length > 0) {
            return '/' + parts[0] + '/';
        }
        return '/';
    }

    /**
     * Escapa HTML para evitar XSS
     * @param {string} text - Texto a escapar
     * @returns {string} Texto escapado
     */
    static escapeHtml(text) {
        if (text === null || text === undefined) return "";
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/"/g, "&quot;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
    }

    /**
     * Selecciona elemento de forma segura
     * @param {string} selector - Selector CSS
     * @returns {Element|null} Elemento o null
     */
    static safeQuery(selector) {
        try { 
            return document.querySelector(selector); 
        } catch (e) { 
            console.warn('Selector inválido:', selector);
            return null; 
        }
    }

    /**
     * Formatea precio en formato de moneda
     * @param {number} precio - Precio a formatear
     * @returns {string} Precio formateado
     */
    static formatearPrecio(precio) {
        return new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS'
        }).format(precio);
    }

    /**
     * Hace una petición fetch segura
     * @param {string} url - URL a consultar
     * @param {object} options - Opciones de fetch
     * @returns {Promise} Promesa de la respuesta
     */
    static async fetchSeguro(url, options = {}) {
        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en fetch:', error);
            throw error;
        }
    }

    /**
     * Debounce para funciones
     * @param {Function} func - Función a debounce
     * @param {number} wait - Tiempo de espera en ms
     * @returns {Function} Función debounced
     */
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Configuración global del proyecto
window.SUPERMERCADO_CONFIG = {
    BASE_URL: Utils.detectarBase(),
    API_ENDPOINTS: {
        PRODUCTOS: 'productos.php',
        CARRITO_AGREGAR: 'carrito/agregar_carrito.php',
        CARRITO_OBTENER: 'carrito/obtener_carrito.php',
        CARRITO_ELIMINAR: 'carrito/eliminar_item.php',
        LOGIN: 'login/login.php',
        LOGOUT: 'login/logout.php',
        REGISTRO: 'login/registro.php'
    },
    DEBOUNCE_TIME: 300,
    NOTIFICATION_DURATION: 3000
};

// Exportar para módulos
window.Utils = Utils;