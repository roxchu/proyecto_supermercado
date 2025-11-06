/**
 * Módulo de Eventos del Carrito
 * Maneja todos los eventos y interacciones del carrito
 */

export class CarritoEvents {
    constructor(carritoUI, carritoAPI) {
        this.ui = carritoUI;
        this.api = carritoAPI;
        this.cargando = false;
        this.sesionActiva = false;
        
        this.init();
    }

    /**
     * Inicializa todos los event listeners
     */
    init() {
        this.configurarEventosCarrito();
        this.configurarEventosProductos();
        this.configurarEventosUI();
        this.configurarEventosSesion();
    }

    /**
     * Configura eventos principales del carrito
     */
    configurarEventosCarrito() {
        // Evento para abrir carrito
        const botonAbrirCarrito = document.querySelector(".cart") || document.querySelector(".btn-carrito");
        if (botonAbrirCarrito) {
            botonAbrirCarrito.addEventListener("click", async () => {
                this.ui.abrir();
                await this.verificarSesionYCargar();
            });
        }

        // Eventos para cerrar carrito
        const overlay = this.ui.getOverlay();
        const cerrarBtn = this.ui.getBotonCerrar();
        
        overlay.addEventListener("click", () => this.ui.cerrar());
        cerrarBtn.addEventListener("click", () => this.ui.cerrar());
    }

    /**
     * Configura eventos de productos (agregar y eliminar)
     */
    configurarEventosProductos() {
        // Agregar productos al carrito
        document.addEventListener("click", async (e) => {
            const btn = e.target.closest(".agregar-carrito, .boton-agregar, #btn-agregar-carrito");
            if (!btn || this.cargando) return;

            await this.manejarAgregarProducto(btn);
        });

        // Eliminar productos del carrito
        document.addEventListener("click", async (e) => {
            const btn = e.target.closest(".eliminar");
            if (!btn || this.cargando) return;

            e.preventDefault();
            await this.manejarEliminarProducto(btn);
        });
    }

    /**
     * Configura eventos de la interfaz
     */
    configurarEventosUI() {
        const botones = this.ui.getBotonesSesion();

        // Botón iniciar sesión
        botones.iniciar.addEventListener("click", () => {
            this.ui.cerrar();
            this.abrirModalLogin();
        });

        // Botón registrarse
        botones.registrar.addEventListener("click", () => {
            this.ui.cerrar();
            this.abrirModalRegistro();
        });

        // Botón pagar
        botones.pagar.addEventListener("click", () => {
            window.location.href = "direcciones/direcciones.php";
        });
    }

    /**
     * Configura eventos relacionados con la sesión
     */
    configurarEventosSesion() {
        // Inicialización automática
        this.inicializarCarrito();
    }

    /**
     * Maneja la adición de productos al carrito
     */
    async manejarAgregarProducto(btn) {
        const productoDiv = btn.closest(".producto");
        const idProducto = productoDiv?.dataset.id;
        if (!idProducto) return;

        const selectCantidad = productoDiv.querySelector("select[name=cantidad]");
        const cantidad = selectCantidad ? parseInt(selectCantidad.value) || 1 : 1;

        this.cargando = true;
        btn.disabled = true;

        try {
            const data = await this.api.agregarProducto(idProducto, cantidad);

            if (data.success || data.exito) {
                btn.textContent = "¡Agregado!";
                setTimeout(() => btn.textContent = "Agregar al carrito", 1000);
                await this.cargarCarrito();
            } else if (data.message?.includes("sesión")) {
                alert("Debes iniciar sesión para agregar productos.");
            } else {
                alert(`Error: ${data.message || "No se pudo agregar el producto."}`);
            }
        } catch (err) {
            if (err.message?.includes("401")) {
                alert("Debes iniciar sesión para agregar productos.");
            } else {
                alert("Error de conexión al agregar producto.");
            }
        } finally {
            this.cargando = false;
            btn.disabled = false;
        }
    }

    /**
     * Maneja la eliminación de productos del carrito
     */
    async manejarEliminarProducto(btn) {
        const idCarrito = btn.dataset.id;
        const idProducto = btn.closest(".carrito-item")?.dataset.idProducto;

        if (!idCarrito && !idProducto) return;

        this.cargando = true;
        btn.disabled = true;

        try {
            const data = await this.api.eliminarProducto(idCarrito, idProducto);

            if (data.success || data.exito) {
                await this.cargarCarrito();
            } else {
                alert(`Error al eliminar: ${data.message || data.msg}`);
            }
        } catch (err) {
            alert("Error de conexión al eliminar producto.");
        } finally {
            this.cargando = false;
            btn.disabled = false;
        }
    }

    /**
     * Verifica sesión y carga carrito
     */
    async verificarSesionYCargar() {
        await this.verificarSesion();
        await this.cargarCarrito();
    }

    /**
     * Verifica el estado de la sesión
     */
    async verificarSesion() {
        try {
            this.sesionActiva = await this.api.verificarSesion();
            this.ui.actualizarSesion(this.sesionActiva);
            return this.sesionActiva;
        } catch (err) {
            this.sesionActiva = false;
            this.ui.actualizarSesion(false);
            return false;
        }
    }

    /**
     * Carga el contenido del carrito
     */
    async cargarCarrito() {
        if (!this.sesionActiva) {
            this.ui.mostrarSinSesion();
            return;
        }

        this.cargando = true;
        this.ui.mostrarCargando();

        try {
            const data = await this.api.obtenerCarrito();

            if (!data.success || !data.carrito || data.carrito.length === 0) {
                this.ui.mostrarVacio();
                return;
            }

            const total = data.subtotal_global || 0;
            this.ui.renderizarProductos(data.carrito, total);

        } catch (err) {
            this.ui.mostrarError();
        } finally {
            this.cargando = false;
        }
    }

    /**
     * Abre el modal de login
     */
    abrirModalLogin() {
        const loginModal = document.getElementById("loginModal");
        const loginForm = document.getElementById("login-form-dni");
        const registerForm = document.getElementById("register-form");

        if (loginModal) {
            loginModal.style.display = "block";
            if (loginForm) loginForm.style.display = "block";
            if (registerForm) registerForm.style.display = "none";
            const modalTitle = document.getElementById("modal-title");
            if (modalTitle) modalTitle.textContent = "Iniciar Sesión";
        }
    }

    /**
     * Abre el modal de registro
     */
    abrirModalRegistro() {
        const loginModal = document.getElementById("loginModal");
        const loginForm = document.getElementById("login-form-dni");
        const registerForm = document.getElementById("register-form");

        if (loginModal) {
            loginModal.style.display = "block";
            if (registerForm) registerForm.style.display = "block";
            if (loginForm) loginForm.style.display = "none";
            const modalTitle = document.getElementById("modal-title");
            if (modalTitle) modalTitle.textContent = "Crear Cuenta";
        }
    }

    /**
     * Inicializa el carrito automáticamente
     */
    async inicializarCarrito() {
        await this.verificarSesion();
        if (this.sesionActiva) {
            await this.cargarCarrito();
        }
    }
}