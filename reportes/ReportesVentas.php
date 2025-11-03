<?php
/**
 * Generador de reportes de ventas
 * Sistema para generar reportes diarios, semanales, mensuales y anuales
 */

// No incluir db.php aquí, se debe incluir desde el archivo que use esta clase

class ReportesVentas {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Genera reporte de ventas del día actual
     * @return array Datos del reporte
     */
    public function reporteDiario() {
        $fecha = date('Y-m-d');
        return $this->generarReporte('dia', $fecha, $fecha);
    }
    
    /**
     * Genera reporte de ventas de la semana actual
     * @return array Datos del reporte
     */
    public function reporteSemanal() {
        $fechaInicio = date('Y-m-d', strtotime('monday this week'));
        $fechaFin = date('Y-m-d', strtotime('sunday this week'));
        return $this->generarReporte('semana', $fechaInicio, $fechaFin);
    }
    
    /**
     * Genera reporte de ventas del mes actual
     * @return array Datos del reporte
     */
    public function reporteMensual() {
        $fechaInicio = date('Y-m-01');
        $fechaFin = date('Y-m-t');
        return $this->generarReporte('mes', $fechaInicio, $fechaFin);
    }
    
    /**
     * Genera reporte de ventas del año actual
     * @return array Datos del reporte
     */
    public function reporteAnual() {
        $fechaInicio = date('Y-01-01');
        $fechaFin = date('Y-12-31');
        return $this->generarReporte('año', $fechaInicio, $fechaFin);
    }
    
    /**
     * Genera reporte personalizado por fechas
     * @param string $fechaInicio Fecha de inicio (Y-m-d)
     * @param string $fechaFin Fecha de fin (Y-m-d)
     * @return array Datos del reporte
     */
    public function reportePersonalizado($fechaInicio, $fechaFin) {
        return $this->generarReporte('personalizado', $fechaInicio, $fechaFin);
    }
    
    /**
     * Genera reporte de ventas
     * @param string $periodo Tipo de período
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @return array Datos del reporte
     */
    private function generarReporte($periodo, $fechaInicio, $fechaFin) {
        try {
            // Llamar al procedimiento almacenado para generar/actualizar el reporte
            $stmt = $this->pdo->prepare("CALL generar_reporte_ventas(?, ?, ?)");
            $stmt->execute([$periodo, $fechaInicio, $fechaFin]);
            
            // Obtener datos del reporte
            $stmt = $this->pdo->prepare("
                SELECT * FROM reportes_ventas 
                WHERE periodo = ? AND fecha_inicio = ? AND fecha_fin = ?
                ORDER BY fecha_generacion DESC 
                LIMIT 1
            ");
            $stmt->execute([$periodo, $fechaInicio, $fechaFin]);
            $reporte = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reporte) {
                throw new Exception("No se pudo generar el reporte");
            }
            
            // Obtener datos adicionales
            $reporte['detalle_productos'] = $this->obtenerProductosMasVendidos($fechaInicio, $fechaFin);
            $reporte['ventas_por_dia'] = $this->obtenerVentasPorDia($fechaInicio, $fechaFin);
            $reporte['comparacion_periodos'] = $this->compararConPeriodoAnterior($periodo, $fechaInicio, $fechaFin);
            
            return $reporte;
            
        } catch (Exception $e) {
            throw new Exception("Error al generar reporte: " . $e->getMessage());
        }
    }
    
    /**
     * Obtiene los productos más vendidos en un período
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @param int $limite Número máximo de productos a retornar
     * @return array Lista de productos más vendidos
     */
    public function obtenerProductosMasVendidos($fechaInicio, $fechaFin, $limite = 10) {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.Id_Producto,
                p.Nombre_Producto,
                p.codigo_producto,
                SUM(dc.Cantidad) as total_vendido,
                SUM(dc.Total) as ingresos_producto,
                AVG(dc.Precio_Unitario_Momento) as precio_promedio,
                COUNT(DISTINCT vu.id_venta) as numero_ventas
            FROM venta_unificada vu
            JOIN detalle_carrito dc ON vu.id_venta = dc.Id_Venta
            JOIN producto p ON dc.Id_Producto = p.Id_Producto
            WHERE DATE(vu.fecha_venta) BETWEEN ? AND ?
            AND vu.Estado = 'Entregado'
            GROUP BY p.Id_Producto
            ORDER BY total_vendido DESC
            LIMIT ?
        ");
        $stmt->execute([$fechaInicio, $fechaFin, $limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene ventas agrupadas por día
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @return array Ventas por día
     */
    public function obtenerVentasPorDia($fechaInicio, $fechaFin) {
        $stmt = $this->pdo->prepare("
            SELECT 
                DATE(fecha_venta) as fecha,
                COUNT(*) as numero_ventas,
                SUM(Total_Venta) as total_ventas,
                SUM(CASE WHEN tipo_venta = 'presencial' THEN Total_Venta ELSE 0 END) as ventas_presenciales,
                SUM(CASE WHEN tipo_venta = 'virtual' THEN Total_Venta ELSE 0 END) as ventas_virtuales,
                AVG(Total_Venta) as ticket_promedio
            FROM venta_unificada
            WHERE DATE(fecha_venta) BETWEEN ? AND ?
            AND Estado = 'Entregado'
            GROUP BY DATE(fecha_venta)
            ORDER BY fecha ASC
        ");
        $stmt->execute([$fechaInicio, $fechaFin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Compara ventas con el período anterior
     * @param string $periodo Tipo de período
     * @param string $fechaInicio Fecha de inicio del período actual
     * @param string $fechaFin Fecha de fin del período actual
     * @return array Comparación con período anterior
     */
    public function compararConPeriodoAnterior($periodo, $fechaInicio, $fechaFin) {
        // Calcular fechas del período anterior
        $diasPeriodo = (strtotime($fechaFin) - strtotime($fechaInicio)) / (60 * 60 * 24) + 1;
        $fechaInicioAnterior = date('Y-m-d', strtotime($fechaInicio . " -{$diasPeriodo} days"));
        $fechaFinAnterior = date('Y-m-d', strtotime($fechaInicio . " -1 day"));
        
        // Obtener datos del período actual
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as transacciones,
                COALESCE(SUM(Total_Venta), 0) as total_ventas
            FROM venta_unificada
            WHERE DATE(fecha_venta) BETWEEN ? AND ?
            AND Estado = 'Entregado'
        ");
        $stmt->execute([$fechaInicio, $fechaFin]);
        $actual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener datos del período anterior
        $stmt->execute([$fechaInicioAnterior, $fechaFinAnterior]);
        $anterior = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calcular porcentajes de cambio
        $cambioVentas = $anterior['total_ventas'] > 0 
            ? (($actual['total_ventas'] - $anterior['total_ventas']) / $anterior['total_ventas']) * 100 
            : 0;
            
        $cambioTransacciones = $anterior['transacciones'] > 0 
            ? (($actual['transacciones'] - $anterior['transacciones']) / $anterior['transacciones']) * 100 
            : 0;
        
        return [
            'periodo_actual' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'total_ventas' => $actual['total_ventas'],
                'transacciones' => $actual['transacciones']
            ],
            'periodo_anterior' => [
                'fecha_inicio' => $fechaInicioAnterior,
                'fecha_fin' => $fechaFinAnterior,
                'total_ventas' => $anterior['total_ventas'],
                'transacciones' => $anterior['transacciones']
            ],
            'cambios' => [
                'ventas_porcentaje' => round($cambioVentas, 2),
                'transacciones_porcentaje' => round($cambioTransacciones, 2),
                'diferencia_ventas' => $actual['total_ventas'] - $anterior['total_ventas'],
                'diferencia_transacciones' => $actual['transacciones'] - $anterior['transacciones']
            ]
        ];
    }
    
    /**
     * Obtiene estadísticas de empleados (para ventas presenciales)
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @return array Estadísticas por empleado
     */
    public function obtenerEstadisticasEmpleados($fechaInicio, $fechaFin) {
        $stmt = $this->pdo->prepare("
            SELECT 
                e.id_empleado,
                u.nombre_usuario,
                e.Cargo,
                COUNT(vu.id_venta) as ventas_realizadas,
                SUM(vu.Total_Venta) as total_vendido,
                AVG(vu.Total_Venta) as ticket_promedio
            FROM empleado e
            JOIN usuario u ON e.id_usuario = u.id_usuario
            LEFT JOIN venta_unificada vu ON e.id_empleado = vu.id_empleado 
                AND DATE(vu.fecha_venta) BETWEEN ? AND ?
                AND vu.tipo_venta = 'presencial'
                AND vu.Estado = 'Entregado'
            GROUP BY e.id_empleado
            ORDER BY total_vendido DESC
        ");
        $stmt->execute([$fechaInicio, $fechaFin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene métricas de conversión de carritos virtuales
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFin Fecha de fin
     * @return array Métricas de conversión
     */
    public function obtenerMetricasConversion($fechaInicio, $fechaFin) {
        // Carritos creados (estado Pendiente o superior)
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as carritos_creados
            FROM venta_unificada
            WHERE tipo_venta = 'virtual'
            AND DATE(fecha_venta) BETWEEN ? AND ?
        ");
        $stmt->execute([$fechaInicio, $fechaFin]);
        $carritosCreados = $stmt->fetchColumn();
        
        // Carritos completados (estado Entregado)
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as carritos_completados
            FROM venta_unificada
            WHERE tipo_venta = 'virtual'
            AND Estado = 'Entregado'
            AND DATE(fecha_venta) BETWEEN ? AND ?
        ");
        $stmt->execute([$fechaInicio, $fechaFin]);
        $carritosCompletados = $stmt->fetchColumn();
        
        $tasaConversion = $carritosCreados > 0 ? ($carritosCompletados / $carritosCreados) * 100 : 0;
        
        return [
            'carritos_creados' => $carritosCreados,
            'carritos_completados' => $carritosCompletados,
            'tasa_conversion' => round($tasaConversion, 2),
            'carritos_abandonados' => $carritosCreados - $carritosCompletados
        ];
    }
}

/**
 * Ejemplo de uso:
 * 
 * require_once 'ReportesVentas.php';
 * require_once '../carrito/db.php';
 * 
 * $reportes = new ReportesVentas($pdo);
 * 
 * // Reporte del día
 * $reporteDiario = $reportes->reporteDiario();
 * 
 * // Reporte semanal
 * $reporteSemanal = $reportes->reporteSemanal();
 * 
 * // Reporte personalizado
 * $reportePersonalizado = $reportes->reportePersonalizado('2025-10-01', '2025-10-31');
 * 
 * // Productos más vendidos
 * $topProductos = $reportes->obtenerProductosMasVendidos('2025-10-01', '2025-10-31', 5);
 */
?>