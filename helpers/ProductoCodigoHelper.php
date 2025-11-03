<?php
/**
 * Helper functions para el manejo del nuevo formato de código de producto
 * Formato: ABCD123X donde:
 * - ABCD: 4 caracteres del código del producto
 * - 123: 3 dígitos de cantidad (000-999)
 * - X: 1 caracter separador/identificador
 */

class ProductoCodigoHelper {
    
    /**
     * Codifica un producto en el formato de 8 caracteres
     * @param string $codigoProducto Código de 4 caracteres del producto
     * @param int $cantidad Cantidad de productos (0-999)
     * @param string $separador Caracter separador (por defecto '|')
     * @return string Código codificado de 8 caracteres
     */
    public static function codificar($codigoProducto, $cantidad, $separador = '|') {
        // Validar código de producto (4 caracteres)
        if (strlen($codigoProducto) !== 4) {
            throw new InvalidArgumentException("El código del producto debe tener exactamente 4 caracteres");
        }
        
        // Validar cantidad (0-999)
        if ($cantidad < 0 || $cantidad > 999) {
            throw new InvalidArgumentException("La cantidad debe estar entre 0 y 999");
        }
        
        // Validar separador (1 caracter)
        if (strlen($separador) !== 1) {
            throw new InvalidArgumentException("El separador debe ser un solo caracter");
        }
        
        // Formatear cantidad con ceros a la izquierda
        $cantidadFormateada = str_pad($cantidad, 3, '0', STR_PAD_LEFT);
        
        return $codigoProducto . $cantidadFormateada . $separador;
    }
    
    /**
     * Decodifica un código de 8 caracteres
     * @param string $codigoCodificado Código de 8 caracteres
     * @return array Array asociativo con codigo_producto, cantidad y separador
     */
    public static function decodificar($codigoCodificado) {
        // Validar longitud
        if (strlen($codigoCodificado) !== 8) {
            throw new InvalidArgumentException("El código codificado debe tener exactamente 8 caracteres");
        }
        
        return [
            'codigo_producto' => substr($codigoCodificado, 0, 4),
            'cantidad' => (int)substr($codigoCodificado, 4, 3),
            'separador' => substr($codigoCodificado, 7, 1)
        ];
    }
    
    /**
     * Obtiene el código del producto desde un código codificado
     * @param string $codigoCodificado Código de 8 caracteres
     * @return string Código del producto (4 caracteres)
     */
    public static function obtenerCodigoProducto($codigoCodificado) {
        $decodificado = self::decodificar($codigoCodificado);
        return $decodificado['codigo_producto'];
    }
    
    /**
     * Obtiene la cantidad desde un código codificado
     * @param string $codigoCodificado Código de 8 caracteres
     * @return int Cantidad de productos
     */
    public static function obtenerCantidad($codigoCodificado) {
        $decodificado = self::decodificar($codigoCodificado);
        return $decodificado['cantidad'];
    }
    
    /**
     * Valida si un código codificado tiene el formato correcto
     * @param string $codigoCodificado Código a validar
     * @return bool True si es válido, false si no
     */
    public static function esValido($codigoCodificado) {
        try {
            if (strlen($codigoCodificado) !== 8) {
                return false;
            }
            
            // Verificar que los 3 caracteres de cantidad sean numéricos
            $parteCantidad = substr($codigoCodificado, 4, 3);
            if (!is_numeric($parteCantidad)) {
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Genera múltiples códigos para una lista de productos con cantidades
     * @param array $productos Array de arrays con 'codigo' y 'cantidad'
     * @param string $separador Caracter separador común
     * @return array Array de códigos codificados
     */
    public static function codificarMultiples($productos, $separador = '|') {
        $codigos = [];
        
        foreach ($productos as $producto) {
            if (!isset($producto['codigo']) || !isset($producto['cantidad'])) {
                throw new InvalidArgumentException("Cada producto debe tener 'codigo' y 'cantidad'");
            }
            
            $codigos[] = self::codificar($producto['codigo'], $producto['cantidad'], $separador);
        }
        
        return $codigos;
    }
    
    /**
     * Decodifica múltiples códigos
     * @param array $codigos Array de códigos codificados
     * @return array Array de productos decodificados
     */
    public static function decodificarMultiples($codigos) {
        $productos = [];
        
        foreach ($codigos as $codigo) {
            $productos[] = self::decodificar($codigo);
        }
        
        return $productos;
    }
    
    /**
     * Calcula el total de productos de una lista de códigos
     * @param array $codigos Array de códigos codificados
     * @return int Total de productos
     */
    public static function calcularTotalProductos($codigos) {
        $total = 0;
        
        foreach ($codigos as $codigo) {
            $total += self::obtenerCantidad($codigo);
        }
        
        return $total;
    }
    
    /**
     * Agrupa códigos por producto
     * @param array $codigos Array de códigos codificados
     * @return array Array agrupado por código de producto con cantidad total
     */
    public static function agruparPorProducto($codigos) {
        $agrupados = [];
        
        foreach ($codigos as $codigo) {
            $decodificado = self::decodificar($codigo);
            $codigoProducto = $decodificado['codigo_producto'];
            
            if (!isset($agrupados[$codigoProducto])) {
                $agrupados[$codigoProducto] = [
                    'codigo_producto' => $codigoProducto,
                    'cantidad_total' => 0,
                    'codigos_originales' => []
                ];
            }
            
            $agrupados[$codigoProducto]['cantidad_total'] += $decodificado['cantidad'];
            $agrupados[$codigoProducto]['codigos_originales'][] = $codigo;
        }
        
        return $agrupados;
    }
}

/**
 * Ejemplo de uso:
 * 
 * // Codificar un producto
 * $codigo = ProductoCodigoHelper::codificar('A001', 15, '|');
 * echo $codigo; // A001015|
 * 
 * // Decodificar
 * $decodificado = ProductoCodigoHelper::decodificar('A001015|');
 * // $decodificado = ['codigo_producto' => 'A001', 'cantidad' => 15, 'separador' => '|']
 * 
 * // Validar
 * $esValido = ProductoCodigoHelper::esValido('A001015|'); // true
 * 
 * // Codificar múltiples
 * $productos = [
 *     ['codigo' => 'A001', 'cantidad' => 15],
 *     ['codigo' => 'B002', 'cantidad' => 3],
 *     ['codigo' => 'C003', 'cantidad' => 8]
 * ];
 * $codigos = ProductoCodigoHelper::codificarMultiples($productos);
 * // ['A001015|', 'B002003|', 'C003008|']
 */
?>