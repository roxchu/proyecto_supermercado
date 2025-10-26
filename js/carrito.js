document.addEventListener("DOMContentLoaded", () => {
  const body = document.body;

  // --- Crear carrito deslizable ---
  const carritoSidebar = document.createElement("div");
  carritoSidebar.classList.add("carrito-sidebar");
  carritoSidebar.innerHTML = `
    <div class="carrito-overlay"></div>
    <div class="carrito-panel">
      <button class="carrito-cerrar" aria-label="Cerrar carrito">&times;</button>
      <div class="carrito-contenido">
        <h2 class="carrito-titulo"><i class="fas fa-shopping-basket"></i> Mi Carrito</h2>
        <ul class="carrito-lista"></ul>
        <div class="carrito-total">
          <strong>Total:</strong> <span id="carrito-total-precio">$0.00</span>
        </div>
        <div class="carrito-botones">
          <button class="btn-iniciar">Iniciar sesión</button>
          <button class="btn-registrarse">Registrarse</button>
        </div>
      </div>
    </div>
  `;
  body.appendChild(carritoSidebar);

  // --- Referencias ---
  const botonAbrirCarrito =
    document.querySelector(".cart") || document.querySelector(".btn-carrito");
  const overlay = carritoSidebar.querySelector(".carrito-overlay");
  const cerrarBtn = carritoSidebar.querySelector(".carrito-cerrar");
  const listaCarrito = carritoSidebar.querySelector(".carrito-lista");
  const totalCarrito = document.getElementById("carrito-total-precio");
  const cartCount = document.getElementById("cart-count");

  // --- Abrir/Cerrar carrito ---
  if (botonAbrirCarrito) {
    botonAbrirCarrito.addEventListener("click", () => {
      carritoSidebar.classList.add("activo");
      body.style.overflow = "hidden";
      cargarCarrito();
    });
  }

  const cerrarCarrito = () => {
    carritoSidebar.classList.remove("activo");
    body.style.overflow = "auto";
  };

  overlay.addEventListener("click", cerrarCarrito);
  cerrarBtn.addEventListener("click", cerrarCarrito);

  // --- Cargar productos del carrito ---
  async function cargarCarrito() {
    try {
      const resp = await fetch("carrito/obtener_carrito.php", { credentials: "same-origin" });
      const data = await resp.json();

      listaCarrito.innerHTML = "";
      let total = 0;

      if (!data.carrito || data.carrito.length === 0) {
        listaCarrito.innerHTML = `<li class="vacio">🛒 Tu carrito está vacío</li>`;
        totalCarrito.textContent = "$0.00";
        if (cartCount) cartCount.textContent = "0";
        return;
      }

      data.carrito.forEach((prod) => {
        const id = prod.Id_Producto;
        const nombre = prod.nombre || "Producto";
        const precio = parseFloat(prod.Precio_Unitario_Momento);
        const cantidad = parseInt(prod.Cantidad);
        const subtotal = precio * cantidad;
        total += subtotal;

        const li = document.createElement("li");
        li.classList.add("carrito-item");
        li.dataset.id = id;
        li.innerHTML = `
          <div class="item-nombre">${escapeHtml(nombre)}</div>
          <div class="item-controles">
            <div class="cantidad-controles">
              <button class="btn-restar" data-id="${id}">−</button>
              <span class="cantidad">${cantidad}</span>
              <button class="btn-sumar" data-id="${id}">+</button>
            </div>
            <div class="item-precio">
              <span class="subtotal">$${subtotal.toFixed(2)}</span>
              <button class="eliminar" data-id="${id}" title="Eliminar producto">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        `;
        listaCarrito.appendChild(li);
      });

      totalCarrito.textContent = `$${total.toFixed(2)}`;
      if (cartCount) cartCount.textContent = data.carrito.length.toString();
    } catch (err) {
      console.error("Error al cargar el carrito:", err);
      listaCarrito.innerHTML = "<li class='error'>⚠️ Error al cargar el carrito</li>";
    }
  }

  function escapeHtml(text) {
    if (!text && text !== 0) return "";
    return String(text)
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  // --- Agregar producto ---
  document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".agregar-carrito, .boton-agregar, #btn-agregar-carrito");
    if (!btn) return;

    const productoDiv = btn.closest(".producto");
    const idProducto = productoDiv?.dataset.id;
    let cantidad = 1;

    const cantidadInput = productoDiv?.querySelector(".cantidad");
    if (cantidadInput && !isNaN(parseInt(cantidadInput.value))) {
      cantidad = parseInt(cantidadInput.value);
    }

    try {
      const resp = await fetch("carrito/agregar_carrito.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({ id: idProducto, cantidad }),
      });
      const data = await resp.json();
      if (data.success) {
        cargarCarrito();
      } else {
        alert(`❌ ${data.msg || "No se pudo agregar el producto"}`);
      }
    } catch (err) {
      console.error("Error al agregar producto:", err);
      alert("Error al agregar producto");
    }
  });

  // --- Modificar cantidad / Eliminar ---
  document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".btn-sumar, .btn-restar, .eliminar");
    if (!btn) return;

    e.preventDefault();
    const id = btn.dataset.id;
    if (!id) return;

    if (btn.classList.contains("btn-sumar")) {
      await modificarCantidad(id, "sumar");
    } else if (btn.classList.contains("btn-restar")) {
      await modificarCantidad(id, "restar");
    } else if (btn.classList.contains("eliminar")) {
      await eliminarProducto(id);
    }
  });

  async function modificarCantidad(id, accion) {
    try {
      const resp = await fetch("carrito/modificar_cantidad.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({ id, accion }),
      });
      const data = await resp.json();
      if (data.success || data.ok) {
        await cargarCarrito();
      } else {
        console.error("❌ Error al modificar cantidad:", data.msg || data);
      }
    } catch (err) {
      console.error("Error al modificar cantidad:", err);
    }
  }

  async function eliminarProducto(id) {
    try {
      const resp = await fetch("carrito/eliminar_carrito.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({ id_producto: id }),
      });
      const data = await resp.json();
      if (data.success) {
        await cargarCarrito();
      } else {
        console.error("❌ Error al eliminar producto:", data.msg || data);
      }
    } catch (err) {
      console.error("Error al eliminar producto:", err);
    }
  }

  // --- Sesión ---
  fetch("login/check_session.php")
    .then((r) => r.json())
    .then((data) => {
      const botones = carritoSidebar.querySelector(".carrito-botones");
      if (data.logged_in) {
        botones.innerHTML = `
          <p>👋 Hola, <strong>${escapeHtml(data.nombre)}</strong></p>
          <button class="btn-cerrar-sesion">Cerrar sesión</button>
        `;
        botones.querySelector(".btn-cerrar-sesion").addEventListener("click", () => {
          fetch("login/logout.php").then(() => location.reload());
        });
      }
    })
    .catch(() => {});
});
