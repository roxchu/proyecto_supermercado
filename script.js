// script.js

document.addEventListener('DOMContentLoaded', () => {
    
    // --- Referencias del DOM ---
    const modal = document.getElementById('loginModal');
    const loginLink = document.getElementById('login-link');
    const closeBtn = document.querySelector('.close-btn');
    const userInfoDiv = document.getElementById('user-info');
    const userGreeting = document.getElementById('user-greeting');
    const gestionLink = document.getElementById('link-gestion');
    const adminLink = document.getElementById('link-admin');
    const menuGestion = document.getElementById('menu-gestion');
    const menuAdmin = document.getElementById('menu-admin');


    // --- Lógica del Modal ---
    loginLink.onclick = function() { modal.style.display = "block"; }
    closeBtn.onclick = function() { modal.style.display = "none"; }
    window.onclick = function(event) {
        if (event.target == modal) { modal.style.display = "none"; }
    }
    
    /**
     * Función que actualiza la interfaz después de un login exitoso.
     * @param {string} rol - El rol del usuario ('client', 'employee', 'admin').
     * @param {string} nombre - El nombre del usuario.
     */
    function updateUI(rol, nombre) {
        // Ocultar Iniciar Sesión y mostrar el saludo
        loginLink.style.display = 'none';
        userInfoDiv.style.display = 'flex'; 
        userGreeting.innerHTML = `<i class="fas fa-user"></i> Hola, ${nombre}`;

        // Resetear la visibilidad de los enlaces de gestión
        gestionLink.style.display = 'none';
        adminLink.style.display = 'none';
        menuGestion.style.display = 'none';
        menuAdmin.style.display = 'none';
        
        // Mostrar elementos según el rol
        if (rol === 'admin') {
            gestionLink.style.display = 'inline-block';
            adminLink.style.display = 'inline-block';
            menuGestion.style.display = 'list-item';
            menuAdmin.style.display = 'list-item';
        } else if (rol === 'employee') {
            gestionLink.style.display = 'inline-block';
            menuGestion.style.display = 'list-item';
        }
    }
    
    // --- Lógica de Envío del Formulario ---
    document.getElementById('login-form-dni').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const dni = document.getElementById('dni').value.trim();
        const messageElement = document.getElementById('login-message');
        messageElement.textContent = 'Verificando...';
        
        fetch('login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', },
            body: `dni=${encodeURIComponent(dni)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageElement.textContent = `¡Bienvenido! Rol: ${data.rol}.`;
                
                // Actualizar UI con el rol y nombre devuelto por PHP
                updateUI(data.rol, data.nombre); 
                
                // Cierra el modal y redirige si es empleado/admin
                setTimeout(() => {
                    modal.style.display = "none";
                    if (data.rol === 'admin') {
                         window.location.href = 'dashboard_admin.php';
                    } else if (data.rol === 'employee') {
                         window.location.href = 'dashboard_empleado.php';
                    }
                    // Si es 'client', se queda en el index
                }, 1000); 

            } else {
                messageElement.textContent = data.message || 'Error desconocido al iniciar sesión.';
            }
        })
        .catch(error => {
            messageElement.textContent = 'Error de conexión con el servidor.';
            console.error('Error:', error);
        });
    });

    // NOTA: Aquí iría una función para chequear la sesión al cargar la página (ej: usando fetch a un script de chequeo de sesión).
});