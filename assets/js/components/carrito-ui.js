/**
 * Módulo UI del Carrito
 * Maneja toda la interfaz visual y animaciones del carrito
 */

export class CarritoUI {
    constructor() {
        this.sidebar = null;
        this.lista = null;
        this.total = null;
        this.cartCount = null;
        this.botonesSesion = null;
        this.accionesCarrito = null;
        this.formatter = new Intl.NumberFormat("es-AR", {
            style: "currency",
            currency: "ARS",
        });
        
        this.init();
    }

    /**
     * Inicializa la interfaz del carrito
     */
    init() {
        this.crearSidebar();
        this.obtenerElementos();
    }

    /**
     * Crea la estructura HTML del sidebar del carrito
     */
    crearSidebar() {
        const carritoSidebar = document.createElement("div");
        carritoSidebar.classList.add("carrito-sidebar", "oculto");
        carritoSidebar.innerHTML = `
            <div class="carrito-overlay"></div>
            <div class="carrito-panel">
                <button class="carrito-cerrar" aria-label="Cerrar carrito"><i class="fas fa-times"></i></button>
                <div class="carrito-contenido">
                    <h2 class="carrito-titulo"><i class="fas fa-shopping-basket"></i> Mi Carrito</h2>
                    <ul class="carrito-lista"></ul>
                    <div class="carrito-total">
                        <strong>Total:</strong> <span id="carrito-total-precio">$0.00</span>
                    </div>
                    <div class="carrito-botones" id="carrito-botones">
                        <button class="btn-iniciar">Iniciar sesión</button>
                        <button class="btn-registrarse">Registrarse</button>
                    </div>
                    <div class="carrito-acciones" id="carrito-acciones" style="display:none;">
                        <button class="btn-pagar">Ir a pagar</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(carritoSidebar);
        this.sidebar = carritoSidebar;
    }

    /**
     * Obtiene referencias a los elementos del DOM
     */
    obtenerElementos() {
        this.lista = this.sidebar.querySelector(".carrito-lista");
        this.total = document.getElementById("carrito-total-precio");
        this.cartCount = document.getElementById("cart-count");
        this.botonesSesion = document.getElementById("carrito-botones");
        this.accionesCarrito = document.getElementById("carrito-acciones");
    }

    /**
     * Abre el carrito con animación
     */
    abrir() {
        this.sidebar.classList.remove("oculto");
        this.sidebar.classList.add("activo");
        document.body.style.overflow = "hidden";
    }

    /**
     * Cierra el carrito con animación
     */
    cerrar() {
        this.sidebar.classList.remove("activo");
        document.body.style.overflow = "auto";
    }

    /**
     * Actualiza la interfaz según el estado de la sesión
     */
    actualizarSesion(sesionActiva) {
        this.botonesSesion.style.display = sesionActiva ? "none" : "flex";
        this.accionesCarrito.style.display = sesionActiva ? "flex" : "none";
    }

    /**
     * Muestra estado de carga
     */
    mostrarCargando() {
        this.lista.innerHTML = `<li class="cargando">Cargando carrito...</li>`;
    }

    /**
     * Muestra mensaje cuando no hay sesión
     */
    mostrarSinSesion() {
        this.lista.innerHTML = `<li class="vacio">Inicia sesión para ver tu carrito.</li>`;
        this.total.textContent = this.formatter.format(0);
        if (this.cartCount) this.cartCount.textContent = "0";
    }

    /**
     * Muestra carrito vacío
     */
    mostrarVacio() {
        this.lista.innerHTML = `<li class="vacio">Tu carrito está vacío</li>`;
        this.total.textContent = this.formatter.format(0);
        if (this.cartCount) this.cartCount.textContent = "0";
    }

    /**
     * Muestra error
     */
    mostrarError(mensaje = "Error al cargar el carrito") {
        this.lista.innerHTML = `<li class="error">${mensaje}</li>`;
    }

    /**
     * Renderiza los productos en el carrito
     */
    renderizarProductos(productos, totalGlobal) {
        this.lista.innerHTML = "";

        productos.forEach((prod) => {
            const idCarrito = prod.Id_Carrito;
            const nombre = prod.nombre || `Producto #${prod.Id_Producto}`;
            const precio = parseFloat(prod.Precio_Unitario_Momento) || 0;
            const cantidad = parseInt(prod.Cantidad) || 0;
            const subtotal = parseFloat(prod.Total) || 0;

            const li = document.createElement("li");
            li.classList.add("carrito-item");
            li.dataset.idCarrito = idCarrito;
            li.dataset.idProducto = prod.Id_Producto;

            li.innerHTML = `
                <div class="item-nombre">${this.escapeHtml(nombre)}</div>
                <div class="item-info">
                    <span class="cantidad">${cantidad} x ${this.formatter.format(precio)}</span>
                    <span class="subtotal"><strong>${this.formatter.format(subtotal)}</strong></span>
                    <button class="eliminar" data-id="${idCarrito}" title="Eliminar producto">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            this.lista.appendChild(li);
        });

        this.total.textContent = this.formatter.format(totalGlobal);
        if (this.cartCount) this.cartCount.textContent = productos.length.toString();
    }

    /**
     * Escapa HTML para prevenir XSS
     */
    escapeHtml(text) {
        if (text === null || text === undefined) return "";
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/"/g, "&quot;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
    }

    /**
     * Obtiene el overlay para eventos
     */
    getOverlay() {
        return this.sidebar.querySelector(".carrito-overlay");
    }

    /**
     * Obtiene el botón de cerrar para eventos
     */
    getBotonCerrar() {
        return this.sidebar.querySelector(".carrito-cerrar");
    }

    /**
     * Obtiene los botones de sesión para eventos
     */
    getBotonesSesion() {
        return {
            iniciar: this.sidebar.querySelector(".btn-iniciar"),
            registrar: this.sidebar.querySelector(".btn-registrarse"),
            pagar: this.sidebar.querySelector(".btn-pagar")
        };
    }
}