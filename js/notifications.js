// notifications.js - Sistema de notificaciones

/**
 * Manejador de notificaciones del sistema
 */
class NotificationManager {
    constructor() {
        this.container = this.crearContainer();
        this.notifications = [];
    }

    /**
     * Crea el contenedor principal de notificaciones
     */
    crearContainer() {
        let container = document.getElementById('notifications-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notifications-container';
            container.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                z-index: 10000;
                pointer-events: none;
            `;
            document.body.appendChild(container);
        }
        return container;
    }

    /**
     * Muestra una notificación
     * @param {string} mensaje - Mensaje a mostrar
     * @param {string} tipo - Tipo: 'success', 'error', 'warning', 'info'
     * @param {number} duracion - Duración en ms (opcional)
     */
    mostrar(mensaje, tipo = 'info', duracion = 3000) {
        const notification = this.crearNotificacion(mensaje, tipo);
        this.container.appendChild(notification);
        this.notifications.push(notification);

        // Animación de entrada
        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        });

        // Auto-eliminar
        setTimeout(() => {
            this.eliminar(notification);
        }, duracion);

        return notification;
    }

    /**
     * Crea el elemento de notificación
     */
    crearNotificacion(mensaje, tipo) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${tipo}`;
        
        const colores = {
            success: { bg: '#4CAF50', icon: '✅' },
            error: { bg: '#f44336', icon: '❌' },
            warning: { bg: '#FF9800', icon: '⚠️' },
            info: { bg: '#2196F3', icon: 'ℹ️' }
        };

        const color = colores[tipo] || colores.info;

        notification.style.cssText = `
            background: ${color.bg};
            color: white;
            padding: 12px 16px;
            margin-bottom: 10px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            pointer-events: auto;
            display: flex;
            align-items: center;
            gap: 8px;
            max-width: 350px;
            word-wrap: break-word;
        `;

        notification.innerHTML = `
            <span class="notification-icon">${color.icon}</span>
            <span class="notification-message">${Utils.escapeHtml(mensaje)}</span>
            <button class="notification-close" style="
                background: none;
                border: none;
                color: white;
                cursor: pointer;
                padding: 0;
                margin-left: auto;
                font-size: 18px;
                opacity: 0.7;
                transition: opacity 0.2s;
            ">&times;</button>
        `;

        // Evento para cerrar manualmente
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => this.eliminar(notification));
        closeBtn.addEventListener('mouseenter', () => closeBtn.style.opacity = '1');
        closeBtn.addEventListener('mouseleave', () => closeBtn.style.opacity = '0.7');

        return notification;
    }

    /**
     * Elimina una notificación
     */
    eliminar(notification) {
        if (!notification || !this.container.contains(notification)) return;

        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';

        setTimeout(() => {
            if (this.container.contains(notification)) {
                this.container.removeChild(notification);
            }
            this.notifications = this.notifications.filter(n => n !== notification);
        }, 300);
    }

    /**
     * Elimina todas las notificaciones
     */
    eliminarTodas() {
        this.notifications.forEach(notification => this.eliminar(notification));
    }

    /**
     * Métodos de conveniencia
     */
    success(mensaje, duracion) {
        return this.mostrar(mensaje, 'success', duracion);
    }

    error(mensaje, duracion) {
        return this.mostrar(mensaje, 'error', duracion);
    }

    warning(mensaje, duracion) {
        return this.mostrar(mensaje, 'warning', duracion);
    }

    info(mensaje, duracion) {
        return this.mostrar(mensaje, 'info', duracion);
    }
}

// Instancia global
window.notificationManager = new NotificationManager();

// Función global de conveniencia
window.mostrarNotificacion = (mensaje, tipo, duracion) => {
    return window.notificationManager.mostrar(mensaje, tipo, duracion);
};