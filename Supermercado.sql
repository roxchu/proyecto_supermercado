-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 03-10-2025 a las 01:13:56
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
-- Base de datos: `Supermercado`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion_corta` varchar(255) DEFAULT NULL,
  `precio_actual` decimal(10,3) NOT NULL,
  `precio_anterior` decimal(10,3) DEFAULT NULL,
  `imagen_url` varchar(255) NOT NULL,
  `etiqueta_especial` varchar(50) DEFAULT NULL,
  `es_destacado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `descripcion_corta`, `precio_actual`, `precio_anterior`, `imagen_url`, `etiqueta_especial`, `es_destacado`) VALUES
(1, 'Nescafé Dolca Choco Mocha X 125 Gr.', '2x1', 4.150, NULL, 'img/cafe.png', 'EXCLUSIVO ONLINE', 1),
(2, 'Leche Descremada La Serenísima Protein 1 Lt.', '3x2', 2.470, NULL, 'img/leche.png', 'LARGA VIDA', 1),
(3, 'Galletitas Dulces Melba Rellenas', '38% OFF', 0.790, 1.285, 'img/galletas.png', 'Melba 120g', 1),
(4, 'Yogur Batido sabor Natural Ser 300 Gr.', '2do al 70%', 3.750, NULL, 'img/yogur.png', 'SER BIG POT', 1),
(5, 'Manteca Sello de Oro', '35% OFF', 2.570, 3.955, 'img/manteca.png', NULL, 1),
(6, 'Queso Cremoso La Paulina (Precio/Kg)', NULL, 9.890, NULL, 'img/queso_cremoso.png', 'PACK AHORRO', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
