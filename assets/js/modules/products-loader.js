/**
 * Módulo de Carga de Productos
 * Maneja la carga de productos desde el servidor
 */

export class ProductsLoader {
    constructor(baseURL = '/proyecto_supermercado/') {
        this.baseURL = baseURL;
        this.contenedor = null;
        this.cargando = false;
        this.init();
    }

    /**
     * Inicializa el loader
     */
    init() {
        this.contenedor = document.getElementById("carrusel-dinamico-container");
        if (!this.contenedor) {
            console.warn('ProductsLoader: Contenedor no encontrado');
            return;
        }
        console.log('📦 ProductsLoader inicializado');
    }

    /**
     * Carga productos por categoría o destacados
     */
    async cargarProductos(categoria = null) {
        if (!this.contenedor) {
            console.error('No hay contenedor disponible para cargar productos');
            return false;
        }

        if (this.cargando) {
            console.log('Ya hay una carga en progreso...');
            return false;
        }

        this.cargando = true;
        this.mostrarCargando(categoria);

        const url = categoria
            ? `${this.baseURL}productos.php?categoria=${encodeURIComponent(categoria)}`
            : `${this.baseURL}productos.php`;

        try {
            console.log(`📡 Cargando productos desde: ${url}`);
            
            const response = await fetch(url, {
                cache: 'no-store',
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const html = await response.text();
            this.renderizarProductos(html);
            
            // Disparar evento de productos cargados
            document.dispatchEvent(new CustomEvent('productosActualizados', {
                detail: { categoria, contenedor: this.contenedor }
            }));

            console.log('✅ Productos cargados exitosamente');
            return true;

        } catch (error) {
            console.error('❌ Error al cargar productos:', error);
            this.mostrarError(error.message);
            return false;
        } finally {
            this.cargando = false;
        }
    }

    /**
     * Muestra el estado de carga
     */
    mostrarCargando(categoria) {
        const texto = categoria 
            ? `Cargando ${categoria}...` 
            : "Cargando productos destacados...";
        
        this.contenedor.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #007bff;"></i>
                <p style="margin-top: 10px; color: #666;">${texto}</p>
            </div>
        `;
    }

    /**
     * Muestra error en la carga
     */
    mostrarError(mensaje) {
        this.contenedor.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 24px; color: #dc3545;"></i>
                <p style="color: #dc3545; margin-top: 10px;">Error al cargar productos</p>
                <p style="color: #666; font-size: 14px;">${mensaje}</p>
                <button onclick="window.location.reload()" style="margin-top: 15px; padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Reintentar
                </button>
            </div>
        `;
    }

    /**
     * Renderiza los productos recibidos
     */
    renderizarProductos(html) {
        if (!html || html.trim() === '') {
            this.mostrarVacio();
            return;
        }

        // Crear elemento temporal para procesar el HTML
        const temp = document.createElement('div');
        temp.innerHTML = html.trim();

        // Buscar el contenedor de productos en la respuesta
        const productosContainer = temp.querySelector('.carousel-track') || temp;
        
        if (productosContainer && productosContainer.children.length > 0) {
            this.contenedor.innerHTML = productosContainer.innerHTML;
        } else {
            this.mostrarVacio();
        }
    }

    /**
     * Muestra mensaje cuando no hay productos
     */
    mostrarVacio() {
        this.contenedor.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-box-open" style="font-size: 24px; color: #999;"></i>
                <p style="color: #999; margin-top: 10px;">No hay productos disponibles en esta categoría</p>
            </div>
        `;
    }

    /**
     * Recarga los productos actuales
     */
    async recargar() {
        // Por ahora recarga los destacados, se puede mejorar para recordar la categoría actual
        return await this.cargarProductos();
    }

    /**
     * Obtiene el contenedor actual
     */
    getContenedor() {
        return this.contenedor;
    }

    /**
     * Verifica si está cargando
     */
    estaCargando() {
        return this.cargando;
    }

    /**
     * Obtiene el número de productos cargados
     */
    getNumeroProductos() {
        if (!this.contenedor) return 0;
        return this.contenedor.querySelectorAll('.producto-card, .carrusel-slide').length;
    }

    /**
     * Limpia el contenedor
     */
    limpiar() {
        if (this.contenedor) {
            this.contenedor.innerHTML = '';
        }
    }
}