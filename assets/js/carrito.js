document.addEventListener("DOMContentLoaded", () => {
    const body = document.body;
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
    body.appendChild(carritoSidebar);

    const botonAbrirCarrito = document.querySelector(".cart") || document.querySelector(".btn-carrito");
    const overlay = carritoSidebar.querySelector(".carrito-overlay");
    const cerrarBtn = carritoSidebar.querySelector(".carrito-cerrar");
    const listaCarrito = carritoSidebar.querySelector(".carrito-lista");
    const totalCarrito = document.getElementById("carrito-total-precio");
    const cartCount = document.getElementById("cart-count"); 
    const botonesSesion = document.getElementById("carrito-botones");
    const accionesCarrito = document.getElementById("carrito-acciones");
    const btnPagar = carritoSidebar.querySelector(".btn-pagar");

    let sesionActiva = false;
    let cargando = false;

    function escapeHtml(text) {
        if (text === null || text === undefined) return "";
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/"/g, "&quot;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
    }
    
    const formatter = new Intl.NumberFormat("es-AR", {
        style: "currency",
        currency: "ARS",
    });

    async function verificarSesion() {
        try {
            const resp = await fetch("login/check_session.php", {
                credentials: "include", 
            });
            const data = await resp.json();

            sesionActiva = !!(data.logged_in || data.user_id || data.id_usuario); 

            botonesSesion.style.display = sesionActiva ? "none" : "flex";
            accionesCarrito.style.display = sesionActiva ? "flex" : "none";

            return sesionActiva;
        } catch (err) {
            console.error("Error al verificar la sesión:", err);
            botonesSesion.style.display = "flex"; 
            accionesCarrito.style.display = "none";
            return false;
        }
    }

    async function cargarCarrito() {
        if (!sesionActiva) {
            listaCarrito.innerHTML = `<li class="vacio">Inicia sesión para ver tu carrito.</li>`;
            totalCarrito.textContent = formatter.format(0);
            if (cartCount) cartCount.textContent = "0";
            return;
        }

        cargando = true;
        listaCarrito.innerHTML = `<li class="cargando">Cargando carrito...</li>`;

        try {
            const resp = await fetch("carrito/obtener_carrito.php", {
                credentials: "include",
            });
            
            if (!resp.ok) {
                const errorData = await resp.json();
                throw new Error(errorData.message || `Error HTTP: ${resp.status}`);
            }

            const data = await resp.json();
            listaCarrito.innerHTML = "";
            let total = 0;

            if (!data.success || !data.carrito || data.carrito.length === 0) {
                listaCarrito.innerHTML = `<li class="vacio">Tu carrito está vacío</li>`;
                totalCarrito.textContent = formatter.format(0);
                if (cartCount) cartCount.textContent = "0";
                return;
            }
            
            total = data.subtotal_global || 0; 
            
            data.carrito.forEach((prod) => {
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
                    <div class="item-nombre">${escapeHtml(nombre)}</div>
                    <div class="item-info">
                        <span class="cantidad">${cantidad} x ${formatter.format(precio)}</span>
                        <span class="subtotal"><strong>${formatter.format(subtotal)}</strong></span>
                        <button class="eliminar" data-id="${idCarrito}" title="Eliminar producto">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                listaCarrito.appendChild(li);
            });

            totalCarrito.textContent = formatter.format(total);
            if (cartCount) cartCount.textContent = data.carrito.length.toString();

        } catch (err) {
            console.error("Error al cargar el carrito:", err);
            listaCarrito.innerHTML = `<li class="error">Error al cargar el carrito</li>`;
        } finally {
            cargando = false;
        }
    }

    if (botonAbrirCarrito) {
        botonAbrirCarrito.addEventListener("click", async () => {
            carritoSidebar.classList.remove("oculto");
            carritoSidebar.classList.add("activo");
            body.style.overflow = "hidden";
            await verificarSesion();
            await cargarCarrito();
        });
    }

    const cerrarCarrito = () => {
        carritoSidebar.classList.remove("activo");
        body.style.overflow = "auto";
    };

    overlay.addEventListener("click", cerrarCarrito);
    cerrarBtn.addEventListener("click", cerrarCarrito);

    document.addEventListener("click", async (e) => {
        const btn = e.target.closest(".agregar-carrito, .boton-agregar, #btn-agregar-carrito");
        if (!btn || cargando) return;

        const productoDiv = btn.closest(".producto");
        const idProducto = productoDiv?.dataset.id;
        if (!idProducto) return;

        const selectCantidad = productoDiv.querySelector("select[name=cantidad]"); 
        const cantidad = selectCantidad ? parseInt(selectCantidad.value) || 1 : 1;

        cargando = true;
        btn.disabled = true;

        try {
            const resp = await fetch("carrito/agregar_carrito.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                credentials: "include", 
                body: JSON.stringify({ id_producto: idProducto, cantidad }),
            });

            const data = await resp.json();

            if (resp.status === 401 || data.message?.includes("sesión")) {
                alert("Debes iniciar sesión para agregar productos.");
            } else if (data.success || data.exito) {
                btn.textContent = "¡Agregado!";
                setTimeout(() => btn.textContent = "Agregar al carrito", 1000); 
                await cargarCarrito(); 
            } else {
                alert(`Error: ${data.message || "No se pudo agregar el producto."}`);
            }
        } catch (err) {
            console.error("Error al agregar producto:", err);
            alert("Error de conexión al agregar producto.");
        } finally {
            cargando = false;
            btn.disabled = false;
        }
    });

    document.addEventListener("click", async (e) => {
        const btn = e.target.closest(".eliminar");
        if (!btn || cargando) return;

        e.preventDefault();

        const idCarrito = btn.dataset.id;
        const idProducto = btn.closest(".carrito-item")?.dataset.idProducto;
        
        if (!idCarrito && !idProducto) return; 

        cargando = true;
        btn.disabled = true;

        try {
            const bodyData = idCarrito ? { id_carrito: idCarrito } : { id_producto: idProducto };

            const resp = await fetch("carrito/eliminar_item.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                credentials: "include",
                body: JSON.stringify(bodyData),
            });

            const data = await resp.json();

            if (data.success || data.exito) {
                await cargarCarrito();
            } else {
                alert(`Error al eliminar: ${data.message || data.msg}`);
            }
        } catch (err) {
            console.error("Error al eliminar producto:", err);
            alert("Error de conexión al eliminar producto.");
        } finally {
            cargando = false;
            btn.disabled = false;
        }
    });

    carritoSidebar.querySelector(".btn-iniciar").addEventListener("click", () => {
        cerrarCarrito();
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
    });

    carritoSidebar.querySelector(".btn-registrarse").addEventListener("click", () => {
        cerrarCarrito();
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
    });

    btnPagar.addEventListener("click", () => {
        window.location.href = "direcciones/direcciones.php";
    });

    (async function inicializarCarrito() {
        await verificarSesion();
        if (sesionActiva) {
            await cargarCarrito();
        }
    })();
});
