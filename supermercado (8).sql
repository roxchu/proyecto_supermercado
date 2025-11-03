-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-11-2025 a las 19:58:41
-- Versión del servidor: 10.4.24-MariaDB
-- Versión de PHP: 8.1.6

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
-- Estructura de tabla para la tabla `carrito`
--

CREATE TABLE `carrito` (
  `id_carrito` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `Precio_Unitario_Momento` decimal(10,2) NOT NULL,
  `Cantidad` int(11) NOT NULL DEFAULT 1,
  `Total` decimal(10,2) GENERATED ALWAYS AS (`Cantidad` * `Precio_Unitario_Momento`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `id_categoria` int(11) NOT NULL,
  `Nombre_Categoria` text COLLATE utf8mb4_spanish2_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`id_categoria`, `Nombre_Categoria`) VALUES
(1, 'Frutas y Verduras'),
(2, 'Carnes y Pescados'),
(3, 'Lácteos y Huevos'),
(4, 'Panadería'),
(5, 'Bebidas'),
(6, 'Despensa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_carrito`
--

CREATE TABLE `detalle_carrito` (
  `id_detalle_carrito` int(11) NOT NULL,
  `id_carrito` int(11) NOT NULL,
  `id_direccion` int(11) NOT NULL,
  `Fecha_Agregado` datetime NOT NULL DEFAULT current_timestamp(),
  `Estado` varchar(200) COLLATE utf8mb4_spanish2_ci NOT NULL DEFAULT 'Pendiente',
  `Costo_Envio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Total_Final` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `detalle_carrito`
--

INSERT INTO `detalle_carrito` (`id_detalle_carrito`, `id_carrito`, `id_direccion`, `Fecha_Agregado`, `Estado`, `Costo_Envio`, `Total_Final`) VALUES
(1, 0, 0, '2025-10-26 18:19:26', 'Pendiente', '0.00', '14500.00'),
(2, 0, 0, '2025-10-26 19:19:50', 'Pendiente', '0.00', '0.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id_detalle_venta` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `Cantidad` int(11) NOT NULL CHECK (`Cantidad` > 0),
  `Precio_Unitario_Venta` decimal(10,2) NOT NULL,
  `IVA_Aplicado` decimal(5,2) NOT NULL DEFAULT 21.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direcciones`
--

CREATE TABLE `direcciones` (
  `id_direccion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre_direccion` varchar(200) COLLATE utf8mb4_spanish2_ci NOT NULL COMMENT 'Ej: Casa, Trabajo',
  `calle_numero` varchar(255) COLLATE utf8mb4_spanish2_ci NOT NULL COMMENT 'Calle y número juntos',
  `piso_depto` varchar(50) COLLATE utf8mb4_spanish2_ci DEFAULT NULL COMMENT 'Opcional',
  `Ciudad` text COLLATE utf8mb4_spanish2_ci NOT NULL,
  `Provincia` text COLLATE utf8mb4_spanish2_ci NOT NULL,
  `Codigo_postal` varchar(20) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `Referencia` varchar(200) COLLATE utf8mb4_spanish2_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `id_empleado` int(11) NOT NULL,
  `Fecha_contratacion` date NOT NULL,
  `Cargo` varchar(50) COLLATE utf8mb4_spanish2_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `id_producto` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `Nombre_Producto` varchar(200) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `Descripcion` varchar(500) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `Stock` int(11) NOT NULL DEFAULT 0,
  `precio_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio_anterior` decimal(10,2) DEFAULT NULL,
  `es_destacado` tinyint(1) DEFAULT 0,
  `etiqueta_especial` varchar(50) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `descuento_texto` varchar(100) COLLATE utf8mb4_spanish2_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`id_producto`, `id_categoria`, `Nombre_Producto`, `Descripcion`, `Stock`, `precio_actual`, `precio_anterior`, `es_destacado`, `etiqueta_especial`, `descuento_texto`) VALUES
(1, 1, 'Manzanas Rojas Premium', 'Manzanas rojas dulces y crujientes, importadas. Ideales para comer solas o en ensaladas.', 150, '2500.00', '3200.00', 1, 'EXCLUSIVO ONLINE', '22% OFF'),
(2, 2, 'Pollo Entero Fresco', 'Pollo entero de granja, sin menudos. Peso aproximado 2kg. Perfecto para asar.', 45, '8900.00', '10500.00', 1, NULL, '15% OFF'),
(3, 3, 'Leche Entera La Serenísima 1L', 'Leche entera UAT fortificada con vitaminas A y D. Larga vida.', 200, '1250.00', NULL, 1, 'LARGA VIDA', NULL),
(4, 5, 'Coca Cola Sabor Original 2.25L', 'Gaseosa Coca Cola sabor original en botella retornable de 2.25 litros.', 80, '1850.00', '2100.00', 1, NULL, '12% OFF'),
(5, 6, 'Arroz Integral Gallo Oro 1kg', 'Arroz integral de grano largo tipo 00000. No se pasa ni se pega.', 120, '1680.00', NULL, 1, NULL, NULL),
(6, 4, 'Pan Francés x6 unidades', 'Pan francés recién horneado del día, crocante por fuera, tierno por dentro.', 60, '2200.00', '2800.00', 0, 'OFERTA DEL DÍA', '21% OFF'),
(7, 1, 'Tomates Perita x 1kg', 'Tomates perita frescos ideales para salsas y ensaladas. Aproximadamente 6-8 tomates por kg.', 95, '1500.00', '1800.00', 0, NULL, '17% OFF'),
(8, 2, 'Carne Picada Especial 500g', 'Carne picada especial con bajo contenido graso. Ideal para hamburguesas o salsas.', 35, '4200.00', NULL, 0, NULL, NULL),
(9, 3, 'Yogur Entero Sancor Frutilla Pack x12', 'Pack económico de 12 yogures enteros sabor frutilla Sancor.', 0, '3600.00', '4200.00', 0, NULL, '14% OFF'),
(10, 5, 'Agua Mineral Villavicencio 2L', 'Agua mineral sin gas botella 2 litros', 150, '850.00', NULL, 1, 'LARGA VIDA', NULL),
(11, 6, 'Fideos Matarazzo 500g', 'Fideos secos tirabuzón de sémola', 180, '980.00', '1200.00', 1, NULL, '18% OFF'),
(12, 4, 'Medialunas x12 unidades', 'Medialunas de manteca recién horneadas', 40, '3200.00', NULL, 1, 'EXCLUSIVO ONLINE', NULL),
(13, 1, 'Bananas x1kg', 'Bananas frescas de Ecuador', 300, '1200.00', NULL, 0, NULL, NULL),
(14, 1, 'Lechuga Criolla', 'Lechuga criolla fresca', 80, '900.00', NULL, 0, NULL, NULL),
(15, 2, 'Milanesas de Pollo x6', 'Milanesas de pollo empanadas pack x6', 25, '5600.00', NULL, 0, NULL, NULL),
(16, 3, 'Queso Cremoso Mendicrim', 'Queso cremoso untable 300g', 65, '2800.00', NULL, 0, NULL, NULL),
(17, 5, 'Jugo Naranja Baggio 1L', 'Jugo de naranja con pulpa', 95, '1450.00', NULL, 0, NULL, NULL),
(18, 6, 'Aceite Girasol Cocinero 900ml', 'Aceite de girasol puro', 110, '2300.00', NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_imagenes`
--

CREATE TABLE `producto_imagenes` (
  `id_imagen` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `url_imagen` varchar(500) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `orden` int(11) DEFAULT 0 COMMENT 'Para ordenar las imágenes, 0 o 1 para la principal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `producto_imagenes`
--

INSERT INTO `producto_imagenes` (`id_imagen`, `id_producto`, `url_imagen`, `orden`) VALUES
(0, 1, 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=400', 1),
(0, 1, 'https://images.unsplash.com/photo-1570913149827-d2ac84ab3f9a?w=400', 2),
(0, 1, 'https://images.unsplash.com/photo-1610399313110-89e4c198e3b0?w=400', 3),
(0, 2, 'https://images.unsplash.com/photo-1587593810167-a84920ea0781?w=400', 1),
(0, 2, 'https://images.unsplash.com/photo-1626071499700-1de1c474b789?w=400', 2),
(0, 4, 'https://images.unsplash.com/photo-1554866585-cd94860890b7?w=400', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_opiniones`
--

CREATE TABLE `producto_opiniones` (
  `id__opinion` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `Calificacion` tinyint(1) NOT NULL CHECK (`Calificacion` >= 1 and `Calificacion` <= 5),
  `Comentario` text COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `Fecha_Opinion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `producto_opiniones`
--

INSERT INTO `producto_opiniones` (`id__opinion`, `id_producto`, `id_usuario`, `Calificacion`, `Comentario`, `Fecha_Opinion`) VALUES
(0, 1, 6, 5, '¡Excelentes manzanas! Muy frescas y crujientes. Llegaron rápido.', '2025-10-24 00:00:48'),
(0, 1, 7, 4, 'Buenas manzanas, aunque un poco caras para mi gusto.', '2025-10-24 00:00:48'),
(0, 1, 8, 5, 'Las mejores que probé en mucho tiempo.', '2025-10-24 00:00:48'),
(0, 2, 6, 5, 'El pollo estaba perfecto para el horno. Buen tamaño y sabor.', '2025-10-24 00:00:48'),
(0, 4, 7, 3, 'Es Coca Cola, no hay mucho que decir. Llegó bien fría.', '2025-10-24 00:00:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `rol_descripcion` varchar(200) COLLATE utf8mb4_spanish2_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id_rol`, `nombre_rol`, `rol_descripcion`) VALUES
(1, 'admin', 'Superusuario'),
(2, 'empleado', 'Empleado de Supermercado'),
(3, 'client', 'Cliente Registrado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `DNI` varchar(15) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `id_rol` int(11) NOT NULL,
  `nombre_usuario` varchar(200) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `correo` varchar(200) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `contrasena` varchar(200) COLLATE utf8mb4_spanish2_ci NOT NULL COMMENT 'Guardar HASH, no texto plano'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `DNI`, `id_rol`, `nombre_usuario`, `correo`, `contrasena`) VALUES
(0, '4321', 3, '4321', '4321@gmail.com', ''),
(1, '49.553.570', 3, 'Cliente 49.553.570', '49.553.570@temp.com', 'sin_pass_hashed'),
(2, '11111111', 1, 'Admin Supremo', 'admin@super.com', 'mi_pass_segura'),
(3, '22222222', 2, 'Empleado General', 'empleado@super.com', 'otra_pass_segura'),
(5, '12763516', 3, 'Cliente 12763516', '12763516@temp.com', 'sin_pass_hashed'),
(6, '99999999', 3, 'Usuario De Prueba', 'prueba@test.com', ''),
(7, '098765', 3, 'ahshfhahs', 'asftadt@gmail.com', ''),
(8, '1213421', 3, 'asfanhgdha', 'hagsfdah@asghagd', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta`
--

CREATE TABLE `venta` (
  `id_venta` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `tipo_venta` enum('virtual','presencial') COLLATE utf8mb4_spanish2_ci NOT NULL,
  `fecha_venta` datetime NOT NULL DEFAULT current_timestamp(),
  `Total_Venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_direccion` int(11) DEFAULT NULL,
  `Estado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD PRIMARY KEY (`id_carrito`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `Id_Producto` (`id_producto`);

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `detalle_carrito`
--
ALTER TABLE `detalle_carrito`
  ADD PRIMARY KEY (`id_detalle_carrito`),
  ADD UNIQUE KEY `Id_Detalle_Carrito_3` (`id_detalle_carrito`),
  ADD KEY `FK_Carrito_Direccion` (`id_direccion`),
  ADD KEY `Id_Detalle_Carrito` (`id_detalle_carrito`),
  ADD KEY `Id_Carrito` (`id_carrito`),
  ADD KEY `Id_Detalle_Carrito_2` (`id_detalle_carrito`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id_detalle_venta`),
  ADD KEY `FK_DetalleVenta_Venta` (`id_venta`),
  ADD KEY `FK_DetalleVenta_Producto` (`id_producto`);

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
  ADD PRIMARY KEY (`id_empleado`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `FK_Producto_Categoria` (`id_categoria`);

--
-- Indices de la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  ADD KEY `Id_Producto` (`id_producto`);

--
-- Indices de la tabla `producto_opiniones`
--
ALTER TABLE `producto_opiniones`
  ADD KEY `Id_Producto` (`id_producto`),
  ADD KEY `id_usuario` (`id_usuario`);

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
-- Indices de la tabla `venta`
--
ALTER TABLE `venta`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `FK_Venta_Usuario` (`id_usuario`),
  ADD KEY `FK_Venta_Empleado` (`id_empleado`),
  ADD KEY `FK_Venta_Direccion` (`id_direccion`),
  ADD KEY `id_direccion_envio` (`id_direccion`),
  ADD KEY `id_direccion` (`id_direccion`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `carrito`
--
ALTER TABLE `carrito`
  MODIFY `id_carrito` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `detalle_carrito`
--
ALTER TABLE `detalle_carrito`
  MODIFY `id_detalle_carrito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id_detalle_venta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  MODIFY `id_direccion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD CONSTRAINT `FK_Carrito_Producto` FOREIGN KEY (`Id_Producto`) REFERENCES `producto` (`Id_Producto`),
  ADD CONSTRAINT `FK_Carrito_Usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `FK_DetalleVenta_Producto` FOREIGN KEY (`Id_Producto`) REFERENCES `producto` (`Id_Producto`),
  ADD CONSTRAINT `FK_DetalleVenta_Venta` FOREIGN KEY (`Id_Venta`) REFERENCES `venta` (`Id_Venta`);

--
-- Filtros para la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD CONSTRAINT `FK_Direccion_Usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  ADD CONSTRAINT `producto_imagenes_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`Id_Producto`);

--
-- Filtros para la tabla `producto_opiniones`
--
ALTER TABLE `producto_opiniones`
  ADD CONSTRAINT `producto_opiniones_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`Id_Producto`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
