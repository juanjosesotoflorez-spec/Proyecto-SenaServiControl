<?php
session_start();
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol'] ?? '') !== 'recepcionista') {
    header('Location: login.php');
    exit;
}

require dirname(__DIR__, 2) . '/db.php';
require_once dirname(__DIR__) . '/compartido/auditoria_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: recepcionista_servicios.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    header('Location: recepcionista_servicios.php?error=sesion');
    exit;
}

$idServicio = (int) ($_POST['id_servicio'] ?? 0);
$fechaTexto = trim($_POST['fecha_entrega_estimada'] ?? '');
$fechaEstimada = null;

if ($fechaTexto !== '') {
    $fecha = DateTime::createFromFormat('!Y-m-d', $fechaTexto);
    if ($fecha === false || $fecha->format('Y-m-d') !== $fechaTexto) {
        header('Location: recepcionista_servicios.php?error=fecha');
        exit;
    }
    $fechaEstimada = $fechaTexto;
}

if ($idServicio <= 0) {
    header('Location: recepcionista_servicios.php?error=servicio');
    exit;
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        'SELECT s.estado, s.fecha_ingreso, s.fecha_entrega_estimada, c.id_cliente
         FROM servicio s
         JOIN vehiculo v ON v.id_vehiculo = s.id_vehiculo
         JOIN cliente c ON c.id_cliente = v.id_cliente
         WHERE s.id_servicio = ?
         FOR UPDATE'
    );
    $stmt->bind_param('i', $idServicio);
    $stmt->execute();
    $servicio = $stmt->get_result()->fetch_assoc();

    if (
        !$servicio ||
        $servicio['estado'] === null ||
        trim((string) $servicio['estado']) === '' ||
        $servicio['estado'] === 'Finalizado'
    ) {
        $conn->rollback();
        header('Location: recepcionista_servicios.php?error=servicio');
        exit;
    }

    if ($fechaEstimada !== null && $fechaEstimada < $servicio['fecha_ingreso']) {
        $conn->rollback();
        header('Location: recepcionista_servicios.php?error=fecha');
        exit;
    }

    if ($fechaEstimada === $servicio['fecha_entrega_estimada']) {
        $conn->commit();
        header('Location: recepcionista_servicios.php?mensaje=sin_cambios');
        exit;
    }

    $stmt = $conn->prepare('UPDATE servicio SET fecha_entrega_estimada = ? WHERE id_servicio = ?');
    $stmt->bind_param('si', $fechaEstimada, $idServicio);
    $stmt->execute();

    $fechaRegistrada = $fechaEstimada === null ? 'sin fecha estimada' : $fechaEstimada;
    $comentario = 'Planificación actualizada por recepción: fecha estimada ' . $fechaRegistrada . '.';
    $estado = (string) $servicio['estado'];
    $stmt = $conn->prepare(
        "INSERT INTO seguimiento (fecha, fecha_hora, estado, comentario, id_servicio, id_tecnico, aprendiz)
         VALUES (CURDATE(), NOW(), ?, ?, ?, NULL, '')"
    );
    $stmt->bind_param('ssi', $estado, $comentario, $idServicio);
    $stmt->execute();

    $mensaje = 'Servicio #' . $idServicio . ': ' . $comentario;
    $estadoNotificacion = 'Interna';
    $idCliente = (int) $servicio['id_cliente'];
    $stmt = $conn->prepare(
        'INSERT INTO notificacion (mensaje, fecha_envio, estado, id_cliente, id_servicio)
         VALUES (?, NOW(), ?, ?, ?)'
    );
    $stmt->bind_param('ssii', $mensaje, $estadoNotificacion, $idCliente, $idServicio);
    $stmt->execute();

    if (!registrarActividadSistema(
        $conn,
        (int)$_SESSION['id_usuario'],
        'fecha_estimada_actualizada',
        'servicio',
        $idServicio,
        $idServicio,
        $comentario
    )) {
        throw new mysqli_sql_exception('No se pudo registrar la auditoría de la fecha estimada.');
    }
    $conn->commit();
    header('Location: recepcionista_servicios.php?mensaje=fecha_actualizada');
    exit;
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    error_log('Error al actualizar fecha estimada: ' . $error->getMessage());
    header('Location: recepcionista_servicios.php?error=guardar_fecha');
    exit;
}
