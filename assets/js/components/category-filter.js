/**
 * Módulo de Filtros de Categorías
 * Maneja la navegación por categorías y filtros
 */

export class CategoryFilter {
    constructor(productsLoader) {
        this.productsLoader = productsLoader;
        this.categoriaActual = null;
        this.links = [];
        this.init();
    }

    /**
     * Inicializa el filtro de categorías
     */
    init() {
        this.obtenerLinks();
        this.configurarEventos();
        console.log('🏷️ CategoryFilter inicializado');
    }

    /**
     * Obtiene todos los enlaces de categorías
     */
    obtenerLinks() {
        this.links = document.querySelectorAll(".side-link[data-categoria]");
        console.log(`Encontrados ${this.links.length} enlaces de categoría`);
    }

    /**
     * Configura los event listeners
     */
    configurarEventos() {
        this.links.forEach(link => {
            link.addEventListener("click", (e) => {
                e.preventDefault();
                this.seleccionarCategoria(link);
            });
        });

        // Escuchar cuando se cierran menús para cargar destacados
        document.addEventListener('menuCerrado', () => {
            if (this.categoriaActual) {
                this.cargarDestacados();
            }
        });
    }

    /**
     * Selecciona una categoría específica
     */
    async seleccionarCategoria(link) {
        const categoria = link.dataset.categoria;
        
        if (categoria === this.categoriaActual) {
            console.log('La categoría ya está seleccionada:', categoria);
            return;
        }

        console.log("Categoría seleccionada:", categoria);
        
        // Actualizar estado visual
        this.actualizarEstadoVisual(link);
        
        // Cargar productos de la categoría
        const exito = await this.productsLoader.cargarProductos(categoria);
        
        if (exito) {
            this.categoriaActual = categoria;
            
            // Disparar evento de cambio de categoría
            document.dispatchEvent(new CustomEvent('categoriaSeleccionada', {
                detail: { categoria, link }
            }));
        } else {
            // Restaurar estado anterior si falló
            this.restaurarEstadoAnterior();
        }
    }

    /**
     * Carga productos destacados (sin categoría)
     */
    async cargarDestacados() {
        console.log("Cargando productos destacados");
        
        // Limpiar selección visual
        this.limpiarSeleccion();
        
        const exito = await this.productsLoader.cargarProductos();
        
        if (exito) {
            this.categoriaActual = null;
            
            document.dispatchEvent(new CustomEvent('destacadosSeleccionados'));
        }
    }

    /**
     * Actualiza el estado visual del enlace seleccionado
     */
    actualizarEstadoVisual(linkSeleccionado) {
        // Remover clase activa de todos los enlaces
        this.links.forEach(link => {
            link.classList.remove('active', 'selected');
            link.removeAttribute('aria-current');
        });
        
        // Agregar clase activa al enlace seleccionado
        linkSeleccionado.classList.add('active', 'selected');
        linkSeleccionado.setAttribute('aria-current', 'page');
        
        // Agregar efecto visual temporal
        linkSeleccionado.style.transform = 'scale(0.98)';
        setTimeout(() => {
            linkSeleccionado.style.transform = '';
        }, 150);
    }

    /**
     * Limpia la selección visual
     */
    limpiarSeleccion() {
        this.links.forEach(link => {
            link.classList.remove('active', 'selected');
            link.removeAttribute('aria-current');
        });
    }

    /**
     * Restaura el estado anterior (en caso de error)
     */
    restaurarEstadoAnterior() {
        if (this.categoriaActual) {
            const linkActual = this.encontrarLinkPorCategoria(this.categoriaActual);
            if (linkActual) {
                this.actualizarEstadoVisual(linkActual);
            }
        } else {
            this.limpiarSeleccion();
        }
    }

    /**
     * Encuentra un enlace por nombre de categoría
     */
    encontrarLinkPorCategoria(categoria) {
        return Array.from(this.links).find(link => link.dataset.categoria === categoria);
    }

    /**
     * Obtiene la categoría actualmente seleccionada
     */
    getCategoriaActual() {
        return this.categoriaActual;
    }

    /**
     * Obtiene todos los nombres de categorías disponibles
     */
    getCategoriasDisponibles() {
        return Array.from(this.links).map(link => link.dataset.categoria);
    }

    /**
     * Selecciona una categoría por nombre
     */
    async seleccionarCategoriaPorNombre(nombreCategoria) {
        const link = this.encontrarLinkPorCategoria(nombreCategoria);
        if (link) {
            await this.seleccionarCategoria(link);
            return true;
        }
        console.warn(`Categoría no encontrada: ${nombreCategoria}`);
        return false;
    }

    /**
     * Verifica si hay una categoría seleccionada
     */
    haySeleccion() {
        return this.categoriaActual !== null;
    }

    /**
     * Resetea a productos destacados
     */
    async reset() {
        await this.cargarDestacados();
    }
}