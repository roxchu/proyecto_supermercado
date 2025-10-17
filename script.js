// --- Lógica de Conexión AJAX para Cargar el Carrusel ---

// 4. Inicia la carga de datos al cargar el documento (Punto de entrada)
document.addEventListener('DOMContentLoaded', cargarCarruselDinamico);

function cargarCarruselDinamico() {
    const contenedor = document.getElementById('carrusel-dinamico-container');
    
    // 1. Solicitamos el contenido de productos.php
    fetch('productos.php')
        .then(response => {
            if (!response.ok) {
                // Si el servidor responde con error (ej. 404), mostramos mensaje
                throw new Error('Error de servidor al cargar productos: ' + response.statusText);
            }
            return response.text(); // Leemos el HTML generado
        })
        .then(html => {
            // 2. Inyectamos el HTML recibido
            contenedor.innerHTML = html;

            // 3. Inicializamos la lógica del carrusel solo si se inyectó correctamente
            const carruselInyectado = document.getElementById('carruselProductos');
            if (carruselInyectado) {
                initializeCarouselProductos();
            } else {
                 // Si el PHP devolvió un error de BD, el HTML será el mensaje de error
                 console.error("No se pudo inicializar el carrusel. Verifique productos.php");
            }
        })
        .catch(error => {
            console.error('Error AJAX:', error);
            contenedor.innerHTML = '<p style="color:red; text-align:center; padding: 50px;">Falló la conexión con el servidor: ' + error.message + '</p>';
        });
}


// --- LÓGICA DEL CARRUSEL (Manipulación del Deslizamiento) ---

let slideIndexProductos = 0;
let slidesProductos;
let trackProductos;
// Número aproximado de tarjetas visibles (ajustar en CSS o media queries si es necesario)
const CARDS_VISIBLE_THRESHOLD = 4; 

function initializeCarouselProductos() {
    // Buscamos los elementos inyectados dinámicamente
    slidesProductos = document.querySelectorAll('#carruselProductos .producto-card');
    trackProductos = document.querySelector('#carruselProductos .carousel-track');
    let prevBtn = document.querySelector('.carrusel-btn.prev'); 
    let nextBtn = document.querySelector('.carrusel-btn.next'); 

    if (slidesProductos.length > 0 && trackProductos) {
        showSlideProductos(slideIndexProductos);

        if (prevBtn) prevBtn.addEventListener('click', () => cambiarSlideProductos(-1));
        if (nextBtn) nextBtn.addEventListener('click', () => cambiarSlideProductos(1));
        
        checkCarouselVisibility();
        window.addEventListener('resize', checkCarouselVisibility); // Revisa en cambio de tamaño
    }
}

function checkCarouselVisibility() {
    let prevBtn = document.querySelector('.carrusel-btn.prev'); 
    let nextBtn = document.querySelector('.carrusel-btn.next');
    
    // Ocultar/Mostrar botones basado en el umbral visible
    if (slidesProductos.length <= CARDS_VISIBLE_THRESHOLD) {
        if (prevBtn) prevBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'none';
    } else {
        if (prevBtn) prevBtn.style.display = 'block';
        if (nextBtn) nextBtn.style.display = 'block';
        showSlideProductos(slideIndexProductos); 
    }
}

function showSlideProductos(n) {
    const totalSlides = slidesProductos.length;
    
    // Cálculo del ancho dinámico para mover el track (más robusto)
    const cardWidth = slidesProductos[0].offsetWidth;
    // El 'gap' del CSS se extrae y se suma al ancho
    const trackGap = parseFloat(getComputedStyle(trackProductos).gap) || 0; 
    const CARD_WIDTH_WITH_GAP = cardWidth + trackGap;

    const maxIndex = totalSlides - CARDS_VISIBLE_THRESHOLD; 

    // Manejo de límites del índice
    if (n > maxIndex) { slideIndexProductos = maxIndex; } 
    if (n < 0) { slideIndexProductos = 0; } 
    
    // Aplicar la traslación (desplazamiento) horizontal
    let offset = slideIndexProductos * CARD_WIDTH_WITH_GAP;
    trackProductos.style.transform = `translateX(-${offset}px)`;

    // Deshabilitar flechas en los extremos
    document.querySelector('.carrusel-btn.prev').disabled = (slideIndexProductos === 0);
    document.querySelector('.carrusel-btn.next').disabled = (slideIndexProductos >= maxIndex);
}

function cambiarSlideProductos(n) {
    slideIndexProductos += n;
    showSlideProductos(slideIndexProductos);
}

// --- MENU LATERAL DE CATEGORÍAS (Off-canvas) ---
document.addEventListener('DOMContentLoaded', () => {
    const btnCategorias = document.getElementById('btn-categorias');
    const sideMenu = document.getElementById('side-menu');
    const btnClose = document.getElementById('btn-close-menu');
    const overlay = document.getElementById('menu-overlay');
    const sideLinks = document.querySelectorAll('.side-link');

    function openMenu() {
        if (!sideMenu) return;
        sideMenu.classList.add('open');
        overlay.classList.add('active');
        btnCategorias.setAttribute('aria-expanded', 'true');
        sideMenu.setAttribute('aria-hidden', 'false');
        // evitar scroll del body si quieres
        document.body.style.overflow = 'hidden';
    }

    function closeMenu() {
        if (!sideMenu) return;
        sideMenu.classList.remove('open');
        overlay.classList.remove('active');
        btnCategorias.setAttribute('aria-expanded', 'false');
        sideMenu.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        btnCategorias.focus();
    }

    if (btnCategorias) btnCategorias.addEventListener('click', openMenu);
    if (btnClose) btnClose.addEventListener('click', closeMenu);
    if (overlay) overlay.addEventListener('click', closeMenu);

    // Cerrar al pulsar Esc
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeMenu();
    });

    // Cuando se cliquea una categoría, cerramos el menú para navegación
    sideLinks.forEach(link => {
        link.addEventListener('click', () => {
            closeMenu();
        });
    });
});