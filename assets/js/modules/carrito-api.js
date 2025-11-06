/**
 * Módulo API del Carrito
 * Maneja todas las peticiones al servidor relacionadas con el carrito
 */

export class CarritoAPI {
    constructor() {
        this.baseURL = '';
    }

    /**
     * Verifica si hay una sesión activa
     */
    async verificarSesion() {
        try {
            const resp = await fetch("login/check_session.php", {
                credentials: "include", 
            });
            const data = await resp.json();
            return !!(data.logged_in || data.user_id || data.id_usuario);
        } catch (err) {
            console.error("Error al verificar la sesión:", err);
            return false;
        }
    }

    /**
     * Obtiene el contenido del carrito desde el servidor
     */
    async obtenerCarrito() {
        try {
            const resp = await fetch("carrito/obtener_carrito.php", {
                credentials: "include",
            });

            if (!resp.ok) {
                const errorData = await resp.json();
                throw new Error(errorData.message || `Error HTTP: ${resp.status}`);
            }

            return await resp.json();
        } catch (err) {
            console.error("Error al cargar el carrito:", err);
            throw err;
        }
    }

    /**
     * Agrega un producto al carrito
     */
    async agregarProducto(idProducto, cantidad = 1) {
        try {
            const resp = await fetch("carrito/agregar_carrito.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                credentials: "include", 
                body: JSON.stringify({ id_producto: idProducto, cantidad }),
            });

            return await resp.json();
        } catch (err) {
            console.error("Error al agregar producto:", err);
            throw err;
        }
    }

    /**
     * Elimina un producto del carrito
     */
    async eliminarProducto(idCarrito, idProducto) {
        try {
            const bodyData = idCarrito ? { id_carrito: idCarrito } : { id_producto: idProducto };

            const resp = await fetch("carrito/eliminar_item.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                credentials: "include",
                body: JSON.stringify(bodyData),
            });

            return await resp.json();
        } catch (err) {
            console.error("Error al eliminar producto:", err);
            throw err;
        }
    }
}