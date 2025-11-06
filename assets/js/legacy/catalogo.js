document.addEventListener("DOMContentLoaded", () => {
    const carruselContainer = document.getElementById("carrusel-dinamico-container");
    const categoriaLinks = document.querySelectorAll(".side-link[data-categoria]");
    const BASE_URL = "/proyecto_supermercado/";

    // 🔹 Función para cargar productos (desde productos.php)
    async function cargarProductos(categoria = null) {
        const texto = categoria ? `Cargando ${categoria}...` : "Cargando productos destacados...";
        carruselContainer.innerHTML = `<p style="text-align:center;">${texto}</p>`;

        const url = categoria
            ? `${BASE_URL}productos.php?categoria=${encodeURIComponent(categoria)}`
            : `${BASE_URL}productos.php`;

        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error("Error al cargar los productos");
            const html = await response.text();
            carruselContainer.innerHTML = html;
        } catch (error) {
            console.error("Error:", error);
            carruselContainer.innerHTML = `<p style="color:red;text-align:center;">Error al cargar los productos.</p>`;
        }
    }

    // 🔹 Cargar productos destacados al inicio
    cargarProductos();

    // 🔹 Detectar clics en cada categoría
    categoriaLinks.forEach(link => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            const categoria = link.dataset.categoria;
            console.log("Categoría seleccionada:", categoria);
            cargarProductos(categoria);
        });
    });
});
