// carousel.js - Módulo para manejo del carrusel de productos

/**
 * Manejador del carrusel de productos
 */
class CarouselManager {
    constructor() {
        this.track = null;
        this.container = null;
        this.btnPrev = null;
        this.btnNext = null;
        this.isInitialized = false;
        this.init();
    }

    /**
     * Inicializa el carrusel
     */
    init() {
        this.findElements();
        if (this.track) {
            this.setupCarousel();
            this.bindEvents();
            this.loadProducts();
            console.log('🎠 CarouselManager inicializado');
        } else {
            console.warn('🎠 CarouselManager: No se encontró el track del carrusel');
        }
    }

    /**
     * Encuentra los elementos del carrusel
     */
    findElements() {
        this.track = Utils.safeQuery('#carrusel-dinamico-container') || Utils.safeQuery('.carousel-track');
        if (this.track) {
            this.container = this.track.closest('.carrusel-container') || this.track.parentElement;
            this.btnPrev = this.container?.querySelector('.prev') || Utils.safeQuery('.carrusel-btn.prev');
            this.btnNext = this.container?.querySelector('.next') || Utils.safeQuery('.carrusel-btn.next');
        }
    }

    /**
     * Configura los estilos y propiedades del carrusel
     */
    setupCarousel() {
        if (!this.track) return;

        // Configurar estilos del track
        Object.assign(this.track.style, {
            display: 'flex',
            overflowX: 'auto',
            scrollBehavior: 'smooth',
            gap: '1rem',
            scrollbarWidth: 'none',
            msOverflowStyle: 'none'
        });

        // Ocultar scrollbar en webkit
        if (!document.getElementById('carousel-scrollbar-styles')) {
            const style = document.createElement('style');
            style.id = 'carousel-scrollbar-styles';
            style.textContent = `
                .carousel-track::-webkit-scrollbar,
                #carrusel-dinamico-container::-webkit-scrollbar {
                    display: none;
                }
            `;
            document.head.appendChild(style);
        }

        this.isInitialized = true;
    }

    /**
     * Vincula los eventos del carrusel
     */
    bindEvents() {
        if (!this.isInitialized) return;

        // Limpiar eventos anteriores
        this.clearHandlers();

        // Configurar botón siguiente
        if (this.btnNext) {
            const handlerNext = (e) => {
                e.preventDefault();
                this.scrollNext();
            };
            this.btnNext.addEventListener('click', handlerNext);
            this.btnNext.__carouselNext = handlerNext;
            this.setupButtonAccessibility(this.btnNext);
        }

        // Configurar botón anterior
        if (this.btnPrev) {
            const handlerPrev = (e) => {
                e.preventDefault();
                this.scrollPrev();
            };
            this.btnPrev.addEventListener('click', handlerPrev);
            this.btnPrev.__carouselPrev = handlerPrev;
            this.setupButtonAccessibility(this.btnPrev);
        }

        // Evento de scroll para actualizar botones
        if (this.track) {
            this.track.addEventListener('scroll', () => {
                requestAnimationFrame(() => this.updateButtonStates());
            });
        }

        // Actualizar estado inicial
        setTimeout(() => this.updateButtonStates(), 100);
    }

    /**
     * Configura la accesibilidad de los botones
     */
    setupButtonAccessibility(button) {
        if (!button) return;

        button.setAttribute('tabindex', '0');
        button.style.cursor = 'pointer';
        
        button.addEventListener('keyup', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                button.click();
            }
        });
    }

    /**
     * Limpia los manejadores de eventos anteriores
     */
    clearHandlers() {
        [this.btnNext, this.btnPrev].forEach(btn => {
            if (!btn) return;
            
            const nextHandler = btn.__carouselNext;
            const prevHandler = btn.__carouselPrev;
            
            if (nextHandler) {
                btn.removeEventListener('click', nextHandler);
                btn.__carouselNext = null;
            }
            
            if (prevHandler) {
                btn.removeEventListener('click', prevHandler);
                btn.__carouselPrev = null;
            }
        });
    }

    /**
     * Calcula la distancia de scroll
     */
    calculateScrollStep() {
        if (!this.track) return 270;

        const firstCard = this.track.querySelector('.producto-card') || this.track.firstElementChild;
        if (!firstCard) return 270;

        const cardRect = firstCard.getBoundingClientRect();
        const gap = parseFloat(getComputedStyle(this.track).gap || '16');
        return Math.ceil(cardRect.width + gap);
    }

    /**
     * Scroll hacia la siguiente sección
     */
    scrollNext() {
        if (!this.track) return;
        
        const step = this.calculateScrollStep();
        this.track.scrollBy({ left: step, behavior: 'smooth' });
        console.log('🎠 Scroll siguiente:', step);
    }

    /**
     * Scroll hacia la sección anterior
     */
    scrollPrev() {
        if (!this.track) return;
        
        const step = this.calculateScrollStep();
        this.track.scrollBy({ left: -step, behavior: 'smooth' });
        console.log('🎠 Scroll anterior:', -step);
    }

    /**
     * Actualiza el estado de los botones
     */
    updateButtonStates() {
        if (!this.track) return;

        const isAtStart = this.track.scrollLeft <= 1;
        const isAtEnd = this.track.scrollLeft + this.track.clientWidth >= this.track.scrollWidth - 1;

        if (this.btnPrev) {
            this.btnPrev.disabled = isAtStart;
            this.btnPrev.style.opacity = isAtStart ? '0.5' : '1';
            this.btnPrev.style.pointerEvents = isAtStart ? 'none' : 'auto';
        }

        if (this.btnNext) {
            this.btnNext.disabled = isAtEnd;
            this.btnNext.style.opacity = isAtEnd ? '0.5' : '1';
            this.btnNext.style.pointerEvents = isAtEnd ? 'none' : 'auto';
        }
    }

    /**
     * Carga los productos en el carrusel
     */
    async loadProducts() {
        if (!this.track) return;

        try {
            console.log('🛒 Cargando productos para carrusel...');
            
            const response = await fetch(SUPERMERCADO_CONFIG.BASE_URL + SUPERMERCADO_CONFIG.API_ENDPOINTS.PRODUCTOS, {
                cache: 'no-store',
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const html = await response.text();
            this.processProductsHTML(html);
            
        } catch (error) {
            console.error('Error al cargar productos:', error);
            this.showError();
        }
    }

    /**
     * Procesa el HTML de productos
     */
    processProductsHTML(html) {
        const temp = document.createElement('div');
        temp.innerHTML = html.trim();

        const fetchedContainer = temp.querySelector('.carrusel-container');
        const fetchedTrack = temp.querySelector('.carousel-track');

        if (fetchedContainer) {
            // Reemplazar contenedor completo
            const existingContainer = Utils.safeQuery('.carrusel-container');
            const newTrack = fetchedContainer.querySelector('.carousel-track');
            
            if (newTrack) {
                newTrack.id = 'carrusel-dinamico-container';
            }
            
            if (existingContainer && existingContainer.parentNode) {
                existingContainer.parentNode.replaceChild(fetchedContainer, existingContainer);
            } else {
                Utils.safeQuery('main')?.appendChild(fetchedContainer);
            }
            
            // Reinicializar con nuevo contenedor
            setTimeout(() => this.reinitialize(), 100);
            
        } else if (fetchedTrack) {
            // Solo actualizar contenido del track
            this.track.innerHTML = fetchedTrack.innerHTML;
            setTimeout(() => this.updateButtonStates(), 100);
            
        } else {
            // Contenido directo
            this.track.innerHTML = temp.innerHTML;
            setTimeout(() => this.updateButtonStates(), 100);
        }

        console.log('✅ Productos cargados en carrusel');
    }

    /**
     * Reinicializa el carrusel después de cambios en el DOM
     */
    reinitialize() {
        this.isInitialized = false;
        this.init();
    }

    /**
     * Muestra error en caso de fallo
     */
    showError() {
        if (this.track) {
            this.track.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: #666;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error al cargar productos</p>
                    <button onclick="window.carouselManager?.loadProducts()" style="padding: 0.5rem 1rem; margin-top: 1rem;">
                        Reintentar
                    </button>
                </div>
            `;
        }
    }

    /**
     * Agrega un producto al carrusel dinámicamente
     */
    addProduct(productHTML) {
        if (!this.track) return;
        
        const productElement = document.createElement('div');
        productElement.innerHTML = productHTML;
        this.track.appendChild(productElement.firstElementChild);
        
        setTimeout(() => this.updateButtonStates(), 100);
    }

    /**
     * Refresca el carrusel
     */
    refresh() {
        this.loadProducts();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Utils !== 'undefined' && typeof SUPERMERCADO_CONFIG !== 'undefined') {
        window.carouselManager = new CarouselManager();
    } else {
        console.error('CarouselManager: Dependencias no encontradas (Utils, SUPERMERCADO_CONFIG)');
    }
});