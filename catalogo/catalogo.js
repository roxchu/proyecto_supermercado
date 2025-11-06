document.addEventListener("DOMContentLoaded", () => {
    console.log("🚀 Cargando catalogo.js...");
    
    const carruselContainer = document.getElementById("carrusel-dinamico-container");
    const categoriaLinks = document.querySelectorAll(".side-link[data-categoria]");
    const BASE_URL = "/proyecto_supermercado/";

    if (!carruselContainer) {
        console.error("❌ No se encontró el contenedor del carrusel");
        return;
    }

    console.log("✅ Contenedor del carrusel encontrado:", carruselContainer);

    // 🔹 Función para cargar productos (desde productos.php)
    async function cargarProductos(categoria = null) {
        const texto = categoria ? `Cargando ${categoria}...` : "Cargando productos destacados...";
        carruselContainer.innerHTML = `<p style="text-align:center;">${texto}</p>`;

        const url = categoria
            ? `${BASE_URL}productos.php?categoria=${encodeURIComponent(categoria)}`
            : `${BASE_URL}productos.php`;

        console.log("📡 Cargando desde:", url);

        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            const html = await response.text();
            
            console.log("✅ Respuesta recibida, longitud:", html.length);
            
            carruselContainer.innerHTML = html;
            
            // Reinicializar botones del carrusel después de cargar productos
            setTimeout(() => {
                inicializarBotonesCarrusel();
                actualizarVisibilidadBotones();
            }, 200);
        } catch (error) {
            console.error("❌ Error al cargar productos:", error);
            carruselContainer.innerHTML = `<p style="color:red;text-align:center;">Error al cargar los productos: ${error.message}</p>`;
        }
    }

    // 🔹 Función para manejar los botones del carrusel
    function inicializarBotonesCarrusel() {
        console.log('🔧 Inicializando botones del carrusel...');
        
        const prevBtn = document.querySelector(".carrusel-btn.prev");
        const nextBtn = document.querySelector(".carrusel-btn.next");
        
        console.log("Botones encontrados:", { prevBtn, nextBtn });
        console.log("Container info:", {
            element: carruselContainer,
            scrollWidth: carruselContainer?.scrollWidth,
            clientWidth: carruselContainer?.clientWidth,
            overflowX: getComputedStyle(carruselContainer)?.overflowX
        });
        
        if (prevBtn) {
            // Limpiar event listeners anteriores
            prevBtn.onclick = null;
            prevBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('⬅️ Prev button clicked!');
                console.log('Before scroll - scrollLeft:', carruselContainer.scrollLeft);
                
                if (carruselContainer) {
                    carruselContainer.scrollLeft -= 300;
                    console.log('After scroll - scrollLeft:', carruselContainer.scrollLeft);
                    
                    // También probar con scrollBy
                    setTimeout(() => {
                        carruselContainer.scrollBy({
                            left: -300,
                            behavior: 'smooth'
                        });
                    }, 100);
                } else {
                    console.error('❌ Container no disponible');
                }
            });
            console.log('✅ Prev button configurado');
        }
        
        if (nextBtn) {
            // Limpiar event listeners anteriores
            nextBtn.onclick = null;
            nextBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('➡️ Next button clicked!');
                console.log('Before scroll - scrollLeft:', carruselContainer.scrollLeft);
                
                if (carruselContainer) {
                    carruselContainer.scrollLeft += 300;
                    console.log('After scroll - scrollLeft:', carruselContainer.scrollLeft);
                    
                    // También probar con scrollBy
                    setTimeout(() => {
                        carruselContainer.scrollBy({
                            left: 300,
                            behavior: 'smooth'
                        });
                    }, 100);
                } else {
                    console.error('❌ Container no disponible');
                }
            });
            console.log('✅ Next button configurado');
        }
        
        // Event delegation como backup
        document.removeEventListener('click', carruselClickHandler);
        document.addEventListener('click', carruselClickHandler);
    }

    function carruselClickHandler(e) {
        if (e.target.classList.contains('carrusel-btn')) {
            console.log('🎯 Clic detectado en botón carrusel via delegation');
            if (e.target.classList.contains('prev')) {
                carruselContainer.scrollBy({ left: -300, behavior: 'smooth' });
            } else if (e.target.classList.contains('next')) {
                carruselContainer.scrollBy({ left: 300, behavior: 'smooth' });
            }
        }
    }

    // 🔹 Función para mostrar/ocultar botones según el scroll
    function actualizarVisibilidadBotones() {
        if (!carruselContainer) return;

        const prevButton = document.querySelector(".carrusel-btn.prev");
        const nextButton = document.querySelector(".carrusel-btn.next");
        const { scrollLeft, scrollWidth, clientWidth } = carruselContainer;
        
        if (prevButton) {
            prevButton.style.opacity = scrollLeft > 0 ? '1' : '0.5';
            prevButton.style.pointerEvents = scrollLeft > 0 ? 'auto' : 'auto';
        }
        
        if (nextButton) {
            const canScrollRight = scrollLeft < (scrollWidth - clientWidth - 10);
            nextButton.style.opacity = canScrollRight ? '1' : '0.5';
            nextButton.style.pointerEvents = canScrollRight ? 'auto' : 'auto';
        }
    }

    // 🔹 Cargar productos destacados al inicio
    console.log('🚀 Inicializando carrusel...');
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

    // 🔹 Inicializar botones del carrusel inmediatamente
    setTimeout(() => {
        inicializarBotonesCarrusel();
        console.log('✅ Botones del carrusel inicializados');
    }, 500);

    // 🔹 Navegación con teclado
    document.addEventListener('keydown', (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            if (carruselContainer) {
                carruselContainer.scrollBy({
                    left: -300,
                    behavior: 'smooth'
                });
            }
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            if (carruselContainer) {
                carruselContainer.scrollBy({
                    left: 300,
                    behavior: 'smooth'
                });
            }
        }
    });

    // 🔹 Función para debugging
    function debugCarousel() {
        console.log('🔍 Debug Carousel Info:');
        console.log('Container:', carruselContainer);
        console.log('Prev Button:', document.querySelector(".carrusel-btn.prev"));
        console.log('Next Button:', document.querySelector(".carrusel-btn.next"));
        
        if (carruselContainer) {
            console.log('Container scroll width:', carruselContainer.scrollWidth);
            console.log('Container client width:', carruselContainer.clientWidth);
            console.log('Container scroll left:', carruselContainer.scrollLeft);
            console.log('Container overflow-x:', getComputedStyle(carruselContainer).overflowX);
            console.log('Container children count:', carruselContainer.children.length);
            console.log('Can scroll?', carruselContainer.scrollWidth > carruselContainer.clientWidth);
            
            // Mostrar info de cada producto
            Array.from(carruselContainer.children).forEach((child, index) => {
                console.log(`Child ${index}:`, {
                    width: child.offsetWidth,
                    className: child.className
                });
            });
        }
    }

    // Exponer función de debug globalmente
    window.debugCarousel = debugCarousel;
    window.testCarruselScroll = function(direction = 'right') {
        console.log('🧪 Test manual del carrusel hacia:', direction);
        if (carruselContainer) {
            const scrollAmount = direction === 'right' ? 300 : -300;
            carruselContainer.scrollLeft += scrollAmount;
            console.log('New scroll position:', carruselContainer.scrollLeft);
        }
    };
    window.forceScroll = function() {
        console.log('🚀 Forcing scroll with scrollTo');
        if (carruselContainer) {
            carruselContainer.scrollTo({
                left: carruselContainer.scrollLeft + 300,
                behavior: 'smooth'
            });
        }
    };
});
