// ui.js - Módulo para manejo de interfaz de usuario y navegación

/**
 * Manejador de interfaz de usuario
 */
class UIManager {
    constructor() {
        this.sideMenu = null;
        this.menuOverlay = null;
        this.isMenuOpen = false;
        this.init();
    }

    /**
     * Inicializa el manejador de UI
     */
    init() {
        this.findElements();
        this.bindEvents();
        this.setupSearch();
        console.log('🎨 UIManager inicializado');
    }

    /**
     * Encuentra elementos del DOM
     */
    findElements() {
        this.sideMenu = Utils.safeQuery('#side-menu');
        this.menuOverlay = Utils.safeQuery('#menu-overlay');
        this.btnCategorias = Utils.safeQuery('#btn-categorias');
        this.btnCloseMenu = Utils.safeQuery('#btn-close-menu');
        this.searchInput = Utils.safeQuery('.search-bar input');
        this.searchButton = Utils.safeQuery('.search-bar button');
    }

    /**
     * Vincula eventos
     */
    bindEvents() {
        // Menú lateral
        if (this.btnCategorias) {
            this.btnCategorias.addEventListener('click', (e) => {
                e.preventDefault();
                this.openSideMenu();
            });
        }

        if (this.btnCloseMenu) {
            this.btnCloseMenu.addEventListener('click', () => this.closeSideMenu());
        }

        if (this.menuOverlay) {
            this.menuOverlay.addEventListener('click', () => this.closeSideMenu());
        }

        // Navegación por categorías
        this.bindCategoryNavigation();

        // Búsqueda
        if (this.searchButton) {
            this.searchButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }

        if (this.searchInput) {
            this.searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.performSearch();
                }
            });
        }

        // Atajos de teclado
        this.bindKeyboardShortcuts();

        // Responsive
        this.bindResponsiveEvents();
    }

    /**
     * Abre el menú lateral
     */
    openSideMenu() {
        if (this.sideMenu && this.menuOverlay) {
            this.sideMenu.classList.add('open');
            this.menuOverlay.classList.add('active');
            this.isMenuOpen = true;
            document.body.style.overflow = 'hidden';
            
            // Focus en primer enlace
            const firstLink = this.sideMenu.querySelector('a');
            if (firstLink) {
                setTimeout(() => firstLink.focus(), 100);
            }
        }
    }

    /**
     * Cierra el menú lateral
     */
    closeSideMenu() {
        if (this.sideMenu && this.menuOverlay) {
            this.sideMenu.classList.remove('open');
            this.menuOverlay.classList.remove('active');
            this.isMenuOpen = false;
            document.body.style.overflow = '';
        }
    }

    /**
     * Vincula navegación por categorías
     */
    bindCategoryNavigation() {
        const categoryLinks = document.querySelectorAll('.side-link[data-categoria]');
        
        categoryLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const categoria = link.dataset.categoria;
                this.filterByCategory(categoria);
                this.closeSideMenu();
            });
        });
    }

    /**
     * Filtra productos por categoría
     */
    async filterByCategory(categoria) {
        try {
            // Manejo especial para "Ver todos los productos"
            if (!categoria || categoria.trim() === '') {
                mostrarNotificacion('Mostrando todos los productos destacados', 'info');
            } else {
                mostrarNotificacion(`Filtrando por: ${categoria}`, 'info');
            }
            
            // Cerrar el menú lateral
            this.closeSideMenu();
            
            // Cargar productos filtrados por categoría
            if (window.carouselManager) {
                await window.carouselManager.loadProductsByCategory(categoria);
                
                if (!categoria || categoria.trim() === '') {
                    mostrarNotificacion('✅ Mostrando productos destacados', 'success');
                } else {
                    mostrarNotificacion(`✅ Mostrando productos de ${categoria}`, 'success');
                }
            } else {
                console.warn('CarouselManager no disponible');
                mostrarNotificacion('Error: Sistema de productos no disponible', 'error');
            }
            
        } catch (error) {
            console.error('Error al filtrar por categoría:', error);
            mostrarNotificacion('❌ Error al filtrar productos', 'error');
        }
    }

    /**
     * Configura la funcionalidad de búsqueda
     */
    setupSearch() {
        if (!this.searchInput) return;

        // Debounce para búsqueda en tiempo real
        const debouncedSearch = Utils.debounce((query) => {
            if (query.length >= 3) {
                this.searchProducts(query);
            }
        }, SUPERMERCADO_CONFIG.DEBOUNCE_TIME);

        this.searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            debouncedSearch(query);
        });
    }

    /**
     * Realiza la búsqueda
     */
    performSearch() {
        if (!this.searchInput) return;
        
        const query = this.searchInput.value.trim();
        if (query.length < 2) {
            mostrarNotificacion('Ingresa al menos 2 caracteres para buscar', 'warning');
            return;
        }

        this.searchProducts(query);
    }

    /**
     * Busca productos
     */
    async searchProducts(query) {
        try {
            console.log('🔍 Buscando:', query);
            mostrarNotificacion(`Buscando: ${query}`, 'info');
            
            // Aquí iría la implementación real de búsqueda
            // Por ahora solo simulamos
            setTimeout(() => {
                mostrarNotificacion(`Resultados encontrados para: ${query}`, 'success');
            }, 1000);
            
        } catch (error) {
            console.error('Error en búsqueda:', error);
            mostrarNotificacion('Error al buscar productos', 'error');
        }
    }

    /**
     * Vincula atajos de teclado
     */
    bindKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Escape para cerrar menús
            if (e.key === 'Escape') {
                if (this.isMenuOpen) {
                    this.closeSideMenu();
                }
            }

            // Ctrl/Cmd + K para enfocar búsqueda
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                if (this.searchInput) {
                    this.searchInput.focus();
                    this.searchInput.select();
                }
            }

            // Alt + M para abrir menú
            if (e.altKey && e.key === 'm') {
                e.preventDefault();
                if (this.isMenuOpen) {
                    this.closeSideMenu();
                } else {
                    this.openSideMenu();
                }
            }
        });
    }

    /**
     * Eventos responsivos
     */
    bindResponsiveEvents() {
        // Cerrar menú al cambiar tamaño de ventana
        window.addEventListener('resize', Utils.debounce(() => {
            if (window.innerWidth > 768 && this.isMenuOpen) {
                this.closeSideMenu();
            }
        }, 250));

        // Touch events para móviles
        this.bindTouchEvents();
    }

    /**
     * Eventos táctiles
     */
    bindTouchEvents() {
        if (!this.sideMenu) return;

        let startX = 0;
        let currentX = 0;
        let isDragging = false;

        // Touch start
        this.sideMenu.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            isDragging = true;
        }, { passive: true });

        // Touch move
        this.sideMenu.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            
            currentX = e.touches[0].clientX;
            const diffX = currentX - startX;
            
            // Swipe para cerrar (hacia la izquierda)
            if (diffX < -50) {
                this.closeSideMenu();
                isDragging = false;
            }
        }, { passive: true });

        // Touch end
        this.sideMenu.addEventListener('touchend', () => {
            isDragging = false;
        }, { passive: true });
    }

    /**
     * Muestra/oculta elementos de carga
     */
    toggleLoading(show = true) {
        const loadingElements = document.querySelectorAll('.loading');
        loadingElements.forEach(el => {
            el.style.display = show ? 'block' : 'none';
        });
    }

    /**
     * Actualiza el título de la página
     */
    updatePageTitle(title) {
        document.title = title ? `${title} - Supermercado Online` : 'Supermercado Online';
    }

    /**
     * Scroll suave a elemento
     */
    scrollToElement(selector, offset = 0) {
        const element = Utils.safeQuery(selector);
        if (element) {
            const top = element.offsetTop - offset;
            window.scrollTo({
                top: top,
                behavior: 'smooth'
            });
        }
    }

    /**
     * Manejo de estados de error
     */
    showError(message, container = 'main') {
        const containerElement = Utils.safeQuery(container);
        if (containerElement) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: #666;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #f44336; margin-bottom: 1rem;"></i>
                    <h3>Error</h3>
                    <p>${Utils.escapeHtml(message)}</p>
                    <button onclick="location.reload()" style="padding: 0.5rem 1rem; margin-top: 1rem;">
                        Recargar página
                    </button>
                </div>
            `;
            containerElement.innerHTML = '';
            containerElement.appendChild(errorDiv);
        }
    }

    /**
     * Manejo de estados vacíos
     */
    showEmpty(message, container = 'main') {
        const containerElement = Utils.safeQuery(container);
        if (containerElement) {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'empty-state';
            emptyDiv.innerHTML = `
                <div style="text-align: center; padding: 3rem; color: #666;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3>Sin resultados</h3>
                    <p>${Utils.escapeHtml(message)}</p>
                </div>
            `;
            containerElement.appendChild(emptyDiv);
        }
    }

    /**
     * Verifica si el menú está abierto
     */
    isMenuOpened() {
        return this.isMenuOpen;
    }

    /**
     * Toggle del menú
     */
    toggleSideMenu() {
        if (this.isMenuOpen) {
            this.closeSideMenu();
        } else {
            this.openSideMenu();
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Utils !== 'undefined' && typeof SUPERMERCADO_CONFIG !== 'undefined') {
        window.uiManager = new UIManager();
    } else {
        console.error('UIManager: Dependencias no encontradas (Utils, SUPERMERCADO_CONFIG)');
    }
});