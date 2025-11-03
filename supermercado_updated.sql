-- phpMyAdmin SQL Dump
-- SUPERMERCADO DATABASE - UPDATED VERSION
-- Fecha de actualización: 03-11-2025
-- Cambios aplicados:
-- 1. Agregado rol 'dueño' con mayor jerarquía que admin
-- 2. Eliminada tabla 'cliente' - se usa solo 'usuario'
-- 3. Cambiados id_usuario por id_empleado en tabla empleado
-- 4. Eliminada columna imagen_url de producto (se usa tabla producto_imagenes)
-- 5. Unificadas tablas venta y carrito con campo tipo_venta
-- 6. Implementado formato de código de producto varchar(8)
-- 7. Agregado sistema de reportes de ventas

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `supermercado`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `Id_Categoria` int(11) NOT NULL,
  `Nombre_Categoria` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`Id_Categoria`, `Nombre_Categoria`) VALUES
(1, 'Frutas y Verduras'),
(2, 'Carnes y Pescados'),
(3, 'Lácteos y Huevos'),
(4, 'Panadería'),
(5, 'Bebidas'),
(6, 'Despensa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direcciones`
--

CREATE TABLE `direcciones` (
  `id_direccion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL COMMENT 'Referencias directamente a usuario',
  `nombre_direccion` varchar(200) NOT NULL COMMENT 'Ej: Casa, Trabajo',
  `calle_numero` varchar(255) NOT NULL COMMENT 'Calle y número juntos',
  `piso_depto` varchar(50) DEFAULT NULL COMMENT 'Opcional',
  `Ciudad` text NOT NULL,
  `Provincia` text NOT NULL,
  `Codigo_postal` varchar(20) NOT NULL,
  `Referencia` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `id_empleado` int(11) NOT NULL COMMENT 'Ahora es independiente, no referencia usuario',
  `id_usuario` int(11) NOT NULL COMMENT 'Referencia al usuario que es empleado',
  `Fecha_contratacion` date NOT NULL,
  `Cargo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `Id_Producto` int(11) NOT NULL,
  `Id_Categoria` int(11) NOT NULL,
  `Nombre_Producto` varchar(200) NOT NULL,
  `Descripcion` varchar(500) NOT NULL,
  `Stock` int(11) NOT NULL DEFAULT 0,
  `codigo_producto` varchar(4) NOT NULL COMMENT 'Código único de 4 caracteres para el producto',
  `precio_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio_anterior` decimal(10,2) DEFAULT NULL,
  `es_destacado` tinyint(1) DEFAULT 0,
  `etiqueta_especial` varchar(50) DEFAULT NULL,
  `descuento_texto` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`Id_Producto`, `Id_Categoria`, `Nombre_Producto`, `Descripcion`, `Stock`, `codigo_producto`, `precio_actual`, `precio_anterior`, `es_destacado`, `etiqueta_especial`, `descuento_texto`) VALUES
(1, 1, 'Manzanas Rojas Premium', 'Manzanas rojas dulces y crujientes, importadas. Ideales para comer solas o en ensaladas.', 150, 'A001', 2500.00, 3200.00, 1, 'EXCLUSIVO ONLINE', '22% OFF'),
(2, 2, 'Pollo Entero Fresco', 'Pollo entero de granja, sin menudos. Peso aproximado 2kg. Perfecto para asar.', 45, 'B001', 8900.00, 10500.00, 1, NULL, '15% OFF'),
(3, 3, 'Leche Entera La Serenísima 1L', 'Leche entera UAT fortificada con vitaminas A y D. Larga vida.', 200, 'C001', 1250.00, NULL, 1, 'LARGA VIDA', NULL),
(4, 5, 'Coca Cola Sabor Original 2.25L', 'Gaseosa Coca Cola sabor original en botella retornable de 2.25 litros.', 80, 'E001', 1850.00, 2100.00, 1, NULL, '12% OFF'),
(5, 6, 'Arroz Integral Gallo Oro 1kg', 'Arroz integral de grano largo tipo 00000. No se pasa ni se pega.', 120, 'F001', 1680.00, NULL, 1, NULL, NULL),
(6, 4, 'Pan Francés x6 unidades', 'Pan francés recién horneado del día, crocante por fuera, tierno por dentro.', 60, 'D001', 2200.00, 2800.00, 0, 'OFERTA DEL DÍA', '21% OFF'),
(7, 1, 'Tomates Perita x 1kg', 'Tomates perita frescos ideales para salsas y ensaladas. Aproximadamente 6-8 tomates por kg.', 95, 'A002', 1500.00, 1800.00, 0, NULL, '17% OFF'),
(8, 2, 'Carne Picada Especial 500g', 'Carne picada especial con bajo contenido graso. Ideal para hamburguesas o salsas.', 35, 'B002', 4200.00, NULL, 0, NULL, NULL),
(9, 3, 'Yogur Entero Sancor Frutilla Pack x12', 'Pack económico de 12 yogures enteros sabor frutilla Sancor.', 0, 'C002', 3600.00, 4200.00, 0, NULL, '14% OFF'),
(10, 5, 'Agua Mineral Villavicencio 2L', 'Agua mineral sin gas botella 2 litros', 150, 'E002', 850.00, NULL, 1, 'LARGA VIDA', NULL),
(11, 6, 'Fideos Matarazzo 500g', 'Fideos secos tirabuzón de sémola', 180, 'F002', 980.00, 1200.00, 1, NULL, '18% OFF'),
(12, 4, 'Medialunas x12 unidades', 'Medialunas de manteca recién horneadas', 40, 'D002', 3200.00, NULL, 1, 'EXCLUSIVO ONLINE', NULL),
(13, 1, 'Bananas x1kg', 'Bananas frescas de Ecuador', 300, 'A003', 1200.00, NULL, 0, NULL, NULL),
(14, 1, 'Lechuga Criolla', 'Lechuga criolla fresca', 80, 'A004', 900.00, NULL, 0, NULL, NULL),
(15, 2, 'Milanesas de Pollo x6', 'Milanesas de pollo empanadas pack x6', 25, 'B003', 5600.00, NULL, 0, NULL, NULL),
(16, 3, 'Queso Cremoso Mendicrim', 'Queso cremoso untable 300g', 65, 'C003', 2800.00, NULL, 0, NULL, NULL),
(17, 5, 'Jugo Naranja Baggio 1L', 'Jugo de naranja con pulpa', 95, 'E003', 1450.00, NULL, 0, NULL, NULL),
(18, 6, 'Aceite Girasol Cocinero 900ml', 'Aceite de girasol puro', 110, 'F003', 2300.00, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_imagenes`
--

CREATE TABLE `producto_imagenes` (
  `Id_Imagen` int(11) NOT NULL,
  `Id_Producto` int(11) NOT NULL,
  `url_imagen` varchar(500) NOT NULL,
  `orden` int(11) DEFAULT 0 COMMENT 'Para ordenar las imágenes, 1 para la principal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `producto_imagenes`
--

INSERT INTO `producto_imagenes` (`Id_Imagen`, `Id_Producto`, `url_imagen`, `orden`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=400', 1),
(2, 1, 'https://images.unsplash.com/photo-1570913149827-d2ac84ab3f9a?w=400', 2),
(3, 1, 'https://images.unsplash.com/photo-1610399313110-89e4c198e3b0?w=400', 3),
(4, 2, 'https://images.unsplash.com/photo-1587593810167-a84920ea0781?w=400', 1),
(5, 2, 'https://images.unsplash.com/photo-1626071499700-1de1c474b789?w=400', 2),
(6, 4, 'https://images.unsplash.com/photo-1554866585-cd94860890b7?w=400', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_opiniones`
--

CREATE TABLE `producto_opiniones` (
  `Id_Opinion` int(11) NOT NULL,
  `Id_Producto` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `Calificacion` tinyint(1) NOT NULL CHECK (`Calificacion` >= 1 and `Calificacion` <= 5),
  `Comentario` text DEFAULT NULL,
  `Fecha_Opinion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `producto_opiniones`
--

INSERT INTO `producto_opiniones` (`Id_Opinion`, `Id_Producto`, `id_usuario`, `Calificacion`, `Comentario`, `Fecha_Opinion`) VALUES
(1, 1, 6, 5, '¡Excelentes manzanas! Muy frescas y crujientes. Llegaron rápido.', '2025-10-24 00:00:48'),
(2, 1, 7, 4, 'Buenas manzanas, aunque un poco caras para mi gusto.', '2025-10-24 00:00:48'),
(3, 1, 8, 5, 'Las mejores que probé en mucho tiempo.', '2025-10-24 00:00:48'),
(4, 2, 6, 5, 'El pollo estaba perfecto para el horno. Buen tamaño y sabor.', '2025-10-24 00:00:48'),
(5, 4, 7, 3, 'Es Coca Cola, no hay mucho que decir. Llegó bien fría.', '2025-10-24 00:00:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL,
  `rol_descripcion` varchar(200) DEFAULT NULL,
  `nivel_jerarquia` int(11) NOT NULL DEFAULT 3 COMMENT '1=dueño, 2=admin, 3=empleado, 4=cliente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id_rol`, `nombre_rol`, `rol_descripcion`, `nivel_jerarquia`) VALUES
(1, 'dueño', 'Propietario de la empresa - Máximo nivel', 1),
(2, 'admin', 'Administrador del sistema', 2),
(3, 'empleado', 'Empleado de Supermercado', 3),
(4, 'cliente', 'Cliente Registrado', 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `DNI` varchar(15) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `nombre_usuario` varchar(200) NOT NULL,
  `correo` varchar(200) NOT NULL,
  `contrasena` varchar(200) NOT NULL COMMENT 'Guardar HASH, no texto plano'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `DNI`, `id_rol`, `nombre_usuario`, `correo`, `contrasena`) VALUES
(1, '12345678', 1, 'Dueño Principal', 'dueno@supermercado.com', 'hash_password_dueno'),
(2, '11111111', 2, 'Admin Supremo', 'admin@super.com', 'mi_pass_segura'),
(3, '22222222', 3, 'Empleado General', 'empleado@super.com', 'otra_pass_segura'),
(4, '49.553.570', 4, 'Cliente 49.553.570', '49.553.570@temp.com', 'sin_pass_hashed'),
(5, '12763516', 4, 'Cliente 12763516', '12763516@temp.com', 'sin_pass_hashed'),
(6, '99999999', 4, 'Usuario De Prueba', 'prueba@test.com', ''),
(7, '098765', 4, 'ahshfhahs', 'asftadt@gmail.com', ''),
(8, '1213421', 4, 'asfanhgdha', 'hagsfdah@asghagd', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta_unificada`
-- Tabla unificada que reemplaza tanto 'venta' como 'carrito'
--

CREATE TABLE `venta_unificada` (
  `id_venta` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL COMMENT 'Cliente que realiza la compra',
  `id_empleado` int(11) DEFAULT NULL COMMENT 'Empleado que procesó la venta (solo ventas presenciales)',
  `tipo_venta` enum('presencial','virtual') NOT NULL COMMENT 'Tipo de venta: presencial o virtual',
  `fecha_venta` datetime NOT NULL DEFAULT current_timestamp(),
  `Total_Venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Id_Direccion_Envio` int(11) DEFAULT NULL COMMENT 'Solo para ventas virtuales',
  `Estado` varchar(50) NOT NULL DEFAULT 'Pendiente' COMMENT 'Pendiente, Procesando, Enviado, Entregado, Cancelado',
  `Costo_Envio` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
-- Para ventas presenciales
--

CREATE TABLE `detalle_venta` (
  `id_detalle_venta` int(11) NOT NULL,
  `Id_Venta` int(11) NOT NULL,
  `productos_codificados` varchar(8) NOT NULL COMMENT 'Formato: ABCD123X (4 chars código + 3 chars cantidad + 1 separador)',
  `Precio_Unitario_Venta` decimal(10,2) NOT NULL,
  `IVA_Aplicado` decimal(5,2) NOT NULL DEFAULT 21.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_carrito`
-- Para ventas virtuales (carrito)
--

CREATE TABLE `detalle_carrito` (
  `Id_Detalle_Carrito` int(11) NOT NULL,
  `Id_Venta` int(11) NOT NULL COMMENT 'Referencia a venta_unificada',
  `Id_Producto` int(11) NOT NULL,
  `Precio_Unitario_Momento` decimal(10,2) NOT NULL,
  `Cantidad` int(11) NOT NULL DEFAULT 1,
  `Total` decimal(10,2) GENERATED ALWAYS AS (`Cantidad` * `Precio_Unitario_Momento`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes_ventas`
-- Para almacenar estadísticas de ventas precalculadas
--

CREATE TABLE `reportes_ventas` (
  `id_reporte` int(11) NOT NULL,
  `periodo` enum('dia','semana','mes','año') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `total_ventas` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cantidad_transacciones` int(11) NOT NULL DEFAULT 0,
  `ventas_presenciales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ventas_virtuales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `fecha_generacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`Id_Categoria`);

--
-- Indices de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD PRIMARY KEY (`id_direccion`),
  ADD KEY `FK_Direccion_Usuario` (`id_usuario`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`id_empleado`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`Id_Producto`),
  ADD UNIQUE KEY `codigo_producto` (`codigo_producto`),
  ADD KEY `FK_Producto_Categoria` (`Id_Categoria`);

--
-- Indices de la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  ADD PRIMARY KEY (`Id_Imagen`),
  ADD KEY `FK_Imagen_Producto` (`Id_Producto`);

--
-- Indices de la tabla `producto_opiniones`
--
ALTER TABLE `producto_opiniones`
  ADD PRIMARY KEY (`Id_Opinion`),
  ADD KEY `FK_Opinion_Producto` (`Id_Producto`),
  ADD KEY `FK_Opinion_Usuario` (`id_usuario`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre_rol` (`nombre_rol`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `DNI` (`DNI`),
  ADD KEY `FK_Usuario_Rol` (`id_rol`);

--
-- Indices de la tabla `venta_unificada`
--
ALTER TABLE `venta_unificada`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `FK_VentaUnif_Usuario` (`id_usuario`),
  ADD KEY `FK_VentaUnif_Empleado` (`id_empleado`),
  ADD KEY `FK_VentaUnif_Direccion` (`Id_Direccion_Envio`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id_detalle_venta`),
  ADD KEY `FK_DetalleVenta_VentaUnif` (`Id_Venta`);

--
-- Indices de la tabla `detalle_carrito`
--
ALTER TABLE `detalle_carrito`
  ADD PRIMARY KEY (`Id_Detalle_Carrito`),
  ADD KEY `FK_DetalleCarrito_VentaUnif` (`Id_Venta`),
  ADD KEY `FK_DetalleCarrito_Producto` (`Id_Producto`);

--
-- Indices de la tabla `reportes_ventas`
--
ALTER TABLE `reportes_ventas`
  ADD PRIMARY KEY (`id_reporte`),
  ADD KEY `idx_periodo_fecha` (`periodo`, `fecha_inicio`, `fecha_fin`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `Id_Categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  MODIFY `id_direccion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `id_empleado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `Id_Producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  MODIFY `Id_Imagen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `producto_opiniones`
--
ALTER TABLE `producto_opiniones`
  MODIFY `Id_Opinion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `venta_unificada`
--
ALTER TABLE `venta_unificada`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id_detalle_venta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_carrito`
--
ALTER TABLE `detalle_carrito`
  MODIFY `Id_Detalle_Carrito` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reportes_ventas`
--
ALTER TABLE `reportes_ventas`
  MODIFY `id_reporte` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD CONSTRAINT `FK_Direccion_Usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD CONSTRAINT `FK_Empleado_Usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `FK_Producto_Categoria` FOREIGN KEY (`Id_Categoria`) REFERENCES `categoria` (`Id_Categoria`) ON DELETE RESTRICT;

--
-- Filtros para la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  ADD CONSTRAINT `FK_Imagen_Producto` FOREIGN KEY (`Id_Producto`) REFERENCES `producto` (`Id_Producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `producto_opiniones`
--
ALTER TABLE `producto_opiniones`
  ADD CONSTRAINT `FK_Opinion_Producto` FOREIGN KEY (`Id_Producto`) REFERENCES `producto` (`Id_Producto`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_Opinion_Usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `FK_Usuario_Rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON DELETE RESTRICT;

--
-- Filtros para la tabla `venta_unificada`
--
ALTER TABLE `venta_unificada`
  ADD CONSTRAINT `FK_VentaUnif_Usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE RESTRICT,
  ADD CONSTRAINT `FK_VentaUnif_Empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_VentaUnif_Direccion` FOREIGN KEY (`Id_Direccion_Envio`) REFERENCES `direcciones` (`id_direccion`) ON DELETE SET NULL;

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `FK_DetalleVenta_VentaUnif` FOREIGN KEY (`Id_Venta`) REFERENCES `venta_unificada` (`id_venta`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_carrito`
--
ALTER TABLE `detalle_carrito`
  ADD CONSTRAINT `FK_DetalleCarrito_VentaUnif` FOREIGN KEY (`Id_Venta`) REFERENCES `venta_unificada` (`id_venta`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_DetalleCarrito_Producto` FOREIGN KEY (`Id_Producto`) REFERENCES `producto` (`Id_Producto`) ON DELETE RESTRICT;

-- --------------------------------------------------------

--
-- Funciones auxiliares para el manejo del código de producto
--

DELIMITER $$

CREATE FUNCTION `decodificar_producto_codigo`(codigo VARCHAR(8)) 
RETURNS JSON
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE codigo_producto VARCHAR(4);
    DECLARE cantidad INT;
    DECLARE separador CHAR(1);
    
    SET codigo_producto = LEFT(codigo, 4);
    SET cantidad = CAST(SUBSTRING(codigo, 5, 3) AS UNSIGNED);
    SET separador = RIGHT(codigo, 1);
    
    RETURN JSON_OBJECT(
        'codigo_producto', codigo_producto,
        'cantidad', cantidad,
        'separador', separador
    );
END$$

CREATE FUNCTION `codificar_producto`(codigo_producto VARCHAR(4), cantidad INT, separador CHAR(1)) 
RETURNS VARCHAR(8)
NO SQL
DETERMINISTIC
BEGIN
    RETURN CONCAT(
        codigo_producto,
        LPAD(cantidad, 3, '0'),
        separador
    );
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Procedimientos almacenados para reportes
--

DELIMITER $$

CREATE PROCEDURE `generar_reporte_ventas`(
    IN periodo_tipo ENUM('dia','semana','mes','año'),
    IN fecha_desde DATE,
    IN fecha_hasta DATE
)
BEGIN
    DECLARE total_ventas_calc DECIMAL(12,2) DEFAULT 0.00;
    DECLARE cantidad_transacciones_calc INT DEFAULT 0;
    DECLARE ventas_presenciales_calc DECIMAL(12,2) DEFAULT 0.00;
    DECLARE ventas_virtuales_calc DECIMAL(12,2) DEFAULT 0.00;
    
    -- Calcular totales
    SELECT 
        COALESCE(SUM(Total_Venta), 0),
        COUNT(*),
        COALESCE(SUM(CASE WHEN tipo_venta = 'presencial' THEN Total_Venta ELSE 0 END), 0),
        COALESCE(SUM(CASE WHEN tipo_venta = 'virtual' THEN Total_Venta ELSE 0 END), 0)
    INTO total_ventas_calc, cantidad_transacciones_calc, ventas_presenciales_calc, ventas_virtuales_calc
    FROM venta_unificada 
    WHERE DATE(fecha_venta) BETWEEN fecha_desde AND fecha_hasta
    AND Estado = 'Entregado';
    
    -- Insertar o actualizar reporte
    INSERT INTO reportes_ventas (
        periodo, fecha_inicio, fecha_fin, total_ventas, 
        cantidad_transacciones, ventas_presenciales, ventas_virtuales
    ) VALUES (
        periodo_tipo, fecha_desde, fecha_hasta, total_ventas_calc,
        cantidad_transacciones_calc, ventas_presenciales_calc, ventas_virtuales_calc
    ) ON DUPLICATE KEY UPDATE
        total_ventas = total_ventas_calc,
        cantidad_transacciones = cantidad_transacciones_calc,
        ventas_presenciales = ventas_presenciales_calc,
        ventas_virtuales = ventas_virtuales_calc,
        fecha_generacion = CURRENT_TIMESTAMP;
        
END$$

DELIMITER ;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;