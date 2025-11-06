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
            console.log('🔐 Verificando sesión...');
            const resp = await fetch("/proyecto_supermercado/login/check_session.php", {
                credentials: "include", 
            });
            const data = await resp.json();
            console.log('🔐 Datos de sesión:', data);

            sesionActiva = !!(data.logged_in || data.user_id || data.id_usuario); 
            console.log('🔐 Sesión activa:', sesionActiva);

            botonesSesion.style.display = sesionActiva ? "none" : "flex";
            accionesCarrito.style.display = sesionActiva ? "flex" : "none";

            return sesionActiva;
        } catch (err) {
            console.error("❌ Error al verificar la sesión:", err);
            botonesSesion.style.display = "flex"; 
            accionesCarrito.style.display = "none";
            return false;
        }
    }

    async function cargarCarrito() {
        console.log('🛒 Cargando carrito, sesión activa:', sesionActiva);
        
        if (!sesionActiva) {
            listaCarrito.innerHTML = `<li class="vacio">Inicia sesión para ver tu carrito.</li>`;
            totalCarrito.textContent = formatter.format(0);
            if (cartCount) cartCount.textContent = "0";
            return;
        }

        cargando = true;
        listaCarrito.innerHTML = `<li class="cargando">Cargando carrito...</li>`;

        try {
            console.log('📡 Solicitando carrito desde obtener_carrito.php');
            
            // Usar ruta absoluta desde la raíz del proyecto
            const obtenerCarritoUrl = "/proyecto_supermercado/carrito/obtener_carrito.php";
            
            console.log('🔗 URL para obtener carrito:', obtenerCarritoUrl);
            
            const resp = await fetch(obtenerCarritoUrl, {
                credentials: "include",
            });
            
            if (!resp.ok) {
                throw new Error(`Error HTTP: ${resp.status}`);
            }

            const data = await resp.json();
            console.log('📦 Respuesta del carrito:', data);
            
            listaCarrito.innerHTML = "";
            let total = 0;

            // Validar la respuesta y extraer productos de manera segura
            if (!data || !data.success) {
                console.log('❌ Error en la respuesta o carrito no exitoso');
                listaCarrito.innerHTML = `<li class="vacio">Error al cargar el carrito</li>`;
                totalCarrito.textContent = formatter.format(0);
                if (cartCount) cartCount.textContent = "0";
                return;
            }

            const productos = data.productos || data.items || [];
            
            if (!Array.isArray(productos) || productos.length === 0) {
                console.log('🛒 Carrito vacío o sin productos válidos');
                listaCarrito.innerHTML = `<li class="vacio">Tu carrito está vacío</li>`;
                totalCarrito.textContent = formatter.format(0);
                if (cartCount) cartCount.textContent = "0";
                return;
            }
            
            console.log(`🛍️ Mostrando ${productos.length} productos en el carrito`);
            
            // Usar la nueva estructura de datos
            productos.forEach((prod) => {
                console.log('📦 Procesando producto:', prod);
                const idCarrito = prod.Id_Carrito;
                const nombre = prod.nombre || prod.nombre_producto || `Producto #${prod.Id_Producto}`;
                const precio = parseFloat(prod.Precio_Unitario_Momento) || parseFloat(prod.precio) || 0;
                const cantidad = parseInt(prod.Cantidad) || parseInt(prod.cantidad) || 0;
                const subtotal = parseFloat(prod.Total) || (precio * cantidad) || 0;

                const li = document.createElement("li");
                li.classList.add("carrito-item");
                li.dataset.idCarrito = idCarrito;
                li.dataset.idProducto = prod.Id_Producto;
                
                li.innerHTML = `
                    <div class="item-contenido">
                        <div class="item-detalles">
                            <div class="item-nombre">${escapeHtml(nombre)}</div>
                            <div class="item-info">
                                <span class="cantidad">${cantidad} x ${formatter.format(precio)}</span>
                                <span class="subtotal"><strong>${formatter.format(subtotal)}</strong></span>
                                <button class="eliminar" data-id="${idCarrito}" title="Eliminar producto">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                listaCarrito.appendChild(li);
            });

            // Usar el total calculado del servidor o calcular localmente
            const totalFinal = data.total || total;
            totalCarrito.textContent = formatter.format(totalFinal);
            if (cartCount) cartCount.textContent = productos.length.toString();

        } catch (err) {
            console.error("Error al cargar el carrito:", err);
            console.error("Stack trace:", err.stack);
            listaCarrito.innerHTML = `<li class="error">Error al cargar el carrito: ${err.message}</li>`;
            totalCarrito.textContent = formatter.format(0);
            if (cartCount) cartCount.textContent = "0";
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

        console.log('🛒 Botón de agregar al carrito clickeado');

        const productoDiv = btn.closest(".producto");
        const idProducto = productoDiv?.dataset.id;
        
        console.log('🆔 ID del producto:', idProducto);
        console.log('📦 Div del producto:', productoDiv);
        
        if (!idProducto) {
            console.error('❌ No se encontró ID del producto');
            return;
        }

        const selectCantidad = productoDiv.querySelector("select[name=cantidad]"); 
        const cantidad = selectCantidad ? parseInt(selectCantidad.value) || 1 : 1;
        
        console.log('📊 Cantidad a agregar:', cantidad);

        cargando = true;
        btn.disabled = true;

        try {
            console.log('📡 Enviando producto al carrito...');
            
            // Usar ruta absoluta desde la raíz del proyecto
            const agregarCarritoUrl = "/proyecto_supermercado/carrito/agregar_carrito.php";
            
            console.log('🔗 URL para agregar:', agregarCarritoUrl);
            
            const resp = await fetch(agregarCarritoUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                credentials: "include", 
                body: JSON.stringify({ id_producto: idProducto, cantidad }),
            });

            const data = await resp.json();
            console.log('✅ Respuesta del servidor:', data);

            if (resp.status === 401 || data.message?.includes("sesión")) {
                console.warn('⚠️ Usuario no autenticado');
                alert("Debes iniciar sesión para agregar productos.");
            } else if (data.success || data.exito) {
                console.log('✅ Producto agregado exitosamente');
                btn.textContent = "¡Agregado!";
                setTimeout(() => btn.textContent = "Agregar al carrito", 1000); 
                await cargarCarrito(); 
            } else {
                console.error('❌ Error al agregar:', data);
                alert(`Error: ${data.message || "No se pudo agregar el producto."}`);
            }
        } catch (err) {
            console.error("❌ Error al agregar producto:", err);
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
        window.location.href = "/proyecto_supermercado/direcciones/direcciones.php";
    });

    // Event listener para cambios de sesión
    document.addEventListener('sessionChanged', async function(event) {
        console.log('🔄 Sesión cambió, actualizando carrito...', event.detail);
        await verificarSesion();
        await cargarCarrito();
    });

    // Verificar sesión periódicamente
    setInterval(async () => {
        const sesionAnterior = sesionActiva;
        await verificarSesion();
        if (sesionAnterior !== sesionActiva) {
            console.log('🔄 Cambio de sesión detectado, recargando carrito');
            await cargarCarrito();
        }
    }, 5000); // Cada 5 segundos

    (async function inicializarCarrito() {
        console.log('🚀 Inicializando carrito...');
        await verificarSesion();
        if (sesionActiva) {
            await cargarCarrito();
        }
    })();
});


