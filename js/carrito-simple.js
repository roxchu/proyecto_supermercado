// carrito-simple.js - Carrito de compras funcional y limpio

// Variables globales
let carritoSimple = null;

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    console.log('🛒 Inicializando carrito...');
    
    // Función para verificar dependencias y crear carrito
    const iniciarCarrito = () => {
        if (typeof Utils === 'undefined' || typeof SUPERMERCADO_CONFIG === 'undefined' || typeof mostrarNotificacion === 'undefined') {
            console.log('⏳ Esperando dependencias del carrito...');
            setTimeout(iniciarCarrito, 100);
            return;
        }
        
        try {
            carritoSimple = new CarritoSimple();
            console.log('✅ Carrito inicializado correctamente');
        } catch (error) {
            console.error('❌ Error al inicializar carrito:', error);
        }
    };
    
    iniciarCarrito();
});

/**
 * Clase principal del carrito de compras
 */
class CarritoSimple {
    constructor() {
        this.items = [];
        this.total = 0;
        this.isOpen = false;
        this.crearInterfaz();
        this.configurarEventos();
        this.verificarEstadoInicial();
    }
    
    /**
     * Crea la interfaz del carrito
     */
    crearInterfaz() {
        // Crear overlay y panel del carrito
        const carritoHTML = `
            <div id="carrito-overlay" class="carrito-overlay-hidden">
            </div>
            <div id="carrito-panel" class="carrito-panel-hidden">
                <div class="carrito-header">
                    <h2>
                        <i class="fas fa-shopping-cart"></i> Mi Carrito
                        <button id="cerrar-carrito" class="carrito-cerrar" title="Cerrar carrito">
                            <i class="fas fa-times"></i>
                        </button>
                    </h2>
                </div>
                <div id="carrito-contenido" class="carrito-contenido">
                    <div id="carrito-items" class="carrito-items">
                        <div class="carrito-vacio">
                            <i class="fas fa-shopping-cart"></i>
                            <p>Tu carrito está vacío</p>
                        </div>
                    </div>
                </div>
                <div class="carrito-footer">
                    <div class="carrito-total">
                        <strong>Total: $<span id="carrito-total">0.00</span></strong>
                    </div>
                    <div class="carrito-acciones">
                        <button id="vaciar-carrito" class="btn-vaciar" title="Vaciar carrito">
                            <i class="fas fa-trash"></i> Vaciar
                        </button>
                        <button id="finalizar-compra" class="btn-finalizar" title="Finalizar compra">
                            <i class="fas fa-credit-card"></i> Finalizar
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Agregar al DOM
        const carritoContainer = document.createElement('div');
        carritoContainer.innerHTML = carritoHTML;
        document.body.appendChild(carritoContainer);
        
        // Agregar estilos CSS
        this.agregarEstilos();
        
        // Obtener referencias a elementos
        this.overlay = document.getElementById('carrito-overlay');
        this.panel = document.getElementById('carrito-panel');
        this.itemsContainer = document.getElementById('carrito-items');
        this.totalElement = document.getElementById('carrito-total');
    }
    
    /**
     * Agrega los estilos CSS del carrito
     */
    agregarEstilos() {
        if (document.getElementById('carrito-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'carrito-styles';
        style.textContent = `
            .carrito-overlay-hidden {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9998;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }
            
            .carrito-overlay-visible {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9998;
                opacity: 1;
                visibility: visible;
                transition: all 0.3s ease;
            }
            
            .carrito-panel-hidden {
                position: fixed;
                top: 0;
                right: -400px;
                width: 400px;
                max-width: 90vw;
                height: 100%;
                background: white;
                z-index: 9999;
                transition: right 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
                display: flex;
                flex-direction: column;
            }
            
            .carrito-panel-visible {
                position: fixed;
                top: 0;
                right: 0;
                width: 400px;
                max-width: 90vw;
                height: 100%;
                background: white;
                z-index: 9999;
                transition: right 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
                display: flex;
                flex-direction: column;
            }
            
            .carrito-header {
                padding: 1rem;
                border-bottom: 2px solid #f0f0f0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            
            .carrito-header h2 {
                margin: 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 1.2rem;
            }
            
            .carrito-cerrar {
                background: none;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                padding: 0.25rem;
                border-radius: 50%;
                transition: background 0.2s;
            }
            
            .carrito-cerrar:hover {
                background: rgba(255, 255, 255, 0.2);
            }
            
            .carrito-contenido {
                flex: 1;
                overflow-y: auto;
                padding: 1rem;
            }
            
            .carrito-items {
                min-height: 200px;
            }
            
            .carrito-vacio {
                text-align: center;
                color: #666;
                padding: 3rem 1rem;
            }
            
            .carrito-vacio i {
                font-size: 3rem;
                margin-bottom: 1rem;
                opacity: 0.5;
            }
            
            .carrito-item {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 1rem 0;
                border-bottom: 1px solid #eee;
                transition: background 0.2s;
            }
            
            .carrito-item:hover {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 1rem 0.5rem;
            }
            
            .item-info {
                flex: 1;
                margin-right: 1rem;
            }
            
            .item-info h4 {
                margin: 0 0 0.5rem 0;
                font-size: 0.95rem;
                color: #333;
                line-height: 1.3;
            }
            
            .item-info p {
                margin: 0;
                font-size: 0.85rem;
                color: #666;
            }
            
            .item-acciones {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 0.5rem;
            }
            
            .item-precio {
                font-weight: bold;
                color: #2c3e50;
                font-size: 0.95rem;
            }
            
            .btn-eliminar-item {
                background: #e74c3c;
                color: white;
                border: none;
                padding: 0.25rem 0.5rem;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.8rem;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                gap: 0.25rem;
            }
            
            .btn-eliminar-item:hover {
                background: #c0392b;
                transform: scale(1.05);
            }
            
            .carrito-footer {
                border-top: 2px solid #f0f0f0;
                padding: 1rem;
                background: #f8f9fa;
            }
            
            .carrito-total {
                text-align: center;
                font-size: 1.2rem;
                margin-bottom: 1rem;
                color: #2c3e50;
            }
            
            .carrito-acciones {
                display: flex;
                gap: 0.5rem;
            }
            
            .btn-vaciar, .btn-finalizar {
                flex: 1;
                padding: 0.75rem;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 500;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                font-size: 0.9rem;
            }
            
            .btn-vaciar {
                background: #95a5a6;
                color: white;
            }
            
            .btn-vaciar:hover {
                background: #7f8c8d;
                transform: translateY(-1px);
            }
            
            .btn-finalizar {
                background: #27ae60;
                color: white;
            }
            
            .btn-finalizar:hover {
                background: #229954;
                transform: translateY(-1px);
            }
            
            @media (max-width: 768px) {
                .carrito-panel-hidden,
                .carrito-panel-visible {
                    width: 100vw;
                    max-width: 100vw;
                }
                
                .carrito-panel-hidden {
                    right: -100vw;
                }
            }
        `;
        
        document.head.appendChild(style);
    }
    
    /**
     * Configura todos los eventos del carrito
     */
    configurarEventos() {
        // Abrir carrito al hacer clic en el icono
        const cartIcon = document.querySelector('.cart');
        if (cartIcon) {
            cartIcon.addEventListener('click', (e) => {
                e.preventDefault();
                this.abrir();
            });
        }
        
        // Cerrar carrito
        const cerrarBtn = document.getElementById('cerrar-carrito');
        if (cerrarBtn) {
            cerrarBtn.addEventListener('click', () => this.cerrar());
        }
        
        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.cerrar());
        }
        
        // Agregar productos al carrito
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-agregar-carrito')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-agregar-carrito');
                const id = btn.dataset.producto;
                if (id) {
                    this.agregarProducto(id);
                }
            }
        });
        
        // Finalizar compra
        const finalizarBtn = document.getElementById('finalizar-compra');
        if (finalizarBtn) {
            finalizarBtn.addEventListener('click', () => this.finalizarCompra());
        }
        
        // Vaciar carrito
        const vaciarBtn = document.getElementById('vaciar-carrito');
        if (vaciarBtn) {
            vaciarBtn.addEventListener('click', () => this.vaciarCarrito());
        }
        
        // Tecla Escape para cerrar
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.cerrar();
            }
        });
    }
    
    /**
     * Verifica el estado inicial del carrito
     */
    verificarEstadoInicial() {
        this.cargarCarrito();
    }
    
    /**
     * Abre el carrito
     */
    abrir() {
        if (this.overlay && this.panel) {
            this.overlay.className = 'carrito-overlay-visible';
            this.panel.className = 'carrito-panel-visible';
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
            this.cargarCarrito();
        }
    }
    
    /**
     * Cierra el carrito
     */
    cerrar() {
        if (this.overlay && this.panel) {
            this.overlay.className = 'carrito-overlay-hidden';
            this.panel.className = 'carrito-panel-hidden';
            this.isOpen = false;
            document.body.style.overflow = '';
        }
    }
    
    /**
     * Agrega un producto al carrito
     */
    async agregarProducto(idProducto, cantidad = 1) {
        try {
            mostrarNotificacion('Agregando producto...', 'info');
            
            const response = await fetch(SUPERMERCADO_CONFIG.BASE_URL + 'carrito/agregar_carrito.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    id_producto: parseInt(idProducto), 
                    cantidad: parseInt(cantidad) 
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                mostrarNotificacion('✅ Producto agregado al carrito', 'success');
                this.actualizarContador(data.items_en_carrito);
                if (this.isOpen) {
                    this.cargarCarrito();
                }
            } else {
                mostrarNotificacion('❌ ' + (data.message || 'Error al agregar producto'), 'error');
            }
        } catch (error) {
            console.error('Error agregando producto:', error);
            mostrarNotificacion('❌ Error de conexión', 'error');
        }
    }
    
    /**
     * Carga el contenido del carrito desde el servidor
     */
    async cargarCarrito() {
        try {
            const response = await fetch(SUPERMERCADO_CONFIG.BASE_URL + 'carrito/obtener_carrito.php');
            const data = await response.json();
            
            if (data.success) {
                this.items = data.carrito || [];
                this.total = data.subtotal_global || 0;
                this.actualizarInterfaz();
                this.actualizarContador(data.total_items);
            } else {
                console.warn('Error al cargar carrito:', data.message);
            }
        } catch (error) {
            console.error('Error cargando carrito:', error);
        }
    }
    
    /**
     * Actualiza la interfaz del carrito
     */
    actualizarInterfaz() {
        if (!this.itemsContainer || !this.totalElement) return;
        
        // Si no hay items, mostrar mensaje vacío
        if (this.items.length === 0) {
            this.itemsContainer.innerHTML = `
                <div class="carrito-vacio">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Tu carrito está vacío</p>
                </div>
            `;
            this.totalElement.textContent = '0.00';
            return;
        }
        
        // Mostrar items del carrito
        this.itemsContainer.innerHTML = this.items.map(item => `
            <div class="carrito-item">
                <div class="item-info">
                    <h4>${Utils.escapeHtml(item.nombre)}</h4>
                    <p>Cantidad: ${item.Cantidad}</p>
                    <p>Precio unitario: $${parseFloat(item.Precio_Unitario_Momento).toFixed(2)}</p>
                </div>
                <div class="item-acciones">
                    <div class="item-precio">$${parseFloat(item.Total).toFixed(2)}</div>
                    <button class="btn-eliminar-item" onclick="carritoSimple.eliminarItem(${item.Id_Carrito})">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        `).join('');
        
        // Actualizar total
        this.totalElement.textContent = this.total.toFixed(2);
    }
    
    /**
     * Elimina un item específico del carrito
     */
    async eliminarItem(idItem) {
        try {
            const response = await fetch(SUPERMERCADO_CONFIG.BASE_URL + 'carrito/eliminar_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_detalle: parseInt(idItem) })
            });
            
            const data = await response.json();
            
            if (data.success) {
                mostrarNotificacion('✅ Producto eliminado', 'success');
                this.cargarCarrito();
            } else {
                mostrarNotificacion('❌ ' + (data.message || 'Error al eliminar'), 'error');
            }
        } catch (error) {
            console.error('Error eliminando item:', error);
            mostrarNotificacion('❌ Error de conexión', 'error');
        }
    }
    
    /**
     * Vacía completamente el carrito
     */
    async vaciarCarrito() {
        if (this.items.length === 0) {
            mostrarNotificacion('El carrito ya está vacío', 'info');
            return;
        }
        
        if (!confirm('¿Estás seguro de que quieres vaciar el carrito?')) {
            return;
        }
        
        try {
            // Eliminar todos los items uno por uno
            for (const item of this.items) {
                await this.eliminarItem(item.Id_Carrito);
            }
            mostrarNotificacion('✅ Carrito vaciado', 'success');
        } catch (error) {
            console.error('Error vaciando carrito:', error);
            mostrarNotificacion('❌ Error al vaciar carrito', 'error');
        }
    }
    
    /**
     * Finaliza la compra
     */
    finalizarCompra() {
        if (this.items.length === 0) {
            mostrarNotificacion('El carrito está vacío', 'warning');
            return;
        }
        
        // TODO: Implementar lógica de finalización de compra
        mostrarNotificacion('🚧 Función de pago en desarrollo', 'info');
        console.log('Finalizando compra con items:', this.items);
    }
    
    /**
     * Actualiza el contador del carrito en el header
     */
    actualizarContador(count) {
        const contador = document.getElementById('cart-count');
        if (contador) {
            contador.textContent = count || this.items.length || 0;
        }
    }
    
    /**
     * Obtiene el estado actual del carrito
     */
    getEstado() {
        return {
            items: this.items,
            total: this.total,
            cantidad: this.items.length,
            isOpen: this.isOpen
        };
    }
}

// Inicializar carrito cuando se carga la página
document.addEventListener('DOMContentLoaded', () => {
    window.carritoSimple = new CarritoSimple();
    console.log('✅ Carrito inicializado correctamente');
});