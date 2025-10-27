-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-10-2025 a las 04:46:57
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

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
  `Id_Carrito` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `Id_Producto` int(11) NOT NULL,
  `Precio_Unitario_Momento` decimal(10,2) NOT NULL,
  `Cantidad` int(11) NOT NULL DEFAULT 1,
  `Total` decimal(10,2) GENERATED ALWAYS AS (`Cantidad` * `Precio_Unitario_Momento`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`id_cliente`) VALUES
(0),
(1),
(5),
(6),
(7),
(8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_carrito`
--

CREATE TABLE `detalle_carrito` (
  `Id_Detalle_Carrito` int(11) NOT NULL,
  `Id_Carrito` int(11) NOT NULL,
  `Id_Direccion` int(11) NOT NULL,
  `Fecha_Agregado` datetime NOT NULL DEFAULT current_timestamp(),
  `Estado` varchar(200) NOT NULL DEFAULT 'Pendiente',
  `Costo_Envio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Total_Final` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `detalle_carrito`
--

INSERT INTO `detalle_carrito` (`Id_Detalle_Carrito`, `Id_Carrito`, `Id_Direccion`, `Fecha_Agregado`, `Estado`, `Costo_Envio`, `Total_Final`) VALUES
(1, 0, 0, '2025-10-26 18:19:26', 'Pendiente', 0.00, 14500.00),
(2, 0, 0, '2025-10-26 19:19:50', 'Pendiente', 0.00, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id_detalle_venta` int(11) NOT NULL,
  `Id_Venta` int(11) NOT NULL,
  `Id_Producto` int(11) NOT NULL,
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
  `id_cliente` int(11) NOT NULL,
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
  `id_empleado` int(11) NOT NULL,
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
  `imagen_url` varchar(500) DEFAULT 'https://via.placeholder.com/250x160',
  `precio_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio_anterior` decimal(10,2) DEFAULT NULL,
  `es_destacado` tinyint(1) DEFAULT 0,
  `etiqueta_especial` varchar(50) DEFAULT NULL,
  `descuento_texto` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`Id_Producto`, `Id_Categoria`, `Nombre_Producto`, `Descripcion`, `Stock`, `imagen_url`, `precio_actual`, `precio_anterior`, `es_destacado`, `etiqueta_especial`, `descuento_texto`) VALUES
(1, 1, 'Manzanas Rojas Premium', 'Manzanas rojas dulces y crujientes, importadas. Ideales para comer solas o en ensaladas.', 150, 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=400', 2500.00, 3200.00, 1, 'EXCLUSIVO ONLINE', '22% OFF'),
(2, 2, 'Pollo Entero Fresco', 'Pollo entero de granja, sin menudos. Peso aproximado 2kg. Perfecto para asar.', 45, 'https://images.unsplash.com/photo-1587593810167-a84920ea0781?w=400', 8900.00, 10500.00, 1, NULL, '15% OFF'),
(3, 3, 'Leche Entera La Serenísima 1L', 'Leche entera UAT fortificada con vitaminas A y D. Larga vida.', 200, 'https://images.unsplash.com/photo-1563636619-e9143da7973b?w=400', 1250.00, NULL, 1, 'LARGA VIDA', NULL),
(4, 5, 'Coca Cola Sabor Original 2.25L', 'Gaseosa Coca Cola sabor original en botella retornable de 2.25 litros.', 80, 'https://images.unsplash.com/photo-1554866585-cd94860890b7?w=400', 1850.00, 2100.00, 1, NULL, '12% OFF'),
(5, 6, 'Arroz Integral Gallo Oro 1kg', 'Arroz integral de grano largo tipo 00000. No se pasa ni se pega.', 120, 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=400', 1680.00, NULL, 1, NULL, NULL),
(6, 4, 'Pan Francés x6 unidades', 'Pan francés recién horneado del día, crocante por fuera, tierno por dentro.', 60, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=400', 2200.00, 2800.00, 0, 'OFERTA DEL DÍA', '21% OFF'),
(7, 1, 'Tomates Perita x 1kg', 'Tomates perita frescos ideales para salsas y ensaladas. Aproximadamente 6-8 tomates por kg.', 95, 'https://images.unsplash.com/photo-1546470427-227a9a593cf4?w=400', 1500.00, 1800.00, 0, NULL, '17% OFF'),
(8, 2, 'Carne Picada Especial 500g', 'Carne picada especial con bajo contenido graso. Ideal para hamburguesas o salsas.', 35, 'https://images.unsplash.com/photo-1603048297172-c92544798d5a?w=400', 4200.00, NULL, 0, NULL, NULL),
(9, 3, 'Yogur Entero Sancor Frutilla Pack x12', 'Pack económico de 12 yogures enteros sabor frutilla Sancor.', 0, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=400', 3600.00, 4200.00, 0, NULL, '14% OFF'),
(10, 5, 'Agua Mineral Villavicencio 2L', 'Agua mineral sin gas botella 2 litros', 150, 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=400', 850.00, NULL, 1, 'LARGA VIDA', NULL),
(11, 6, 'Fideos Matarazzo 500g', 'Fideos secos tirabuzón de sémola', 180, 'https://images.unsplash.com/photo-1551462147-ff29053bfc14?w=400', 980.00, 1200.00, 1, NULL, '18% OFF'),
(12, 4, 'Medialunas x12 unidades', 'Medialunas de manteca recién horneadas', 40, 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=400', 3200.00, NULL, 1, 'EXCLUSIVO ONLINE', NULL),
(13, 1, 'Bananas x1kg', 'Bananas frescas de Ecuador', 300, 'https://images.unsplash.com/photo-1603833665858-e61d17a86224?w=400', 1200.00, NULL, 0, NULL, NULL),
(14, 1, 'Lechuga Criolla', 'Lechuga criolla fresca', 80, 'https://images.unsplash.com/photo-1622206151226-18ca2c9ab4a1?w=400', 900.00, NULL, 0, NULL, NULL),
(15, 2, 'Milanesas de Pollo x6', 'Milanesas de pollo empanadas pack x6', 25, 'https://images.unsplash.com/photo-1632778149955-e80f8ceca2e8?w=400', 5600.00, NULL, 0, NULL, NULL),
(16, 3, 'Queso Cremoso Mendicrim', 'Queso cremoso untable 300g', 65, 'https://images.unsplash.com/photo-1452195100486-9cc805987862?w=400', 2800.00, NULL, 0, NULL, NULL),
(17, 5, 'Jugo Naranja Baggio 1L', 'Jugo de naranja con pulpa', 95, 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=400', 1450.00, NULL, 0, NULL, NULL),
(18, 6, 'Aceite Girasol Cocinero 900ml', 'Aceite de girasol puro', 110, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=400', 2300.00, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_imagenes`
--

CREATE TABLE `producto_imagenes` (
  `Id_Imagen` int(11) NOT NULL,
  `Id_Producto` int(11) NOT NULL,
  `url_imagen` varchar(500) NOT NULL,
  `orden` int(11) DEFAULT 0 COMMENT 'Para ordenar las imágenes, 0 o 1 para la principal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `producto_imagenes`
--

INSERT INTO `producto_imagenes` (`Id_Imagen`, `Id_Producto`, `url_imagen`, `orden`) VALUES
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
  `nombre_rol` varchar(50) NOT NULL,
  `rol_descripcion` varchar(200) DEFAULT NULL
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
  `id_cliente` int(11) NOT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `fecha_venta` datetime NOT NULL DEFAULT current_timestamp(),
  `Total_Venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Id_Direccion_Envio` int(11) DEFAULT NULL,
  `Estado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD PRIMARY KEY (`Id_Carrito`);

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`Id_Categoria`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id_cliente`);

--
-- Indices de la tabla `detalle_carrito`
--
ALTER TABLE `detalle_carrito`
  ADD PRIMARY KEY (`Id_Detalle_Carrito`),
  ADD UNIQUE KEY `Id_Detalle_Carrito_3` (`Id_Detalle_Carrito`),
  ADD KEY `FK_Carrito_Direccion` (`Id_Direccion`),
  ADD KEY `Id_Detalle_Carrito` (`Id_Detalle_Carrito`),
  ADD KEY `Id_Carrito` (`Id_Carrito`),
  ADD KEY `Id_Detalle_Carrito_2` (`Id_Detalle_Carrito`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id_detalle_venta`),
  ADD KEY `FK_DetalleVenta_Venta` (`Id_Venta`),
  ADD KEY `FK_DetalleVenta_Producto` (`Id_Producto`);

--
-- Indices de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD PRIMARY KEY (`id_direccion`),
  ADD KEY `FK_Direccion_Cliente` (`id_cliente`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`id_empleado`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`Id_Producto`),
  ADD KEY `FK_Producto_Categoria` (`Id_Categoria`);

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
  ADD KEY `FK_Venta_Cliente` (`id_cliente`),
  ADD KEY `FK_Venta_Empleado` (`id_empleado`),
  ADD KEY `FK_Venta_Direccion` (`Id_Direccion_Envio`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `carrito`
--
ALTER TABLE `carrito`
  MODIFY `Id_Carrito` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `Id_Categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `detalle_carrito`
--
ALTER TABLE `detalle_carrito`
  MODIFY `Id_Detalle_Carrito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `Id_Producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
