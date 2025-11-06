/**
 * Módulo Principal del Catálogo
 * Une ProductsLoader, CategoryFilter y Carrusel
 */

import { ProductsLoader } from '../modules/products-loader.js';
import { CategoryFilter } from './category-filter.js';
import { Carrusel } from './carrusel.js';

export class Catalogo {
    constructor(baseURL = '/proyecto_supermercado/') {
        this.baseURL = baseURL;
        this.productsLoader = new ProductsLoader(baseURL);
        this.categoryFilter = new CategoryFilter(this.productsLoader);
        this.carrusel = new Carrusel();
        this.init();
    }

    /**
     * Inicializa el catálogo completo
     */
    init() {
        this.configurarEventos();
        this.cargarInicial();
        console.log('🏪 Catálogo inicializado completamente');
    }

    /**
     * Configura los event listeners globales
     */
    configurarEventos() {
        // Escuchar cuando se actualizan los productos para reiniciar el carrusel
        document.addEventListener('productosActualizados', (e) => {
            console.log('📦 Productos actualizados, reiniciando carrusel...');
            
            // Pequeño delay para que el DOM se actualice
            setTimeout(() => {
                this.carrusel.reiniciar();
            }, 100);
        });

        // Escuchar cambios de categoría
        document.addEventListener('categoriaSeleccionada', (e) => {
            const { categoria } = e.detail;
            console.log(`🏷️ Categoría seleccionada: ${categoria}`);
            this.onCategoriaSeleccionada(categoria);
        });

        // Escuchar cuando se cargan destacados
        document.addEventListener('destacadosSeleccionados', () => {
            console.log('⭐ Productos destacados cargados');
            this.onDestacadosCargados();
        });
    }

    /**
     * Carga inicial del catálogo
     */
    async cargarInicial() {
        console.log('🚀 Iniciando carga inicial del catálogo...');
        
        // Cargar productos destacados por defecto
        const exito = await this.productsLoader.cargarProductos();
        
        if (exito) {
            console.log('✅ Carga inicial completada');
        } else {
            console.error('❌ Error en la carga inicial');
        }
    }

    /**
     * Maneja la selección de una categoría
     */
    onCategoriaSeleccionada(categoria) {
        // Scroll al inicio del carrusel cuando se cambia de categoría
        setTimeout(() => {
            this.carrusel.scrollAlInicio();
        }, 200);
    }

    /**
     * Maneja cuando se cargan los destacados
     */
    onDestacadosCargados() {
        // Scroll al inicio cuando se cargan destacados
        setTimeout(() => {
            this.carrusel.scrollAlInicio();
        }, 200);
    }

    /**
     * Recarga todo el catálogo
     */
    async recargar() {
        console.log('🔄 Recargando catálogo completo...');
        
        const categoriaActual = this.categoryFilter.getCategoriaActual();
        
        if (categoriaActual) {
            await this.categoryFilter.seleccionarCategoriaPorNombre(categoriaActual);
        } else {
            await this.categoryFilter.cargarDestacados();
        }
    }

    /**
     * Navega a una categoría específica
     */
    async navegarACategoria(categoria) {
        return await this.categoryFilter.seleccionarCategoriaPorNombre(categoria);
    }

    /**
     * Vuelve a productos destacados
     */
    async volverADestacados() {
        await this.categoryFilter.reset();
    }

    /**
     * Obtiene estadísticas del catálogo
     */
    getEstadisticas() {
        return {
            categoriaActual: this.categoryFilter.getCategoriaActual(),
            categoriasDisponibles: this.categoryFilter.getCategoriasDisponibles(),
            numeroProductos: this.productsLoader.getNumeroProductos(),
            carruselDisponible: this.carrusel.estaDisponible(),
            cargando: this.productsLoader.estaCargando()
        };
    }

    /**
     * Obtiene la instancia del loader de productos
     */
    getProductsLoader() {
        return this.productsLoader;
    }

    /**
     * Obtiene la instancia del filtro de categorías
     */
    getCategoryFilter() {
        return this.categoryFilter;
    }

    /**
     * Obtiene la instancia del carrusel
     */
    getCarrusel() {
        return this.carrusel;
    }

    /**
     * Busca productos (funcionalidad futura)
     */
    async buscarProductos(termino) {
        console.log(`🔍 Búsqueda futura implementada para: ${termino}`);
        // TODO: Implementar búsqueda cuando esté el endpoint
    }

    /**
     * Limpia todo el catálogo
     */
    limpiar() {
        this.productsLoader.limpiar();
        this.categoryFilter.limpiarSeleccion();
    }
}

// Auto-inicialización cuando se carga el DOM
document.addEventListener("DOMContentLoaded", () => {
    // Detectar la base URL automáticamente
    const detectedBase = (function() {
        const baseEl = document.querySelector('base');
        if (baseEl) {
            let b = baseEl.getAttribute('href') || '/';
            return b.endsWith('/') ? b : b + '/';
        }
        const parts = window.location.pathname.split('/').filter(Boolean);
        if (parts.length > 0) {
            return '/' + parts[0] + '/';
        }
        return '/';
    })();

    window.catalogo = new Catalogo(detectedBase);
});