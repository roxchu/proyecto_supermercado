<?php
/**
 * Script para agregar o actualizar la cantidad de un producto en el carrito del usuario.
 * Utiliza PDO para las operaciones de base de datos.
 * Estilo de conexión y respuesta JSON similar a login.php/registro.php.
 */
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

// ---------------------------------------------
// CONFIGURACIÓN DE LA BASE DE DATOS
// ---------------------------------------------
// Nota: En una aplicación real, esta configuración debería estar en un solo archivo incluido (e.g., 'db.php')
$host = 'localhost';
$db   = 'supermercado';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES      => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// ---------------------------------------------
// LÓGICA DE AGREGAR AL CARRITO
// ---------------------------------------------
try {
    // 1. --- Verificar Usuario Logueado ---
    // Usamos 'user_id' como en login.php/registro.php
    $idUsuario = $_SESSION['user_id'] ?? null;

    if (!$idUsuario) {
        http_response_code(401); // No autorizado
        echo json_encode([
            'success' => false,
            'message' => 'Debe iniciar sesión para agregar productos al carrito.'
        ]);
        exit;
    }

    // 2. --- Leer y Validar la Entrada JSON (Esperamos JSON, como en el ejemplo previo) ---
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); // Método no permitido
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($input['id_producto'], $input['cantidad'])) {
        http_response_code(400); // Solicitud incorrecta
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos o formato JSON inválido.'
        ]);
        exit;
    }

    // Sanitización y conversión de tipos
    $idProducto = (int)$input['id_producto'];
    $cantidad = max(1, (int)$input['cantidad']); // Aseguramos cantidad >= 1

    if ($idProducto <= 0) {
        http_response_code(400); 
        echo json_encode(['success' => false, 'message' => 'ID de producto inválido.']);
        exit;
    }
    
    // 3. --- Obtener el Precio Actual del Producto (Crucial para la seguridad) ---
    // Se asume la existencia de la tabla 'producto' con la columna 'precio_actual'
    $stmtPrecio = $pdo->prepare("SELECT precio_actual FROM producto WHERE Id_Producto = ?");
    $stmtPrecio->execute([$idProducto]);
    $precioUnitario = $stmtPrecio->fetchColumn(); 

    if (!$precioUnitario) {
        http_response_code(404); // No encontrado
        echo json_encode([
            'success' => false,
            'message' => 'Producto no encontrado o sin precio disponible.'
        ]);
        exit;
    }
    
    $precioUnitario = (float)$precioUnitario; 

    // 4. --- Lógica de Carrito: Insertar o Actualizar ---

    // A. Verificar si el producto ya está en el carrito
    $stmtCheck = $pdo->prepare("
        SELECT Id_Carrito, Cantidad
        FROM carrito
        WHERE id_usuario = ? AND Id_Producto = ?
    ");
    $stmtCheck->execute([$idUsuario, $idProducto]);
    $item = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    $pdo->beginTransaction(); // Iniciar transacción para asegurar la integridad

    if ($item) {
        // B. El producto ya existe: Actualizar la cantidad
        $nuevaCantidad = $item['Cantidad'] + $cantidad;
        $idCarrito = $item['Id_Carrito'];
        
        // Actualizar Cantidad y Precio_Unitario_Momento. 'Total' es GENERADO por la DB.
        $sqlUpdate = "
            UPDATE carrito
            SET Cantidad = ?, Precio_Unitario_Momento = ? 
            WHERE Id_Carrito = ?
        ";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([$nuevaCantidad, $precioUnitario, $idCarrito]);
        
        $mensajeExito = "Cantidad actualizada correctamente. Nueva cantidad: " . $nuevaCantidad;
    } else {
        // C. El producto es nuevo: Insertar nuevo registro
        // 'Total' se omite en el INSERT porque es una columna GENERATED.
        $sqlInsert = "
            INSERT INTO carrito (id_usuario, Id_Producto, Precio_Unitario_Momento, Cantidad)
            VALUES (?, ?, ?, ?)
        ";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([$idUsuario, $idProducto, $precioUnitario, $cantidad]);
        
        $mensajeExito = "Producto agregado por primera vez al carrito.";
    }

    $pdo->commit(); // Confirmar los cambios

    // 5. --- Respuesta de Éxito ---
    echo json_encode([
        'success' => true,
        'message' => $mensajeExito
    ]);

} catch (Throwable $e) {
    // 6. --- Manejo de Errores ---
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error en agregar_carrito.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno al procesar el carrito.',
        'debug' => $e->getMessage() // Útil para desarrollo
    ]);
}
?>