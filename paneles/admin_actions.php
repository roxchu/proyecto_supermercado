<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../login/control_acceso.php';

// SOLO permite admin para CUALQUIER acción en este archivo
verificar_rol('admin');

// Incluimos la conexión a la base de datos, que faltaba.
require_once __DIR__ . '/../carrito/db.php'; 

// Verificamos que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Método no permitido.']));
}

// Obtenemos la acción solicitada
$action = $_POST['action'] ?? '';

// Usamos un try-catch para todas las operaciones
try {
    
    switch ($action) {

        // --- ACCIONES DE USUARIO ---
        case 'update_user_role':
            $id_usuario = $_POST['id_usuario'];
            $id_rol = $_POST['id_rol'];
            
            $stmt = $pdo->prepare("UPDATE usuario SET id_rol = ? WHERE id_usuario = ?");
            $stmt->execute([$id_rol, $id_usuario]);
            
            echo json_encode(['success' => true, 'message' => 'Rol de usuario actualizado.']);
            break;

        // --- ACCIONES DE ROL ---
        case 'add_role':
            $nombre_rol = $_POST['nombre_rol'];
            
            $stmt = $pdo->prepare("INSERT INTO rol (nombre_rol) VALUES (?)");
            $stmt->execute([$nombre_rol]);
            
            echo json_encode(['success' => true, 'message' => 'Rol creado.']);
            break;

        case 'update_role':
            $id_rol = $_POST['id_rol'];
            $nombre_rol = $_POST['nombre_rol'];
            
            $stmt = $pdo->prepare("UPDATE rol SET nombre_rol = ? WHERE id_rol = ?");
            $stmt->execute([$nombre_rol, $id_rol]);
            
            echo json_encode(['success' => true, 'message' => 'Rol actualizado.']);
            break;

        // --- ACCIÓN GENÉRICA DE ELIMINAR ---
        case 'delete_item':
            $id = $_POST['id'];
            $tipo = $_POST['tipo'];
            
            $sql = "";

            if ($tipo == 'usuario') {
                $sql = "DELETE FROM usuario WHERE id_usuario = ?";
            } 
            // *** MODIFICACIÓN ***: Se eliminó el case 'rol' para evitar su eliminación
            /* elseif ($tipo == 'rol') {
                $sql = "DELETE FROM rol WHERE id_rol = ?";
            } */
            elseif ($tipo == 'producto') {
                // Al borrar un producto, también borramos sus imágenes (principal y secundarias si existieran)
                $pdo->beginTransaction();
                $stmt_img = $pdo->prepare("DELETE FROM producto_imagenes WHERE Id_Producto = ?");
                $stmt_img->execute([$id]);
                
                $stmt_prod = $pdo->prepare("DELETE FROM producto WHERE Id_Producto = ?");
                $stmt_prod->execute([$id]);
                $pdo->commit();
                
                echo json_encode(['success' => true, 'message' => 'Producto eliminado.']);
                exit; // Salimos porque ya gestionamos la transacción
            }

            if ($sql) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Elemento eliminado.']);
            } else {
                throw new Exception("Tipo de elemento no válido para eliminar.");
            }
            break;

        // --- ACCIÓN DE PRODUCTO (CREAR/EDITAR) ---
        case 'save_product':
            // Esta es la acción más compleja.
            $id_producto = $_POST['id_producto'] ?? null;
            $nombre = $_POST['nombre'];
            $descripcion = $_POST['descripcion'];
            $precio_actual = $_POST['precio_actual'];
            $precio_anterior = !empty($_POST['precio_anterior']) ? $_POST['precio_anterior'] : null;
            $stock = $_POST['stock'];
            $id_categoria = $_POST['id_categoria'];
            
            // Solo se usa la imagen principal
            $imagen_principal = $_POST['imagen_url_principal']; 

            $pdo->beginTransaction();

            if ($id_producto) {
                // --- MODO UPDATE (EDITAR) ---
                // Se elimina 'imagen_url' de la tabla 'producto' para resolver el error SQL 1054
                $sql_prod = "UPDATE producto 
                             SET Nombre_Producto = ?, Descripcion = ?, precio_actual = ?, 
                                 precio_anterior = ?, Stock = ?, Id_Categoria = ?
                             WHERE Id_Producto = ?";
                $stmt_prod = $pdo->prepare($sql_prod);
                $stmt_prod->execute([$nombre, $descripcion, $precio_actual, $precio_anterior, $stock, $id_categoria, $id_producto]);
                $message = 'Producto actualizado.';
            } else {
                // --- MODO INSERT (CREAR) ---
                // Se elimina 'imagen_url' de la tabla 'producto' para resolver el error SQL 1054
                $sql_prod = "INSERT INTO producto (Nombre_Producto, Descripcion, precio_actual, precio_anterior, Stock, Id_Categoria) 
                             VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_prod = $pdo->prepare($sql_prod);
                $stmt_prod->execute([$nombre, $descripcion, $precio_actual, $precio_anterior, $stock, $id_categoria]);
                $id_producto = $pdo->lastInsertId(); // Obtenemos el ID del nuevo producto
                $message = 'Producto creado.';
            }

            // --- GESTIÓN DE IMAGEN PRINCIPAL (TABLA producto_imagenes con orden = 1) ---
            
            // 1. Borramos la imagen principal existente (orden = 1) para este producto
            $stmt_delete_img = $pdo->prepare("DELETE FROM producto_imagenes WHERE Id_Producto = ? AND orden = 1");
            $stmt_delete_img->execute([$id_producto]);
            
            // 2. Insertamos la nueva imagen principal (si hay URL)
            if (!empty($imagen_principal)) {
                $stmt_insert_img = $pdo->prepare("INSERT INTO producto_imagenes (Id_Producto, url_imagen, orden) VALUES (?, ?, 1)");
                $stmt_insert_img->execute([$id_producto, $imagen_principal]);
            }
            
            // Si todo salió bien, confirmamos la transacción
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => $message]);
            
            break;

        default:
            throw new Exception("Acción no reconocida.");
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Revertimos si algo falló
    }
    // Manejo de errores de base de datos (ej. claves foráneas duplicadas)
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de BD: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>