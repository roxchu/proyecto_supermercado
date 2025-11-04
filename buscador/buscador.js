document.addEventListener('DOMContentLoaded', () => {
    const inputBuscar = document.getElementById('buscarProducto');
    const btnBuscar = document.getElementById('btnBuscar');
    const contenedorResultados = document.getElementById('resultadoBusqueda');

    // Crear fondo difuminado (para el modal)
    function crearOverlay() {
        const overlay = document.createElement('div');
        overlay.id = 'overlay';
        overlay.style.position = 'fixed';
        overlay.style.top = 0;
        overlay.style.left = 0;
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        overlay.style.zIndex = '999';
        overlay.style.display = 'flex';
        overlay.style.justifyContent = 'center';
        overlay.style.alignItems = 'center';
        return overlay;
    }

    // Mostrar mensaje elegante
    function mostrarMensaje(mensaje, tipo = 'error') {
        const overlay = crearOverlay();
        const modal = document.createElement('div');
        modal.style.background = '#fff';
        modal.style.borderRadius = '12px';
        modal.style.padding = '25px 35px';
        modal.style.textAlign = 'center';
        modal.style.boxShadow = '0 4px 15px rgba(0,0,0,0.3)';
        modal.style.maxWidth = '350px';
        modal.style.animation = 'aparecer 0.3s ease';

        const icon = tipo === 'error' ? '❌' : '✅';
        const color = tipo === 'error' ? '#c0392b' : '#2ecc71';

        modal.innerHTML = `
            <div style="font-size:40px; color:${color};">${icon}</div>
            <p style="color:${color}; font-weight:600; margin-top:10px;">${mensaje}</p>
            <button style="
                margin-top:15px;
                padding:8px 15px;
                background-color:#007bff;
                border:none;
                color:white;
                border-radius:6px;
                cursor:pointer;
            ">Cerrar</button>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        overlay.querySelector('button').addEventListener('click', () => overlay.remove());
    }

    // Buscar producto en la BD
    function buscarProducto() {
        const termino = inputBuscar.value.trim();

        if (termino === '') {
            mostrarMensaje('⚠️ Escribí algo para buscar.');
            return;
        }

        fetch('buscar_producto.php?termino=' + encodeURIComponent(termino))
            .then(res => res.json())
            .then(data => {
                contenedorResultados.innerHTML = ''; // Limpia antes de mostrar

                if (data.error) {
                    mostrarMensaje(data.error);
                } else if (Array.isArray(data) && data.length > 0) {
                    // Mostrar resultados como tarjetas en el body
                    contenedorResultados.style.display = 'grid';
                    contenedorResultados.style.gridTemplateColumns = 'repeat(auto-fit, minmax(220px, 1fr))';
                    contenedorResultados.style.gap = '20px';
                    contenedorResultados.style.padding = '40px';
                    contenedorResultados.style.width = '90%';
                    contenedorResultados.style.margin = '0 auto';

                    contenedorResultados.innerHTML = data.map(prod => `
                        <div class="tarjeta-producto" style="
                            border:1px solid #ddd;
                            border-radius:12px;
                            box-shadow:0 2px 6px rgba(0,0,0,0.1);
                            text-align:center;
                            padding:15px;
                            transition:transform 0.2s;
                            background:#fff;
                        " onmouseover="this.style.transform='scale(1.03)'"
                          onmouseout="this.style.transform='scale(1)'">
                            <img src="${prod.imagen}" alt="${prod.nombre}" width="160" height="140" style="object-fit:cover; border-radius:8px;">
                            <h3 style="margin-top:10px;">${prod.nombre}</h3>
                            <p style="color:#007bff; font-weight:bold;">$${prod.precio}</p>
                            <button style="
                                background-color:#28a745;
                                color:white;
                                border:none;
                                border-radius:6px;
                                padding:8px 12px;
                                cursor:pointer;
                            ">Agregar al carrito</button>
                        </div>
                    `).join('');
                } else {
                    mostrarMensaje('❌ No se encontró ningún producto con ese nombre.');
                }
            })
            .catch(err => {
                console.error(err);
                mostrarMensaje('❌ Error al realizar la búsqueda.');
            });
    }

    btnBuscar.addEventListener('click', buscarProducto);
    inputBuscar.addEventListener('keypress', e => {
        if (e.key === 'Enter') buscarProducto();
    });
});
