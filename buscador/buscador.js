document.addEventListener('DOMContentLoaded', () => {
    const inputBuscar = document.getElementById('buscarProducto');
    const btnBuscar = document.getElementById('btnBuscar');

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
            <p style="color:${color}; font-weight:600; margin-top:10px; font-size: 16px;">${mensaje}</p>
            <button style="
                margin-top:15px;
                padding:8px 15px;
                background-color:#007bff;
                border:none;
                color:white;
                border-radius:6px;
                cursor:pointer;
                font-weight: 500;
            ">Cerrar</button>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        overlay.querySelector('button').addEventListener('click', () => overlay.remove());
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.remove();
            }
        });
    }

    // Buscar producto en la BD
    function buscarProducto() {
        const termino = inputBuscar.value.trim();

        if (termino === '') {
            mostrarMensaje('⚠️ Escribí algo para buscar.');
            return;
        }

        console.log('🔍 Buscando:', termino);

        fetch('/proyecto_supermercado/buscador/buscar_producto.php?termino=' + encodeURIComponent(termino))
            .then(res => res.json())
            .then(data => {
                console.log('📦 Resultados:', data);

                if (data.error) {
                    // Mostrar mensaje si no hay productos
                    mostrarMensaje(data.error);
                } else if (Array.isArray(data) && data.length > 0) {
                    // Mostrar resultados como tarjetas en overlay
                    mostrarResultados(data, termino);
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

    // Mostrar resultados en una grilla
    function mostrarResultados(productos, termino) {
        const overlay = crearOverlay();
        const modal = document.createElement('div');
        modal.style.background = '#fff';
        modal.style.borderRadius = '12px';
        modal.style.padding = '20px';
        modal.style.maxWidth = '90vw';
        modal.style.maxHeight = '80vh';
        modal.style.overflowY = 'auto';
        modal.style.boxShadow = '0 4px 15px rgba(0,0,0,0.3)';

        let html = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; position: sticky; top: 0; background: #fff; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                <h2 style="margin: 0; color: #333;">Resultados de búsqueda: "${termino}" (${productos.length})</h2>
                <button id="cerrar-busqueda" style="
                    background: none;
                    border: none;
                    font-size: 28px;
                    cursor: pointer;
                    color: #999;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">&times;</button>
            </div>
            <div style="
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 20px;
            ">
        `;

        productos.forEach(prod => {
            const descuento = prod.precio_anterior ? Math.round(((prod.precio_anterior - prod.precio) / prod.precio_anterior) * 100) : 0;
            html += `
                <div class="tarjeta-producto" style="
                    border: 1px solid #ddd;
                    border-radius: 12px;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                    text-align: center;
                    padding: 20px;
                    transition: transform 0.2s, box-shadow 0.2s;
                    background: #fff;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    position: relative;
                " onmouseover="this.style.transform='scale(1.03)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.15)'"
                  onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.1)'"&>
                    ${descuento > 0 ? `<div style="position: absolute; top: 10px; right: 10px; background: #e74c3c; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">-${descuento}%</div>` : ''}
                    
                    <div style="
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 180px;
                        width: 100%;
                        margin-bottom: 15px;
                        border-radius: 8px;
                        overflow: hidden;
                        background: #f8f9fa;
                    ">
                        <img src="${prod.imagen}" alt="${prod.nombre}" 
                             style="
                                max-width: 100%;
                                max-height: 100%;
                                object-fit: contain;
                                border-radius: 8px;
                                display: block;
                             "
                             onerror="this.src='https://via.placeholder.com/200x150?text=Sin+Imagen'">
                    </div>
                    
                    <h3 style="margin: 15px 0 10px 0; color: #333; font-size: 16px; font-weight: 600;">${prod.nombre}</h3>
                    
                    <p style="color: #666; font-size: 12px; margin: 5px 0 10px 0;">${prod.categoria}</p>
                    
                    <div style="display: flex; justify-content: center; gap: 10px; align-items: center; margin: 10px 0;">
                        <p style="color: #007bff; font-weight: bold; font-size: 20px; margin: 0;">$${parseFloat(prod.precio).toLocaleString('es-AR', {minimumFractionDigits: 2})}</p>
                        ${prod.precio_anterior ? `<p style="color: #999; font-size: 14px; margin: 0; text-decoration: line-through;">$${parseFloat(prod.precio_anterior).toLocaleString('es-AR', {minimumFractionDigits: 2})}</p>` : ''}
                    </div>
                    
                    <p style="color: #666; font-size: 12px; margin: 10px 0 15px 0;">Stock: <strong>${prod.stock > 0 ? prod.stock + ' disponibles' : 'Sin stock'}</strong></p>
                    
                    <div class="producto" data-id="${prod.id}" data-nombre="${prod.nombre}" style="margin-bottom: 10px; width: 100%;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 10px;">
                            <label style="font-size: 14px; color: #333; font-weight: 500;">Cantidad:</label>
                            <input type="number" 
                                   min="1" 
                                   max="${prod.stock}" 
                                   value="1" 
                                   class="cantidad-input"
                                   data-product-id="${prod.id}"
                                   style="
                                       width: 60px;
                                       padding: 5px;
                                       border: 1px solid #ddd;
                                       border-radius: 4px;
                                       text-align: center;
                                       font-size: 14px;
                                   "
                                   oninput="this.value = Math.max(1, Math.min(${prod.stock}, parseInt(this.value) || 1))"
                                   ${prod.stock <= 0 ? 'disabled' : ''}>
                        </div>
                        <button class="boton-agregar" data-product-id="${prod.id}" style="
                            background-color: ${prod.stock > 0 ? '#28a745' : '#6c757d'};
                            color: white;
                            border: none;
                            border-radius: 6px;
                            padding: 12px 20px;
                            cursor: ${prod.stock > 0 ? 'pointer' : 'not-allowed'};
                            font-weight: bold;
                            transition: background-color 0.2s;
                            width: 100%;
                            max-width: 200px;
                            margin-bottom: 10px;
                        " ${prod.stock <= 0 ? 'disabled' : ''}>
                            ${prod.stock > 0 ? 'Agregar al carrito' : 'Sin stock'}
                        </button>
                    </div>
                    
                    <a href="/proyecto_supermercado/mostrar.php?id=${prod.id}" style="
                        display: inline-block;
                        color: #007bff;
                        text-decoration: none;
                        font-size: 14px;
                        margin-top: 5px;
                        font-weight: 500;
                        transition: color 0.2s;
                    " onmouseover="this.style.color='#0056b3'" onmouseout="this.style.color='#007bff'">
                        Ver detalles →
                    </a>
                </div>
            `;
        });

        html += `</div>`;
        modal.innerHTML = html;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        // Event listener para cerrar
        modal.querySelector('#cerrar-busqueda').addEventListener('click', () => {
            overlay.remove();
        });

        // Event listeners para botones de agregar al carrito
        modal.querySelectorAll('.boton-agregar').forEach(boton => {
            boton.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const cantidadInput = modal.querySelector(`.cantidad-input[data-product-id="${productId}"]`);
                const cantidad = parseInt(cantidadInput.value) || 1;
                
                if (cantidad <= 0) {
                    mostrarMensaje('⚠️ La cantidad debe ser mayor a 0');
                    return;
                }
                
                console.log(`🛒 Agregando ${cantidad} unidad(es) del producto ID: ${productId}`);
                agregarAlCarrito(productId, cantidad, overlay);
            });
        });

        // Event listener para el overlay
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.remove();
            }
        });
    }

    // Función para agregar productos al carrito con cantidad específica
    async function agregarAlCarrito(productId, cantidad, overlay) {
        try {
            console.log(`🛒 Enviando datos: productId=${productId}, cantidad=${cantidad}`);
            
            const requestData = {
                id_producto: parseInt(productId),
                cantidad: parseInt(cantidad)
            };
            
            console.log('📦 Datos a enviar:', requestData);

            const response = await fetch('/proyecto_supermercado/carrito/agregar_carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });

            const data = await response.json();
            console.log('📨 Respuesta del servidor:', data);

            if (data.success) {
                mostrarMensaje(`✅ ${cantidad} producto(s) agregado(s) al carrito`, 'success');
                
                // Actualizar contador del carrito si existe
                if (typeof actualizarContadorCarrito === 'function') {
                    actualizarContadorCarrito();
                }
                
                // Cerrar el modal de búsqueda
                overlay.remove();
            } else {
                mostrarMensaje(`❌ Error: ${data.message || 'No se pudo agregar al carrito'}`);
            }
        } catch (error) {
            console.error('❌ Error al agregar al carrito:', error);
            mostrarMensaje('❌ Error de conexión al agregar al carrito');
        }
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
