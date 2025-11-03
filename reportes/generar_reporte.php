<?php
/**
 * API endpoint para generar reportes de ventas
 * Maneja las peticiones AJAX desde la interfaz de reportes
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manear preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../login/control_acceso.php';
require_once __DIR__ . '/../carrito/db.php';
require_once __DIR__ . '/ReportesVentas.php';

// Verificar que el usuario tenga permisos (admin o dueño)
try {
    session_start();
    if (!isset($_SESSION['usuario_logueado']) || !isset($_SESSION['rol'])) {
        throw new Exception('No autorizado');
    }
    
    $rolesPermitidos = ['admin', 'dueño'];
    if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
        throw new Exception('Permisos insuficientes');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de autorización: ' . $e->getMessage()
    ]);
    exit;
}

// Procesar la petición
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

try {
    // Obtener datos de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['tipo'])) {
        throw new Exception('Datos de entrada inválidos');
    }
    
    $tipo = $input['tipo'];
    $reportes = new ReportesVentas($pdo);
    $reporte = null;
    
    // Generar reporte según el tipo
    switch ($tipo) {
        case 'diario':
            $reporte = $reportes->reporteDiario();
            break;
            
        case 'semanal':
            $reporte = $reportes->reporteSemanal();
            break;
            
        case 'mensual':
            $reporte = $reportes->reporteMensual();
            break;
            
        case 'anual':
            $reporte = $reportes->reporteAnual();
            break;
            
        case 'personalizado':
            if (!isset($input['fechaInicio']) || !isset($input['fechaFin'])) {
                throw new Exception('Fechas requeridas para reporte personalizado');
            }
            
            $fechaInicio = $input['fechaInicio'];
            $fechaFin = $input['fechaFin'];
            
            // Validar formato de fechas
            if (!DateTime::createFromFormat('Y-m-d', $fechaInicio) || 
                !DateTime::createFromFormat('Y-m-d', $fechaFin)) {
                throw new Exception('Formato de fecha inválido (usar Y-m-d)');
            }
            
            // Validar que fecha inicio sea anterior a fecha fin
            if ($fechaInicio > $fechaFin) {
                throw new Exception('La fecha de inicio debe ser anterior a la fecha de fin');
            }
            
            $reporte = $reportes->reportePersonalizado($fechaInicio, $fechaFin);
            break;
            
        default:
            throw new Exception('Tipo de reporte no válido');
    }
    
    if (!$reporte) {
        throw new Exception('No se pudo generar el reporte');
    }
    
    // Obtener datos adicionales
    $fechaInicio = $reporte['fecha_inicio'];
    $fechaFin = $reporte['fecha_fin'];
    
    // Agregar estadísticas de empleados si están disponibles
    try {
        $reporte['estadisticas_empleados'] = $reportes->obtenerEstadisticasEmpleados($fechaInicio, $fechaFin);
    } catch (Exception $e) {
        $reporte['estadisticas_empleados'] = [];
    }
    
    // Agregar métricas de conversión para ventas virtuales
    try {
        $reporte['metricas_conversion'] = $reportes->obtenerMetricasConversion($fechaInicio, $fechaFin);
    } catch (Exception $e) {
        $reporte['metricas_conversion'] = [
            'carritos_creados' => 0,
            'carritos_completados' => 0,
            'tasa_conversion' => 0,
            'carritos_abandonados' => 0
        ];
    }
    
    // Formatear números para mejor presentación
    $reporte['total_ventas'] = number_format($reporte['total_ventas'], 2, '.', '');
    $reporte['ventas_presenciales'] = number_format($reporte['ventas_presenciales'], 2, '.', '');
    $reporte['ventas_virtuales'] = number_format($reporte['ventas_virtuales'], 2, '.', '');
    
    // Formatear productos más vendidos
    if (isset($reporte['detalle_productos'])) {
        foreach ($reporte['detalle_productos'] as &$producto) {
            $producto['ingresos_producto'] = number_format($producto['ingresos_producto'], 2, '.', '');
            $producto['precio_promedio'] = number_format($producto['precio_promedio'], 2, '.', '');
        }
    }
    
    // Formatear ventas por día
    if (isset($reporte['ventas_por_dia'])) {
        foreach ($reporte['ventas_por_dia'] as &$venta) {
            $venta['total_ventas'] = number_format($venta['total_ventas'], 2, '.', '');
            $venta['ventas_presenciales'] = number_format($venta['ventas_presenciales'], 2, '.', '');
            $venta['ventas_virtuales'] = number_format($venta['ventas_virtuales'], 2, '.', '');
            $venta['ticket_promedio'] = number_format($venta['ticket_promedio'], 2, '.', '');
        }
    }
    
    // Agregar información del período para el frontend
    $reporte['info_periodo'] = [
        'tipo' => $tipo,
        'fecha_inicio_formateada' => date('d/m/Y', strtotime($fechaInicio)),
        'fecha_fin_formateada' => date('d/m/Y', strtotime($fechaFin)),
        'dias_periodo' => (strtotime($fechaFin) - strtotime($fechaInicio)) / (60 * 60 * 24) + 1
    ];
    
    echo json_encode([
        'success' => true,
        'reporte' => $reporte,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Log de acceso a reportes para auditoría
 */
function registrarAccesoReporte($tipoReporte, $usuarioId, $exito = true) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO log_reportes (
                tipo_reporte, 
                id_usuario, 
                fecha_acceso, 
                exito, 
                ip_acceso
            ) VALUES (?, ?, NOW(), ?, ?)
        ");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->execute([$tipoReporte, $usuarioId, $exito ? 1 : 0, $ip]);
    } catch (Exception $e) {
        // Log error but don't break the main functionality
        error_log("Error registrando acceso a reporte: " . $e->getMessage());
    }
}

// Registrar el acceso si tenemos la sesión disponible
if (isset($_SESSION['id_usuario']) && isset($tipo)) {
    registrarAccesoReporte($tipo, $_SESSION['id_usuario'], true);
}
?>