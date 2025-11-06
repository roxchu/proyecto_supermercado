/**
 * Módulo del Carrusel de Productos
 * Maneja la navegación y comportamiento del carrusel
 */

export class Carrusel {
    constructor() {
        this.track = null;
        this.btnPrev = null;
        this.btnNext = null;
        this.cardWidth = 270;
        this.gap = 0;
        this.init();
    }

    /**
     * Inicializa el carrusel
     */
    init() {
        this.encontrarElementos();
        if (this.track) {
            this.configurarCarrusel();
        }
    }

    /**
     * Encuentra los elementos del carrusel en el DOM
     */
    encontrarElementos() {
        this.track = document.getElementById('carrusel-dinamico-container') || 
                    document.querySelector('.carousel-track');

        if (!this.track) {
            console.warn('Carousel: track no encontrado');
            return;
        }

        const container = this.track.closest('.carrusel-container') || this.track.parentElement;
        
        // Buscar botones con diferentes clases posibles
        this.btnPrev = container ? 
            (container.querySelector('.prev') || container.querySelector('.carrusel-btn.prev')) : 
            (document.querySelector('.prev') || document.querySelector('.carrusel-btn.prev'));
            
        this.btnNext = container ? 
            (container.querySelector('.next') || container.querySelector('.carrusel-btn.next')) : 
            (document.querySelector('.next') || document.querySelector('.carrusel-btn.next'));
            
        console.log('🔍 Elementos encontrados:', {
            track: !!this.track,
            btnPrev: !!this.btnPrev,
            btnNext: !!this.btnNext
        });
    }

    /**
     * Configura el comportamiento del carrusel
     */
    configurarCarrusel() {
        this.configurarEstilos();
        this.calcularDimensiones();
        this.configurarBotones();
        this.configurarAccesibilidad();
        this.configurarObservador();
        this.actualizarEstadoBotones();
    }

    /**
     * Configura estilos básicos del track
     */
    configurarEstilos() {
        this.track.style.overflowX = 'auto';
        this.track.style.scrollBehavior = 'smooth';
    }

    /**
     * Calcula las dimensiones de las tarjetas
     */
    calcularDimensiones() {
        const firstCard = this.track.querySelector('.producto-card') || this.track.firstElementChild;
        
        if (firstCard) {
            const styles = getComputedStyle(this.track);
            this.gap = parseFloat(styles.gap || 0);
            this.cardWidth = Math.ceil(firstCard.getBoundingClientRect().width + this.gap);
        }
        
        console.log(`📐 Carrusel configurado: cardWidth=${this.cardWidth}, gap=${this.gap}`);
    }

    /**
     * Configura los botones de navegación
     */
    configurarBotones() {
        // Limpiar handlers anteriores
        this.limpiarHandlers();

        // Configurar botón siguiente
        if (this.btnNext) {
            const handlerNext = () => this.scrollHorizontal(this.cardWidth);
            this.btnNext.addEventListener('click', handlerNext);
            this.btnNext.__carouselNext = handlerNext;
        }

        // Configurar botón anterior
        if (this.btnPrev) {
            const handlerPrev = () => this.scrollHorizontal(-this.cardWidth);
            this.btnPrev.addEventListener('click', handlerPrev);
            this.btnPrev.__carouselPrev = handlerPrev;
        }
    }

    /**
     * Limpia handlers anteriores para evitar duplicados
     */
    limpiarHandlers() {
        if (this.btnNext && this.btnNext.__carouselNext) {
            this.btnNext.removeEventListener('click', this.btnNext.__carouselNext);
            this.btnNext.__carouselNext = null;
        }
        if (this.btnPrev && this.btnPrev.__carouselPrev) {
            this.btnPrev.removeEventListener('click', this.btnPrev.__carouselPrev);
            this.btnPrev.__carouselPrev = null;
        }
    }

    /**
     * Realiza scroll horizontal
     */
    scrollHorizontal(distancia) {
        if (!this.track) return;
        
        this.track.scrollBy({
            left: distancia,
            behavior: 'smooth'
        });
    }

    /**
     * Configura accesibilidad para teclado
     */
    configurarAccesibilidad() {
        [this.btnPrev, this.btnNext].forEach(btn => {
            if (!btn) return;
            
            btn.setAttribute('tabindex', '0');
            btn.style.cursor = 'pointer';
            
            btn.addEventListener('keyup', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    btn.click();
                }
            });
        });
    }

    /**
     * Configura observador para cambios en el contenido
     */
    configurarObservador() {
        if (!this.track) return;

        // Observar scroll para actualizar botones
        this.track.addEventListener('scroll', () => {
            requestAnimationFrame(() => this.actualizarEstadoBotones());
        });

        // Observar cambios en el contenido
        const observer = new MutationObserver(() => {
            this.calcularDimensiones();
            this.actualizarEstadoBotones();
        });

        observer.observe(this.track, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Actualiza el estado de los botones (habilitado/deshabilitado)
     */
    actualizarEstadoBotones() {
        if (!this.track) return;

        const isAtStart = this.track.scrollLeft <= 1;
        const isAtEnd = this.track.scrollLeft + this.track.clientWidth >= this.track.scrollWidth - 1;

        if (this.btnPrev) {
            this.btnPrev.disabled = isAtStart;
            this.btnPrev.style.opacity = isAtStart ? '0.5' : '1';
        }

        if (this.btnNext) {
            this.btnNext.disabled = isAtEnd;
            this.btnNext.style.opacity = isAtEnd ? '0.5' : '1';
        }
    }

    /**
     * Reinicia el carrusel (útil cuando se actualiza el contenido)
     */
    reiniciar() {
        console.log('🔄 Reiniciando carrusel...');
        this.init();
    }

    /**
     * Scroll al inicio
     */
    scrollAlInicio() {
        if (!this.track) return;
        this.track.scrollTo({ left: 0, behavior: 'smooth' });
    }

    /**
     * Scroll al final
     */
    scrollAlFinal() {
        if (!this.track) return;
        this.track.scrollTo({ left: this.track.scrollWidth, behavior: 'smooth' });
    }

    /**
     * Scroll a una posición específica
     */
    scrollAPosicion(index) {
        if (!this.track) return;
        const posicion = index * this.cardWidth;
        this.track.scrollTo({ left: posicion, behavior: 'smooth' });
    }

    /**
     * Obtiene el índice actual visible
     */
    getIndiceActual() {
        if (!this.track) return 0;
        return Math.round(this.track.scrollLeft / this.cardWidth);
    }

    /**
     * Obtiene el número total de elementos
     */
    getTotalElementos() {
        if (!this.track) return 0;
        return this.track.children.length;
    }

    /**
     * Verifica si el carrusel existe y está funcional
     */
    estaDisponible() {
        return !!(this.track && (this.btnNext || this.btnPrev));
    }
}