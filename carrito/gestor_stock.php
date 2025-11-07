<?php
/**
 * Funciones para gestión avanzada de stock
 * Maneja la renovación automática y notificaciones de stock bajo
 */

declare(strict_types=1);

class GestorStock {
    private PDO $pdo;
    
    // Configuración de renovación por categoría
    private array $configuracionRenovacion = [
        1 => 200, // Frutas y Verduras - 200 unidades
        2 => 50,  // Carnes y Pescados - 50 unidades
        3 => 150, // Lácteos y Huevos - 150 unidades
        4 => 100, // Panadería - 100 unidades
        5 => 180, // Bebidas - 180 unidades
        6 => 120, // Despensa - 120 unidades
    ];
    
    private int $stockRenovacionDefault = 100;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Verifica y actualiza el stock de un producto
     * @param int $idProducto ID del producto
     * @param int $cantidadSolicitada Cantidad que se quiere reducir
     * @return array Resultado de la operación
     */
    public function procesarReduccionStock(int $idProducto, int $cantidadSolicitada): array {
        try {
            // Obtener información del producto
            $stmt = $this->pdo->prepare("
                SELECT p.Stock, p.Id_Categoria, p.Nombre_Producto, c.Nombre_Categoria 
                FROM producto p 
                LEFT JOIN categoria c ON p.Id_Categoria = c.Id_Categoria 
                WHERE p.Id_Producto = ?
            ");
            $stmt->execute([$idProducto]);
            $producto = $stmt->fetch();
            
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }
            
            $stockActual = (int)$producto['Stock'];
            $idCategoria = (int)$producto['Id_Categoria'];
            
            // Verificar stock disponible
            if ($stockActual < $cantidadSolicitada) {
                return [
                    'success' => false,
                    'message' => "Stock insuficiente. Disponible: {$stockActual}",
                    'stock_actual' => $stockActual
                ];
            }
            
            // Reducir stock
            $nuevoStock = $stockActual - $cantidadSolicitada;
            $this->actualizarStock($idProducto, $nuevoStock);
            
            $resultado = [
                'success' => true,
                'stock_anterior' => $stockActual,
                'stock_actual' => $nuevoStock,
                'cantidad_reducida' => $cantidadSolicitada,
                'renovado' => false
            ];
            
            // Verificar si necesita renovación
            if ($nuevoStock <= 0) {
                $stockRenovado = $this->renovarStock($idProducto, $idCategoria);
                $resultado['renovado'] = true;
                $resultado['stock_actual'] = $stockRenovado;
                $resultado['mensaje_renovacion'] = "Stock agotado. Renovado automáticamente a {$stockRenovado} unidades.";
                
                // Log de renovación
                $this->logRenovacion($idProducto, $producto['Nombre_Producto'], $stockRenovado);
            }
            
            return $resultado;
            
        } catch (Exception $e) {
            throw new Exception("Error al procesar stock: " . $e->getMessage());
        }
    }
    
    /**
     * Renueva el stock de un producto según su categoría
     */
    private function renovarStock(int $idProducto, int $idCategoria): int {
        $stockRenovado = $this->configuracionRenovacion[$idCategoria] ?? $this->stockRenovacionDefault;
        $this->actualizarStock($idProducto, $stockRenovado);
        return $stockRenovado;
    }
    
    /**
     * Actualiza el stock en la base de datos
     */
    private function actualizarStock(int $idProducto, int $nuevoStock): void {
        $stmt = $this->pdo->prepare("UPDATE producto SET Stock = ? WHERE Id_Producto = ?");
        $stmt->execute([$nuevoStock, $idProducto]);
    }
    
    /**
     * Registra la renovación de stock en logs
     */
    private function logRenovacion(int $idProducto, string $nombreProducto, int $stockRenovado): void {
        $mensaje = sprintf(
            "[%s] Stock renovado - Producto ID: %d (%s) - Nuevo stock: %d unidades",
            date('Y-m-d H:i:s'),
            $idProducto,
            $nombreProducto,
            $stockRenovado
        );
        error_log($mensaje);
    }
    
    /**
     * Obtiene productos con stock bajo (menos de 10 unidades)
     */
    public function obtenerProductosStockBajo(): array {
        $stmt = $this->pdo->query("
            SELECT 
                Id_Producto,
                Nombre_Producto,
                Stock,
                Id_Categoria
            FROM producto 
            WHERE Stock <= 10 AND Stock > 0
            ORDER BY Stock ASC
        ");
        
        return $stmt->fetchAll();
    }
    
    /**
     * Configura la cantidad de renovación para una categoría específica
     */
    public function configurarRenovacionCategoria(int $idCategoria, int $cantidad): void {
        $this->configuracionRenovacion[$idCategoria] = $cantidad;
    }
}
?>