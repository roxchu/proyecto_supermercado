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

    // Cerrar resultados
    function cerrarResultados() {
        contenedorResultados.innerHTML = '';
        contenedorResultados.style.display = 'none';
    }

    // Buscar producto en la BD
    function buscarProducto() {
        const termino = inputBuscar.value.trim();

        if (termino === '') {
            mostrarMensaje('⚠️ Escribí algo para buscar.');
            return;
        }

        console.log('🔍 Buscando:', termino);

        fetch('buscador/buscar_producto.php?termino=' + encodeURIComponent(termino))
            .then(res => res.json())
            .then(data => {
                console.log('📦 Resultados:', data);
                contenedorResultados.innerHTML = ''; // Limpia antes de mostrar

                if (data.error) {
                    mostrarMensaje(data.error);
                } else if (Array.isArray(data) && data.length > 0) {
                    // Mostrar resultados como tarjetas en overlay
                    const overlay = crearOverlay();
                    const modal = document.createElement('div');
                    modal.style.background = '#fff';
                    modal.style.borderRadius = '12px';
                    modal.style.padding = '20px';
                    modal.style.maxWidth = '90vw';
                    modal.style.maxHeight = '80vh';
                    modal.style.overflowY = 'auto';
                    modal.style.boxShadow = '0 4px 15px rgba(0,0,0,0.3)';

                    modal.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2 style="margin: 0; color: #333;">Resultados de búsqueda: "${termino}"</h2>
                            <button id="cerrar-busqueda" style="
                                background: none;
                                border: none;
                                font-size: 24px;
                                cursor: pointer;
                                color: #666;
                            ">&times;</button>
                        </div>
                        <div style="
                            display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                            gap: 20px;
                        ">
                            ${data.map(prod => `
                                <div class="tarjeta-producto" style="
                                    border: 1px solid #ddd;
                                    border-radius: 12px;
                                    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                                    text-align: center;
                                    padding: 15px;
                                    transition: transform 0.2s;
                                    background: #fff;
                                " onmouseover="this.style.transform='scale(1.03)'"
                                  onmouseout="this.style.transform='scale(1)'">
                                    <img src="${prod.imagen}" alt="${prod.nombre}" width="200" height="150" 
                                         style="object-fit: cover; border-radius: 8px; margin-bottom: 10px;"
                                         onerror="this.src='https://via.placeholder.com/200x150?text=Sin+Imagen'">
                                    <h3 style="margin: 10px 0; color: #333; font-size: 16px;">${prod.nombre}</h3>
                                    <p style="color: #007bff; font-weight: bold; font-size: 18px; margin: 10px 0;">$${parseFloat(prod.precio).toLocaleString('es-AR', {minimumFractionDigits: 2})}</p>
                                    <p style="color: #666; font-size: 12px; margin: 5px 0;">Stock: ${prod.stock > 0 ? prod.stock + ' disponibles' : 'Sin stock'}</p>
                                    <div class="producto" data-id="${prod.id}" data-nombre="${prod.nombre}">
                                        <button class="boton-agregar" style="
                                            background-color: ${prod.stock > 0 ? '#28a745' : '#6c757d'};
                                            color: white;
                                            border: none;
                                            border-radius: 6px;
                                            padding: 10px 15px;
                                            cursor: ${prod.stock > 0 ? 'pointer' : 'not-allowed'};
                                            font-weight: bold;
                                            transition: background-color 0.2s;
                                        " ${prod.stock <= 0 ? 'disabled' : ''}>
                                            ${prod.stock > 0 ? 'Agregar al carrito' : 'Sin stock'}
                                        </button>
                                    </div>
                                    <a href="mostrar.php?id=${prod.id}" style="
                                        display: inline-block;
                                        margin-top: 10px;
                                        color: #007bff;
                                        text-decoration: none;
                                        font-size: 14px;
                                    ">Ver detalles</a>
                                </div>
                            `).join('')}
                        </div>
                    `;

                    overlay.appendChild(modal);
                    document.body.appendChild(overlay);

                    // Event listener para cerrar
                    modal.querySelector('#cerrar-busqueda').addEventListener('click', () => {
                        overlay.remove();
                    });

                    // Event listener para el overlay
                    overlay.addEventListener('click', (e) => {
                        if (e.target === overlay) {
                            overlay.remove();
                        }
                    });

                    console.log(`✅ Mostrados ${data.length} productos encontrados`);
                } else {
                    mostrarMensaje('❌ No se encontró ningún producto con ese nombre.');
                }
            })
            .catch(err => {
                console.error('❌ Error en búsqueda:', err);
                mostrarMensaje('❌ Error al realizar la búsqueda.');
            });
    }

    // Event listeners
    if (btnBuscar) {
        btnBuscar.addEventListener('click', buscarProducto);
    }
    
    if (inputBuscar) {
        inputBuscar.addEventListener('keypress', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarProducto();
            }
        });
    }

    console.log('🔍 Buscador inicializado correctamente');
});
