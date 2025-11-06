/**
 * Módulo Principal del Carrito
 * Une todos los componentes del carrito
 */

import { CarritoAPI } from '../modules/carrito-api.js';
import { CarritoUI } from './carrito-ui.js';
import { CarritoEvents } from './carrito-events.js';

export class Carrito {
    constructor() {
        this.api = new CarritoAPI();
        this.ui = new CarritoUI();
        this.events = new CarritoEvents(this.ui, this.api);
    }

    /**
     * Inicializa el carrito completo
     */
    init() {
        console.log('🛒 Carrito inicializado');
    }

    /**
     * Obtiene la instancia de la API
     */
    getAPI() {
        return this.api;
    }

    /**
     * Obtiene la instancia de la UI
     */
    getUI() {
        return this.ui;
    }

    /**
     * Obtiene la instancia de eventos
     */
    getEvents() {
        return this.events;
    }
}

// Auto-inicialización cuando se carga el DOM
document.addEventListener("DOMContentLoaded", () => {
    window.carrito = new Carrito();
    window.carrito.init();
});