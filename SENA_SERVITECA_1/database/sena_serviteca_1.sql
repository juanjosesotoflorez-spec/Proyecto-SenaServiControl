-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-10-2026 a las 19:13:11
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

CREATE DATABASE IF NOT EXISTS `sena_serviteca_1`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
USE `sena_serviteca_1`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sena_serviteca_1`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE PROCEDURE `actualizar_estado_servicio` (IN `p_id_servicio` INT, IN `p_estado` VARCHAR(50))   BEGIN
    UPDATE servicio
    SET estado = p_estado
    WHERE id_servicio = p_id_servicio;
END$$

CREATE PROCEDURE `registrar_cliente` (IN `p_nombre` VARCHAR(50), IN `p_apellido` VARCHAR(50), IN `p_documento` VARCHAR(20), IN `p_telefono` VARCHAR(20), IN `p_direccion` VARCHAR(100), IN `p_correo` VARCHAR(100))   BEGIN
    INSERT INTO cliente(
        nombre,
        apellido,
        documento,
        telefono,
        direccion,
        correo
    )
    VALUES(
        p_nombre,
        p_apellido,
        p_documento,
        p_telefono,
        p_direccion,
        p_correo
    );
END$$

CREATE PROCEDURE `registrar_servicio` (IN `p_tipo_servicio` VARCHAR(100), IN `p_descripcion` TEXT, IN `p_fecha_ingreso` DATE, IN `p_estado` VARCHAR(50), IN `p_costo` DECIMAL(10,2), IN `p_id_vehiculo` INT, IN `p_id_tecnico` INT, IN `p_id_recepcionista` INT)   BEGIN
    INSERT INTO servicio(
        tipo_servicio,
        descripcion,
        fecha_ingreso,
        estado,
        costo,
        id_vehiculo,
        id_tecnico,
        id_recepcionista
    )
    VALUES(
        p_tipo_servicio,
        p_descripcion,
        p_fecha_ingreso,
        p_estado,
        p_costo,
        p_id_vehiculo,
        p_id_tecnico,
        p_id_recepcionista
    );
END$$

CREATE PROCEDURE `registrar_vehiculo` (IN `p_placa` VARCHAR(10), IN `p_marca` VARCHAR(50), IN `p_modelo` VARCHAR(50), IN `p_color` VARCHAR(30), IN `p_anio` YEAR, IN `p_kilometraje` INT UNSIGNED, IN `p_id_cliente` INT)   BEGIN
    INSERT INTO vehiculo(
        placa,
        marca,
        modelo,
        color,
        anio,
        kilometraje,
        id_cliente
    )
    VALUES(
        p_placa,
        p_marca,
        p_modelo,
        p_color,
        p_anio,
        p_kilometraje,
        p_id_cliente
    );
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administrador`
--

CREATE TABLE `administrador` (
  `id_administrador` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `administrador`
--

INSERT INTO `administrador` (`id_administrador`, `nombre`, `apellido`, `documento`, `telefono`, `id_usuario`) VALUES
(1, 'Carlos', 'Ramirez', '1001', '3001111111', 1),
(2, 'Luis', 'Gomez', '1002', '3001111112', 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(100) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`id_cliente`, `nombre`, `apellido`, `documento`, `telefono`, `direccion`, `correo`, `id_usuario`) VALUES
(1, 'Juan', 'Martinez', '3001', '3010000001', 'Calle 1', 'cliente1@gmail.com', 3),
(2, 'Pedro', 'Garcia', '3002', '3010000002', 'Calle 2', 'cliente2@gmail.com', 4),
(3, 'Luis', 'Perez', '3003', '3010000003', 'Calle 3', 'cliente3@gmail.com', 5),
(4, 'Juan', 'Perez', '12345678', '3001234567', 'Calle 10', 'juan@gmail.com', NULL),
(5, 'SAMUEL', '', '0000000000', '', '', 'samu@gmail.com', 11);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `confirmacion_notificacion`
--

CREATE TABLE `confirmacion_notificacion` (
  `id_confirmacion` int(11) NOT NULL,
  `id_notificacion` int(11) NOT NULL,
  `fecha_confirmacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_reparacion`
--

CREATE TABLE `detalle_reparacion` (
  `id_detalle` int(11) NOT NULL,
  `id_reparacion` int(11) NOT NULL,
  `id_repuesto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_reparacion`
--

INSERT INTO `detalle_reparacion` (`id_detalle`, `id_reparacion`, `id_repuesto`, `cantidad`, `subtotal`) VALUES
(1, 1, 1, 1, 250000.00),
(2, 2, 2, 1, 120000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimiento`
--

CREATE TABLE `mantenimiento` (
  `id_mantenimiento` int(11) NOT NULL,
  `tipo_mantenimiento` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_programacion` date DEFAULT NULL,
  `estado_autorizacion` varchar(20) DEFAULT NULL,
  `estado_trabajo` varchar(30) DEFAULT NULL,
  `fecha_respuesta_autorizacion` datetime DEFAULT NULL,
  `id_servicio` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mantenimiento`
--

INSERT INTO `mantenimiento` (`id_mantenimiento`, `tipo_mantenimiento`, `observaciones`, `id_servicio`) VALUES
(1, 'Preventivo', 'Cambio de aceite y filtros realizado', 1),
(2, 'General', 'Revision completa del vehiculo', 3),
(3, 'Alineacion', 'Balanceo y alineacion completados', 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificacion`
--

CREATE TABLE `notificacion` (
  `id_notificacion` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha_envio` datetime DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_servicio` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificacion`
--

INSERT INTO `notificacion` (`id_notificacion`, `mensaje`, `fecha_envio`, `estado`, `id_cliente`, `id_servicio`) VALUES
(1, 'Su vehiculo esta listo para entrega', '2025-06-02 10:00:00', 'Enviada', 1, NULL),
(2, 'La reparacion de su vehiculo fue finalizada', '2025-06-04 15:00:00', 'Enviada', 2, NULL),
(3, 'Su servicio continua en proceso', '2025-06-05 11:00:00', 'Pendiente', 3, NULL),
(4, 'Esperando aprobacion del servicio', '2025-06-06 09:00:00', 'Pendiente', 1, NULL),
(5, 'Vehiculo entregado satisfactoriamente', '2025-06-07 16:00:00', 'Enviada', 2, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recepcionista`
--

CREATE TABLE `recepcionista` (
  `id_recepcionista` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `turno` varchar(30) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `recepcionista`
--

INSERT INTO `recepcionista` (`id_recepcionista`, `nombre`, `apellido`, `documento`, `telefono`, `turno`, `id_usuario`) VALUES
(1, 'Laura', 'Perez', '2001', '3002222201', 'Mañana', 2),
(2, 'Sara', 'Gomez', '2002', '3002222202', 'Tarde', 9);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reparacion`
--

CREATE TABLE `reparacion` (
  `id_reparacion` int(11) NOT NULL,
  `problema_reportado` text DEFAULT NULL,
  `dano_detectado` text DEFAULT NULL,
  `presupuesto` decimal(10,2) DEFAULT NULL,
  `solucion` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` varchar(40) DEFAULT NULL,
  `estado_presupuesto` varchar(30) DEFAULT NULL,
  `fecha_respuesta_presupuesto` datetime DEFAULT NULL,
  `id_servicio` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reparacion`
--

INSERT INTO `reparacion` (`id_reparacion`, `dano_detectado`, `solucion`, `observaciones`, `id_servicio`) VALUES
(1, 'Bateria descargada', 'Reemplazo de bateria', 'Prueba de encendido satisfactoria', 2),
(2, 'Pastillas de freno desgastadas', 'Cambio de frenos', 'Sistema probado correctamente', 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repuesto`
--

CREATE TABLE `repuesto` (
  `id_repuesto` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `repuesto`
--

INSERT INTO `repuesto` (`id_repuesto`, `nombre`, `descripcion`, `precio`, `stock`) VALUES
(1, 'Bateria Bosch', 'Bateria 12V para automovil', 250000.00, 10),
(2, 'Pastillas de Freno', 'Juego delantero', 120000.00, 20),
(3, 'Filtro de Aceite', 'Filtro para motor gasolina', 25000.00, 30),
(4, 'Aceite 20W50', 'Lubricante para motor', 35000.00, 40),
(5, 'Filtro de Aire', 'Filtro de admision', 20000.00, 25);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimiento`
--

CREATE TABLE `seguimiento` (
  `id_seguimiento` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `fecha_hora` datetime DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `id_servicio` int(11) NOT NULL,
  `id_tecnico` int(11) DEFAULT NULL,
  `aprendiz` varchar(150) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `seguimiento`
--

INSERT INTO `seguimiento` (`id_seguimiento`, `fecha`, `estado`, `comentario`, `id_servicio`, `id_tecnico`, `aprendiz`) VALUES
(1, '2025-06-01', 'Recibido', 'Vehiculo ingresado al taller', 1, NULL, ''),
(2, '2025-06-02', 'Finalizado', 'Mantenimiento completado', 1, NULL, ''),
(3, '2025-06-03', 'Recibido', 'Vehiculo pendiente de reparacion', 2, NULL, ''),
(4, '2025-06-05', 'En proceso', 'Revision tecnica en curso', 3, NULL, ''),
(5, '2025-06-06', 'Pendiente', 'Esperando llegada de repuestos', 4, NULL, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio`
--

CREATE TABLE `servicio` (
  `id_servicio` int(11) NOT NULL,
  `tipo_servicio` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_cita` date DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `fecha_entrega_estimada` date DEFAULT NULL,
  `fecha_entrega_real` date DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `id_tecnico` int(11) NOT NULL,
  `id_recepcionista` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Estructura de tabla para la tabla `historial_asignacion_tecnico`
--

CREATE TABLE `historial_asignacion_tecnico` (
  `id_asignacion` int(11) NOT NULL,
  `id_servicio` int(11) NOT NULL,
  `id_tecnico_anterior` int(11) DEFAULT NULL,
  `id_tecnico_nuevo` int(11) NOT NULL,
  `id_recepcionista` int(11) DEFAULT NULL,
  `id_administrador` int(11) DEFAULT NULL,
  `fecha_asignacion` datetime NOT NULL DEFAULT current_timestamp(),
  `motivo` text DEFAULT NULL,
  `estado_aceptacion` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `fecha_aceptacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Estructura de tabla para la tabla `entrega_vehiculo`
--

CREATE TABLE `entrega_vehiculo` (
  `id_entrega` int(11) NOT NULL,
  `id_servicio` int(11) NOT NULL,
  `id_recepcionista` int(11) NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_salida` datetime DEFAULT NULL,
  `estado_confirmacion_cliente` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `fecha_confirmacion_cliente` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Estructura de tabla para la tabla `historial_actividad_usuario`
--

CREATE TABLE `historial_actividad_usuario` (
  `id_actividad` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_servicio` int(11) DEFAULT NULL,
  `entidad` varchar(40) NOT NULL,
  `id_registro` int(11) DEFAULT NULL,
  `accion` varchar(60) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Estructura de tabla para la tabla `incidencia`
--

CREATE TABLE `incidencia` (
  `id_incidencia` int(11) NOT NULL,
  `id_usuario_reporta` int(11) NOT NULL,
  `id_usuario_atendio` int(11) DEFAULT NULL,
  `id_servicio` int(11) DEFAULT NULL,
  `descripcion` text NOT NULL,
  `estado` varchar(25) NOT NULL DEFAULT 'Abierta',
  `nota_atencion` text DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicio`
--

INSERT INTO `servicio` (`id_servicio`, `tipo_servicio`, `descripcion`, `fecha_ingreso`, `fecha_entrega`, `fecha_entrega_estimada`, `fecha_entrega_real`, `estado`, `costo`, `id_vehiculo`, `id_tecnico`, `id_recepcionista`) VALUES
(1, 'Mantenimiento', 'Cambio de aceite y filtros', '2025-06-01', '2025-06-02', NULL, NULL, 'Finalizado', 150000.00, 1, 1, 1),
(2, 'Reparacion', 'Cambio de bateria', '2025-06-03', '2025-06-04', NULL, NULL, 'Finalizado', 280000.00, 2, 2, 1),
(3, 'Mantenimiento', 'Revision general del vehiculo', '2025-06-05', NULL, NULL, NULL, 'En proceso', 120000.00, 3, 3, 2),
(4, 'Reparacion', 'Cambio de pastillas de freno', '2025-06-06', NULL, NULL, NULL, 'Pendiente', 350000.00, 4, 1, 2),
(5, 'Mantenimiento', 'Alineacion y balanceo', '2025-06-07', NULL, NULL, NULL, 'En proceso', 90000.00, 5, 2, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tecnico`
--

CREATE TABLE `tecnico` (
  `id_tecnico` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `experiencia` varchar(100) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tecnico`
--

INSERT INTO `tecnico` (`id_tecnico`, `nombre`, `apellido`, `documento`, `telefono`, `especialidad`, `experiencia`, `id_usuario`) VALUES
(1, 'Ricardo', 'Mora', '4001', '3020000001', 'Mecanica General', '5 años', 6),
(2, 'Julian', 'Perez', '4002', '3020000002', 'Electricidad Automotriz', '4 años', 7),
(3, 'Oscar', 'Lopez', '4003', '3020000003', 'Suspension', '6 años', 8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('administrador','recepcionista','cliente','tecnico') NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `usuario`, `correo`, `contrasena`, `rol`, `estado`) VALUES
(1, 'admin1', 'admin1@serviteca.com', '123456', 'administrador', 'activo'),
(2, 'recep1', 'recep1@serviteca.com', '123456', 'recepcionista', 'activo'),
(3, 'cliente1', 'cliente1@gmail.com', '123456', 'cliente', 'activo'),
(4, 'cliente2', 'cliente2@gmail.com', '123456', 'cliente', 'activo'),
(5, 'cliente3', 'cliente3@gmail.com', '123456', 'cliente', 'activo'),
(6, 'tecnico1', 'tecnico1@serviteca.com', '123456', 'tecnico', 'activo'),
(7, 'tecnico2', 'tecnico2@serviteca.com', '123456', 'tecnico', 'activo'),
(8, 'tecnico3', 'tecnico3@serviteca.com', '123456', 'tecnico', 'activo'),
(9, 'recep2', 'recep2@serviteca.com', '123456', 'recepcionista', 'activo'),
(10, 'admin2', 'admin2@serviteca.com', '123456', 'administrador', 'activo'),
(11, 'SAMUEL', 'samu@gmail.com', '$2y$10$btnV.OfFpQHWOrgG5iDEaeGxT8yaug6nh/fBA1XpLgr1huFeixMQy', 'cliente', 'activo'),
(12, 'emma', 'emma@gmail.com', '12345678', 'cliente', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculo`
--

CREATE TABLE `vehiculo` (
  `id_vehiculo` int(11) NOT NULL,
  `placa` varchar(10) NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `anio` year(4) DEFAULT NULL,
  `kilometraje` int(10) UNSIGNED DEFAULT NULL,
  `id_cliente` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vehiculo`
--

INSERT INTO `vehiculo` (`id_vehiculo`, `placa`, `marca`, `modelo`, `color`, `anio`, `kilometraje`, `id_cliente`) VALUES
(1, 'ABC123', 'Chevrolet', 'Spark', 'Rojo', '2020', NULL, 1),
(2, 'DEF456', 'Mazda', 'CX5', 'Blanco', '2022', NULL, 2),
(3, 'GHI789', 'Renault', 'Logan', 'Negro', '2019', NULL, 3),
(4, 'JKL321', 'Kia', 'Rio', 'Gris', '2021', NULL, 1),
(5, 'MNO654', 'Toyota', 'Corolla', 'Azul', '2023', NULL, 2);

-- --------------------------------------------------------

--
-- Solicitudes de cita o servicio enviadas por clientes
--

CREATE TABLE `solicitud_servicio` (
  `id_solicitud` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `tipo_servicio` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha_solicitada` date NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `fecha_solicitud` datetime NOT NULL DEFAULT current_timestamp(),
  `id_servicio` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_inventario_repuestos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_inventario_repuestos` (
`id_repuesto` int(11)
,`nombre` varchar(100)
,`descripcion` text
,`precio` decimal(10,2)
,`stock` int(11)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_seguimiento_servicios`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_seguimiento_servicios` (
`id_servicio` int(11)
,`placa` varchar(10)
,`fecha` date
,`estado` varchar(50)
,`comentario` text
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_servicios`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_servicios` (
`id_servicio` int(11)
,`cliente` varchar(50)
,`apellido` varchar(50)
,`placa` varchar(10)
,`tipo_servicio` varchar(100)
,`fecha_ingreso` date
,`estado` varchar(50)
,`costo` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_inventario_repuestos`
--
DROP TABLE IF EXISTS `vista_inventario_repuestos`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_inventario_repuestos`  AS SELECT `repuesto`.`id_repuesto` AS `id_repuesto`, `repuesto`.`nombre` AS `nombre`, `repuesto`.`descripcion` AS `descripcion`, `repuesto`.`precio` AS `precio`, `repuesto`.`stock` AS `stock` FROM `repuesto` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_seguimiento_servicios`
--
DROP TABLE IF EXISTS `vista_seguimiento_servicios`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_seguimiento_servicios`  AS SELECT `s`.`id_servicio` AS `id_servicio`, `v`.`placa` AS `placa`, `seg`.`fecha` AS `fecha`, `seg`.`estado` AS `estado`, `seg`.`comentario` AS `comentario` FROM ((`seguimiento` `seg` join `servicio` `s` on(`seg`.`id_servicio` = `s`.`id_servicio`)) join `vehiculo` `v` on(`s`.`id_vehiculo` = `v`.`id_vehiculo`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_servicios`
--
DROP TABLE IF EXISTS `vista_servicios`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_servicios`  AS SELECT `s`.`id_servicio` AS `id_servicio`, `c`.`nombre` AS `cliente`, `c`.`apellido` AS `apellido`, `v`.`placa` AS `placa`, `s`.`tipo_servicio` AS `tipo_servicio`, `s`.`fecha_ingreso` AS `fecha_ingreso`, `s`.`estado` AS `estado`, `s`.`costo` AS `costo` FROM ((`servicio` `s` join `vehiculo` `v` on(`s`.`id_vehiculo` = `v`.`id_vehiculo`)) join `cliente` `c` on(`v`.`id_cliente` = `c`.`id_cliente`)) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administrador`
--
ALTER TABLE `administrador`
  ADD PRIMARY KEY (`id_administrador`),
  ADD UNIQUE KEY `documento` (`documento`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `documento` (`documento`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `confirmacion_notificacion`
--
ALTER TABLE `confirmacion_notificacion`
  ADD PRIMARY KEY (`id_confirmacion`),
  ADD UNIQUE KEY `uq_confirmacion_notificacion` (`id_notificacion`);

--
-- Indices de la tabla `detalle_reparacion`
--
ALTER TABLE `detalle_reparacion`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_reparacion` (`id_reparacion`),
  ADD KEY `id_repuesto` (`id_repuesto`);

--
-- Indices de la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  ADD PRIMARY KEY (`id_mantenimiento`),
  ADD UNIQUE KEY `id_servicio` (`id_servicio`);

--
-- Indices de la tabla `notificacion`
--
ALTER TABLE `notificacion`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `fk_notificacion_servicio` (`id_servicio`);

--
-- Indices de la tabla `recepcionista`
--
ALTER TABLE `recepcionista`
  ADD PRIMARY KEY (`id_recepcionista`),
  ADD UNIQUE KEY `documento` (`documento`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `reparacion`
--
ALTER TABLE `reparacion`
  ADD PRIMARY KEY (`id_reparacion`),
  ADD UNIQUE KEY `id_servicio` (`id_servicio`);

--
-- Indices de la tabla `repuesto`
--
ALTER TABLE `repuesto`
  ADD PRIMARY KEY (`id_repuesto`);

--
-- Indices de la tabla `seguimiento`
--
ALTER TABLE `seguimiento`
  ADD PRIMARY KEY (`id_seguimiento`),
  ADD KEY `id_servicio` (`id_servicio`),
  ADD KEY `fk_seguimiento_tecnico` (`id_tecnico`);

--
-- Indices de la tabla `servicio`
--
ALTER TABLE `servicio`
  ADD PRIMARY KEY (`id_servicio`),
  ADD KEY `id_vehiculo` (`id_vehiculo`),
  ADD KEY `id_tecnico` (`id_tecnico`),
  ADD KEY `id_recepcionista` (`id_recepcionista`);

--
-- Indices de la tabla `historial_asignacion_tecnico`
--
ALTER TABLE `historial_asignacion_tecnico`
  ADD PRIMARY KEY (`id_asignacion`),
  ADD KEY `fk_hist_asignacion_servicio` (`id_servicio`),
  ADD KEY `fk_hist_asignacion_tecnico_anterior` (`id_tecnico_anterior`),
  ADD KEY `fk_hist_asignacion_tecnico_nuevo` (`id_tecnico_nuevo`),
  ADD KEY `fk_hist_asignacion_recepcionista` (`id_recepcionista`),
  ADD KEY `fk_hist_asignacion_administrador` (`id_administrador`);

--
-- Indices de la tabla `entrega_vehiculo`
--
ALTER TABLE `entrega_vehiculo`
  ADD PRIMARY KEY (`id_entrega`),
  ADD UNIQUE KEY `id_servicio` (`id_servicio`),
  ADD KEY `fk_entrega_recepcionista` (`id_recepcionista`);

--
-- Indices de la tabla `historial_actividad_usuario`
--
ALTER TABLE `historial_actividad_usuario`
  ADD PRIMARY KEY (`id_actividad`),
  ADD KEY `idx_hist_actividad_usuario_fecha` (`id_usuario`,`fecha_hora`),
  ADD KEY `idx_hist_actividad_servicio_fecha` (`id_servicio`,`fecha_hora`),
  ADD KEY `idx_hist_actividad_entidad_registro` (`entidad`,`id_registro`);

--
-- Indices de la tabla `incidencia`
--
ALTER TABLE `incidencia`
  ADD PRIMARY KEY (`id_incidencia`),
  ADD KEY `idx_incidencia_estado_fecha` (`estado`,`fecha_registro`),
  ADD KEY `idx_incidencia_usuario_fecha` (`id_usuario_reporta`,`fecha_registro`),
  ADD KEY `idx_incidencia_servicio` (`id_servicio`);

--
-- Indices de la tabla `tecnico`
--
ALTER TABLE `tecnico`
  ADD PRIMARY KEY (`id_tecnico`),
  ADD UNIQUE KEY `documento` (`documento`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  ADD PRIMARY KEY (`id_vehiculo`),
  ADD UNIQUE KEY `placa` (`placa`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `solicitud_servicio`
--
ALTER TABLE `solicitud_servicio`
  ADD PRIMARY KEY (`id_solicitud`),
  ADD UNIQUE KEY `uq_solicitud_servicio_servicio` (`id_servicio`),
  ADD KEY `idx_solicitud_servicio_estado_fecha` (`estado`,`fecha_solicitud`),
  ADD KEY `idx_solicitud_servicio_vehiculo` (`id_vehiculo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administrador`
--
ALTER TABLE `administrador`
  MODIFY `id_administrador` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `confirmacion_notificacion`
--
ALTER TABLE `confirmacion_notificacion`
  MODIFY `id_confirmacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_reparacion`
--
ALTER TABLE `detalle_reparacion`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  MODIFY `id_mantenimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `historial_asignacion_tecnico`
--
ALTER TABLE `historial_asignacion_tecnico`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `entrega_vehiculo`
--
ALTER TABLE `entrega_vehiculo`
  MODIFY `id_entrega` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_actividad_usuario`
--
ALTER TABLE `historial_actividad_usuario`
  MODIFY `id_actividad` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `incidencia`
--
ALTER TABLE `incidencia`
  MODIFY `id_incidencia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificacion`
--
ALTER TABLE `notificacion`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `recepcionista`
--
ALTER TABLE `recepcionista`
  MODIFY `id_recepcionista` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `reparacion`
--
ALTER TABLE `reparacion`
  MODIFY `id_reparacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `repuesto`
--
ALTER TABLE `repuesto`
  MODIFY `id_repuesto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `seguimiento`
--
ALTER TABLE `seguimiento`
  MODIFY `id_seguimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `servicio`
--
ALTER TABLE `servicio`
  MODIFY `id_servicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tecnico`
--
ALTER TABLE `tecnico`
  MODIFY `id_tecnico` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  MODIFY `id_vehiculo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `solicitud_servicio`
--
ALTER TABLE `solicitud_servicio`
  MODIFY `id_solicitud` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `administrador`
--
ALTER TABLE `administrador`
  ADD CONSTRAINT `administrador_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD CONSTRAINT `cliente_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `confirmacion_notificacion`
--
ALTER TABLE `confirmacion_notificacion`
  ADD CONSTRAINT `fk_confirmacion_notificacion` FOREIGN KEY (`id_notificacion`) REFERENCES `notificacion` (`id_notificacion`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_reparacion`
--
ALTER TABLE `detalle_reparacion`
  ADD CONSTRAINT `detalle_reparacion_ibfk_1` FOREIGN KEY (`id_reparacion`) REFERENCES `reparacion` (`id_reparacion`),
  ADD CONSTRAINT `detalle_reparacion_ibfk_2` FOREIGN KEY (`id_repuesto`) REFERENCES `repuesto` (`id_repuesto`);

--
-- Filtros para la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  ADD CONSTRAINT `mantenimiento_ibfk_1` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`);

--
-- Filtros para la tabla `historial_asignacion_tecnico`
--
ALTER TABLE `historial_asignacion_tecnico`
  ADD CONSTRAINT `fk_hist_asignacion_administrador` FOREIGN KEY (`id_administrador`) REFERENCES `administrador` (`id_administrador`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hist_asignacion_recepcionista` FOREIGN KEY (`id_recepcionista`) REFERENCES `recepcionista` (`id_recepcionista`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hist_asignacion_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hist_asignacion_tecnico_anterior` FOREIGN KEY (`id_tecnico_anterior`) REFERENCES `tecnico` (`id_tecnico`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hist_asignacion_tecnico_nuevo` FOREIGN KEY (`id_tecnico_nuevo`) REFERENCES `tecnico` (`id_tecnico`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `entrega_vehiculo`
--
ALTER TABLE `entrega_vehiculo`
  ADD CONSTRAINT `fk_entrega_recepcionista` FOREIGN KEY (`id_recepcionista`) REFERENCES `recepcionista` (`id_recepcionista`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_entrega_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `historial_actividad_usuario`
--
ALTER TABLE `historial_actividad_usuario`
  ADD CONSTRAINT `fk_hist_actividad_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hist_actividad_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `incidencia`
--
ALTER TABLE `incidencia`
  ADD CONSTRAINT `fk_incidencia_reporta` FOREIGN KEY (`id_usuario_reporta`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_incidencia_atendio` FOREIGN KEY (`id_usuario_atendio`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_incidencia_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `notificacion`
--
ALTER TABLE `notificacion`
  ADD CONSTRAINT `fk_notificacion_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON UPDATE CASCADE,
  ADD CONSTRAINT `notificacion_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`);

--
-- Filtros para la tabla `recepcionista`
--
ALTER TABLE `recepcionista`
  ADD CONSTRAINT `recepcionista_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `reparacion`
--
ALTER TABLE `reparacion`
  ADD CONSTRAINT `reparacion_ibfk_1` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`);

--
-- Filtros para la tabla `seguimiento`
--
ALTER TABLE `seguimiento`
  ADD CONSTRAINT `fk_seguimiento_tecnico` FOREIGN KEY (`id_tecnico`) REFERENCES `tecnico` (`id_tecnico`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `seguimiento_ibfk_1` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`);

--
-- Filtros para la tabla `servicio`
--
ALTER TABLE `servicio`
  ADD CONSTRAINT `servicio_ibfk_1` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_vehiculo`),
  ADD CONSTRAINT `servicio_ibfk_2` FOREIGN KEY (`id_tecnico`) REFERENCES `tecnico` (`id_tecnico`),
  ADD CONSTRAINT `servicio_ibfk_3` FOREIGN KEY (`id_recepcionista`) REFERENCES `recepcionista` (`id_recepcionista`);

--
-- Filtros para la tabla `tecnico`
--
ALTER TABLE `tecnico`
  ADD CONSTRAINT `tecnico_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  ADD CONSTRAINT `vehiculo_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`);

--
-- Filtros para la tabla `solicitud_servicio`
--
ALTER TABLE `solicitud_servicio`
  ADD CONSTRAINT `fk_solicitud_servicio_vehiculo` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_vehiculo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_solicitud_servicio_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
