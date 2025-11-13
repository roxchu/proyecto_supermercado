-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-11-2025 a las 05:39:05
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

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
  `precio_unitario_momento` decimal(10,2) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `total` decimal(10,2) GENERATED ALWAYS AS (`cantidad` * `precio_unitario_momento`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carrito`
--

INSERT INTO `carrito` (`id_carrito`, `id_usuario`, `id_producto`, `precio_unitario_momento`, `cantidad`) VALUES
(3, 3, 2, 8900.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `id_categoria` int(11) NOT NULL,
  `nombre_categoria` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`id_categoria`, `nombre_categoria`) VALUES
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
  `fecha_agregado` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` varchar(200) NOT NULL DEFAULT 'Pendiente',
  `costo_envio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_final` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id_detalle_venta` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL CHECK (`cantidad` > 0),
  `precio_unitario_venta` decimal(10,2) NOT NULL,
  `iva_aplicado` decimal(5,2) NOT NULL DEFAULT 21.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `detalle_venta`
--

INSERT INTO `detalle_venta` (`id_detalle_venta`, `id_venta`, `id_producto`, `cantidad`, `precio_unitario_venta`, `iva_aplicado`) VALUES
(1, 1, 3, 19, 1250.00, 21.00),
(2, 1, 1, 2, 2500.00, 21.00),
(3, 1, 4, 1, 1850.00, 21.00),
(4, 1, 2, 1, 8900.00, 21.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direcciones`
--

CREATE TABLE `direcciones` (
  `id_direccion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre_direccion` varchar(200) NOT NULL COMMENT 'Ej: Casa, Trabajo',
  `calle_numero` varchar(255) NOT NULL COMMENT 'Calle y número juntos',
  `piso_depto` varchar(50) DEFAULT NULL COMMENT 'Opcional',
  `ciudad` text NOT NULL,
  `provincia` text NOT NULL,
  `codigo_postal` varchar(20) NOT NULL,
  `referencia` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `direcciones`
--

INSERT INTO `direcciones` (`id_direccion`, `id_usuario`, `nombre_direccion`, `calle_numero`, `piso_depto`, `ciudad`, `provincia`, `codigo_postal`, `referencia`) VALUES
(1, 2, 'casa', 'av siempreviva 134', '', 'San Isidro', 'Buenos Aires', 'B1234', ''),
(2, 2, 'casa', 'av siempreviva 1341', '', 'San Isidro', 'San Isidro', 'B1234', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `id_empleado` int(11) NOT NULL,
  `fecha_contratacion` date NOT NULL,
  `cargo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodo_pago`
--

CREATE TABLE `metodo_pago` (
  `id_metodo` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `metodo_pago`
--

INSERT INTO `metodo_pago` (`id_metodo`, `nombre`, `descripcion`, `activo`, `fecha_creacion`) VALUES
(1, 'Efectivo', 'Pago contra entrega en efectivo', 1, '2025-11-07 21:14:43'),
(2, 'Transferencia Bancaria', 'Transferencia a cuenta bancaria', 1, '2025-11-07 21:14:43'),
(3, 'Tarjeta de Débito', 'Pago con tarjeta de débito', 1, '2025-11-07 21:14:43'),
(4, 'Tarjeta de Crédito', 'Pago con tarjeta de crédito', 1, '2025-11-07 21:14:43'),
(5, 'Mercado Pago', 'Pago a través de Mercado Pago', 1, '2025-11-07 21:14:43'),
(6, 'Billetera Virtual', 'Otros medios de pago digital', 1, '2025-11-07 21:14:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

CREATE TABLE `pedido` (
  `id_pedido` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_metodo_pago` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `costo_envio` decimal(10,2) DEFAULT 0.00,
  `total_final` decimal(10,2) NOT NULL,
  `id_direccion` int(11) NOT NULL,
  `estado` enum('pendiente','en_preparacion','enviado','recibido') DEFAULT 'pendiente',
  `fecha_pedido` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_entrega_estimada` date DEFAULT NULL,
  `fecha_envio` timestamp NULL DEFAULT NULL,
  `fecha_entrega` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido`
--

INSERT INTO `pedido` (`id_pedido`, `id_usuario`, `id_metodo_pago`, `subtotal`, `costo_envio`, `total_final`, `id_direccion`, `estado`, `fecha_pedido`, `fecha_entrega_estimada`, `fecha_envio`, `fecha_entrega`) VALUES
(1, 2, 1, 20300.00, 0.00, 20300.00, 2, 'recibido', '2025-11-13 04:35:19', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_detalle`
--

CREATE TABLE `pedido_detalle` (
  `id_detalle` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`cantidad` * `precio_unitario`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido_detalle`
--

INSERT INTO `pedido_detalle` (`id_detalle`, `id_pedido`, `id_producto`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 3, 2, 1250.00),
(2, 1, 2, 2, 8900.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_estado_log`
--

CREATE TABLE `pedido_estado_log` (
  `id_log` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_nuevo` varchar(50) NOT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp(),
  `cambiado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido_estado_log`
--

INSERT INTO `pedido_estado_log` (`id_log`, `id_pedido`, `estado_anterior`, `estado_nuevo`, `fecha_cambio`, `cambiado_por`) VALUES
(1, 1, NULL, 'pendiente', '2025-11-13 04:35:19', 2),
(2, 1, 'pendiente', 'en_preparacion', '2025-11-13 04:35:42', NULL),
(3, 1, 'en_preparacion', 'enviado', '2025-11-13 04:36:45', NULL),
(4, 1, 'enviado', 'recibido', '2025-11-13 04:36:50', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_historial`
--

CREATE TABLE `pedido_historial` (
  `id_historial` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_nuevo` varchar(50) NOT NULL,
  `fecha_cambio` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario_cambio` int(11) DEFAULT NULL COMMENT 'Quién hizo el cambio',
  `comentario` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `id_producto` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `nombre_producto` varchar(200) NOT NULL,
  `descripcion` varchar(500) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `precio_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio_anterior` decimal(10,2) DEFAULT NULL,
  `es_destacado` tinyint(1) DEFAULT 0,
  `etiqueta_especial` varchar(50) DEFAULT NULL,
  `descuento_texto` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`id_producto`, `id_categoria`, `nombre_producto`, `descripcion`, `stock`, `precio_actual`, `precio_anterior`, `es_destacado`, `etiqueta_especial`, `descuento_texto`) VALUES
(1, 1, 'Manzanas Rojas Premium', 'Manzanas rojas dulces y crujientes, importadas. Ideales para comer solas o en ensaladas.', 148, 2500.00, 3200.00, 1, 'EXCLUSIVO ONLINE', '22% OFF'),
(2, 2, 'Pollo Entero Fresco', 'Pollo entero de granja, sin menudos. Peso aproximado 2kg. Perfecto para asar.', 35, 8900.00, 10500.00, 1, NULL, '15% OFF'),
(3, 3, 'Leche Entera La Serenísima 1L', 'Leche entera UAT fortificada con vitaminas A y D. Larga vida.', 187, 1250.00, NULL, 1, 'LARGA VIDA', NULL),
(4, 5, 'Coca Cola Sabor Original 2.25L', 'Gaseosa Coca Cola sabor original en botella retornable de 2.25 litros.', 79, 1850.00, 2100.00, 1, NULL, '12% OFF'),
(5, 6, 'Arroz Integral Gallo Oro 1kg', 'Arroz integral de grano largo tipo 00000. No se pasa ni se pega.', 120, 1680.00, NULL, 1, NULL, NULL),
(6, 4, 'Pan Francés x6 unidades', 'Pan francés recién horneado del día, crocante por fuera, tierno por dentro.', 60, 2200.00, 2800.00, 0, 'OFERTA DEL DÍA', '21% OFF'),
(7, 1, 'Tomates Perita x 1kg', 'Tomates perita frescos ideales para salsas y ensaladas. Aproximadamente 6-8 tomates por kg.', 95, 1500.00, 1800.00, 0, NULL, '17% OFF'),
(8, 2, 'Carne Picada Especial 500g', 'Carne picada especial con bajo contenido graso. Ideal para hamburguesas o salsas.', 35, 4200.00, NULL, 0, NULL, NULL),
(9, 3, 'Yogur Entero Sancor Frutilla Pack x12', 'Pack económico de 12 yogures enteros sabor frutilla Sancor.', 0, 3600.00, 4200.00, 0, NULL, '14% OFF'),
(10, 5, 'Agua Mineral Villavicencio 2L', 'Agua mineral sin gas botella 2 litros', 150, 850.00, NULL, 1, 'LARGA VIDA', NULL),
(11, 6, 'Fideos Matarazzo 500g', 'Fideos secos tirabuzón de sémola', 180, 980.00, 1200.00, 1, NULL, '18% OFF'),
(12, 4, 'Medialunas x12 unidades', 'Medialunas de manteca recién horneadas', 40, 3200.00, NULL, 1, 'EXCLUSIVO ONLINE', NULL),
(13, 1, 'Bananas x1kg', 'Bananas frescas de Ecuador', 300, 1200.00, NULL, 0, NULL, NULL),
(14, 1, 'Lechuga Criolla', 'Lechuga criolla fresca', 80, 900.00, NULL, 0, NULL, NULL),
(15, 2, 'Milanesas de Pollo x6', 'Milanesas de pollo empanadas pack x6', 25, 5600.00, NULL, 0, NULL, NULL),
(16, 3, 'Queso Cremoso Mendicrim', 'Queso cremoso untable 300g', 65, 2800.00, NULL, 0, NULL, NULL),
(17, 5, 'Jugo Naranja Baggio 1L', 'Jugo de naranja con pulpa', 95, 1450.00, NULL, 0, NULL, NULL),
(18, 6, 'Aceite Girasol Cocinero 900ml', 'Aceite de girasol puro', 110, 2300.00, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_imagenes`
--

CREATE TABLE `producto_imagenes` (
  `id_imagen` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `url_imagen` varchar(500) NOT NULL,
  `orden` int(11) DEFAULT 0 COMMENT 'Para ordenar las imágenes, 1 para la principal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `producto_imagenes`
--

INSERT INTO `producto_imagenes` (`id_imagen`, `id_producto`, `url_imagen`, `orden`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=400', 1),
(2, 1, 'https://images.unsplash.com/photo-1570913149827-d2ac84ab3f9a?w=400', 2),
(3, 1, 'https://images.unsplash.com/photo-1610399313110-89e4c198e3b0?w=400', 3),
(4, 2, 'https://images.unsplash.com/photo-1587593810167-a84920ea0781?w=400', 1),
(5, 2, 'https://images.unsplash.com/photo-1626071499700-1de1c474b789?w=400', 2),
(6, 3, 'https://images.unsplash.com/photo-1563636619-e9143da7973b?w=400', 1),
(7, 4, 'https://images.unsplash.com/photo-1554866585-cd94860890b7?w=400', 1),
(8, 5, 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=400', 1),
(9, 6, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=400', 1),
(10, 7, 'https://images.unsplash.com/photo-1546470427-227a9a593cf4?w=400', 1),
(11, 8, 'https://images.unsplash.com/photo-1603048297172-c92544798d5a?w=400', 1),
(12, 9, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=400', 1),
(13, 10, 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=400', 1),
(14, 11, 'https://images.unsplash.com/photo-1551462147-ff29053bfc14?w=400', 1),
(15, 12, 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=400', 1),
(16, 13, 'https://images.unsplash.com/photo-1603833665858-e61d17a86224?w=400', 1),
(17, 14, 'https://images.unsplash.com/photo-1622206151226-18ca2c9ab4a1?w=400', 1),
(18, 15, 'https://images.unsplash.com/photo-1632778149955-e80f8ceca2e8?w=400', 1),
(19, 16, 'https://images.unsplash.com/photo-1452195100486-9cc805987862?w=400', 1),
(20, 17, 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=400', 1),
(21, 18, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=400', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_opiniones`
--

CREATE TABLE `producto_opiniones` (
  `id_opinion` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `calificacion` tinyint(1) NOT NULL CHECK (`calificacion` >= 1 and `calificacion` <= 5),
  `comentario` text DEFAULT NULL,
  `fecha_opinion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `producto_opiniones`
--

INSERT INTO `producto_opiniones` (`id_opinion`, `id_producto`, `id_usuario`, `calificacion`, `comentario`, `fecha_opinion`) VALUES
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
  `dni` varchar(15) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `nombre_usuario` varchar(200) NOT NULL,
  `correo` varchar(200) NOT NULL,
  `contrasena` varchar(200) NOT NULL COMMENT 'Guardar HASH, no texto plano'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `dni`, `id_rol`, `nombre_usuario`, `correo`, `contrasena`) VALUES
(1, '49.553.570', 3, 'Cliente 49.553.570', '49.553.570@temp.com', 'sin_pass_hashed'),
(2, '11111111', 1, 'Admin Supremo', 'admin@super.com', 'mi_pass_segura'),
(3, '22222222', 2, 'Empleado General', 'empleado@super.com', 'otra_pass_segura'),
(5, '12763516', 3, 'Cliente 12763516', '12763516@temp.com', 'sin_pass_hashed'),
(6, '99999999', 3, 'Usuario De Prueba', 'prueba@test.com', ''),
(7, '098765', 3, 'ahshfhahs', 'asftadt@gmail.com', ''),
(8, '1213421', 3, 'asfanhgdha', 'hagsfdah@asghagd', ''),
(10, '4321', 3, '4321', '4321@gmail.com', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta`
--

CREATE TABLE `venta` (
  `id_venta` int(11) NOT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `id_direccion` int(11) DEFAULT NULL,
  `fecha_venta` datetime NOT NULL DEFAULT current_timestamp(),
  `total_venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `venta`
--

INSERT INTO `venta` (`id_venta`, `id_empleado`, `id_direccion`, `fecha_venta`, `total_venta`, `estado`, `id_usuario`) VALUES
(1, NULL, 1, '2025-11-12 23:11:24', 39500.00, 1, 2);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD PRIMARY KEY (`id_carrito`),
  ADD KEY `FK_Carrito_Usuario` (`id_usuario`),
  ADD KEY `FK_Carrito_Producto` (`id_producto`);

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
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`id_empleado`);

--
-- Indices de la tabla `metodo_pago`
--
ALTER TABLE `metodo_pago`
  ADD PRIMARY KEY (`id_metodo`);

--
-- Indices de la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `FK_Pedido_Usuario` (`id_usuario`),
  ADD KEY `FK_Pedido_MetodoPago` (`id_metodo_pago`),
  ADD KEY `id_direccion` (`id_direccion`);

--
-- Indices de la tabla `pedido_detalle`
--
ALTER TABLE `pedido_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `FK_Detalle_Pedido` (`id_pedido`),
  ADD KEY `FK_Detalle_Producto` (`id_producto`);

--
-- Indices de la tabla `pedido_estado_log`
--
ALTER TABLE `pedido_estado_log`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_pedido` (`id_pedido`);

--
-- Indices de la tabla `pedido_historial`
--
ALTER TABLE `pedido_historial`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `FK_Historial_Pedido` (`id_pedido`);

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
  ADD PRIMARY KEY (`id_imagen`),
  ADD KEY `FK_ProductoImagen_Producto` (`id_producto`);

--
-- Indices de la tabla `producto_opiniones`
--
ALTER TABLE `producto_opiniones`
  ADD PRIMARY KEY (`id_opinion`),
  ADD KEY `FK_Opinion_Producto` (`id_producto`),
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
  ADD UNIQUE KEY `DNI` (`dni`),
  ADD KEY `FK_Usuario_Rol` (`id_rol`);

--
-- Indices de la tabla `venta`
--
ALTER TABLE `venta`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `FK_Venta_Empleado` (`id_empleado`),
  ADD KEY `FK_Venta_Direccion` (`id_direccion`),
  ADD KEY `FK_Venta_Usuario` (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `carrito`
--
ALTER TABLE `carrito`
  MODIFY `id_carrito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id_detalle_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  MODIFY `id_direccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `metodo_pago`
--
ALTER TABLE `metodo_pago`
  MODIFY `id_metodo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pedido`
--
ALTER TABLE `pedido`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pedido_detalle`
--
ALTER TABLE `pedido_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pedido_estado_log`
--
ALTER TABLE `pedido_estado_log`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pedido_historial`
--
ALTER TABLE `pedido_historial`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  MODIFY `id_imagen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `producto_opiniones`
--
ALTER TABLE `producto_opiniones`
  MODIFY `id_opinion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `venta`
--
ALTER TABLE `venta`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `pedido_estado_log`
--
ALTER TABLE `pedido_estado_log`
  ADD CONSTRAINT `FK_Log_Pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE;

-- ============================================================
-- TRIGGERS
-- ============================================================

--
-- Trigger: Crear venta cuando pedido se marca como recibido
--
DELIMITER $$

CREATE TRIGGER crear_venta_al_recibir_pedido
AFTER UPDATE ON pedido
FOR EACH ROW
BEGIN
    -- Solo si el estado cambió a 'recibido'
    IF NEW.estado = 'recibido' AND OLD.estado != 'recibido' THEN
        INSERT INTO venta (
            id_usuario,
            id_direccion,
            fecha_venta,
            total_venta,
            estado
        ) VALUES (
            NEW.id_usuario,
            NEW.id_direccion,
            NOW(),
            NEW.total_final,
            1
        );
    END IF;
END$$

DELIMITER ;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
