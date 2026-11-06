<?php
session_start();
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol'] ?? '') !== 'recepcionista') {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../db.php';
require_once __DIR__ . '/auditoria_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro_servicios.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    header('Location: registro_servicios.php?error=sesion');
    exit;
}

$tipo = trim($_POST['tipo_servicio'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$fechaIngreso = trim($_POST['fecha_ingreso'] ?? '');
$fechaEstimada = trim($_POST['fecha_entrega_estimada'] ?? '');
$estado = trim($_POST['estado'] ?? '');
$costoTexto = trim($_POST['costo'] ?? '');
$idVehiculo = (int) ($_POST['id_vehiculo'] ?? 0);
$idTecnico = (int) ($_POST['id_tecnico'] ?? 0);
$tiposPermitidos = ['Mantenimiento', 'Reparacion', 'Revision general', 'Diagnostico'];
$estadosPermitidos = ['Pendiente'];
$fechaValida = static function (string $fecha): bool {
    $date = DateTime::createFromFormat('!Y-m-d', $fecha);
    return $date !== false && $date->format('Y-m-d') === $fecha;
};

if (
    $tipo === '' ||
    !in_array($tipo, $tiposPermitidos, true) ||
    $descripcion === '' ||
    !$fechaValida($fechaIngreso) ||
    !in_array($estado, $estadosPermitidos, true) ||
    $idVehiculo <= 0 ||
    $idTecnico <= 0 ||
    !is_numeric($costoTexto) ||
    !is_finite((float) $costoTexto) ||
    (float) $costoTexto < 0 ||
    (float) $costoTexto > 99999999.99 ||
    ($fechaEstimada !== '' && (!$fechaValida($fechaEstimada) || $fechaEstimada < $fechaIngreso))
) {
    header('Location: registro_servicios.php?error=datos');
    exit;
}

$idUsuario = (int) $_SESSION['id_usuario'];
$stmt = $conn->prepare('SELECT id_recepcionista FROM recepcionista WHERE id_usuario = ?');
$stmt->bind_param('i', $idUsuario);
$stmt->execute();
$recepcionista = $stmt->get_result()->fetch_assoc();

if (!$recepcionista) {
    header('Location: registro_servicios.php?error=perfil');
    exit;
}

$idRecepcionista = (int) $recepcionista['id_recepcionista'];
$stmt = $conn->prepare('SELECT id_vehiculo, id_cliente FROM vehiculo WHERE id_vehiculo = ?');
$stmt->bind_param('i', $idVehiculo);
$stmt->execute();
$vehiculo = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare('SELECT id_tecnico FROM tecnico WHERE id_tecnico = ?');
$stmt->bind_param('i', $idTecnico);
$stmt->execute();
$tecnicoExiste = $stmt->get_result()->num_rows === 1;

if (!$vehiculo || !$tecnicoExiste) {
    header('Location: registro_servicios.php?error=relacion');
    exit;
}

$conn->begin_transaction();
try {
    $costo = (float) $costoTexto;
    $stmt = $conn->prepare(
        'INSERT INTO servicio (tipo_servicio, descripcion, fecha_ingreso, fecha_entrega_estimada, estado, costo, id_vehiculo, id_tecnico, id_recepcionista)
         VALUES (?, ?, ?, NULLIF(?, \'\'), ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'sssssdiii',
        $tipo,
        $descripcion,
        $fechaIngreso,
        $fechaEstimada,
        $estado,
        $costo,
        $idVehiculo,
        $idTecnico,
        $idRecepcionista
    );
    $stmt->execute();
    $idServicio = (int) $conn->insert_id;

    $motivoAsignacion = 'Asignación inicial al registrar el servicio.';
    $stmt = $conn->prepare(
        "INSERT INTO historial_asignacion_tecnico
         (id_servicio,id_tecnico_anterior,id_tecnico_nuevo,id_recepcionista,motivo)
         VALUES (?,NULL,?,?,?)"
    );
    $stmt->bind_param('iiis', $idServicio, $idTecnico, $idRecepcionista, $motivoAsignacion);
    $stmt->execute();

    $comentarioInicial = 'Ingreso del servicio. Trabajo solicitado: ' . $descripcion;
    $stmt = $conn->prepare(
        'INSERT INTO seguimiento (fecha, fecha_hora, estado, comentario, id_servicio, id_tecnico, aprendiz)
         VALUES (CURDATE(), NOW(), ?, ?, ?, NULL, \'\')'
    );
    $stmt->bind_param('ssi', $estado, $comentarioInicial, $idServicio);
    $stmt->execute();

    $mensajeNotificacion = 'Se registró el ingreso del servicio #' . $idServicio . '. Estado inicial: ' . $estado . '.';
    $estadoNotificacion = 'Interna';
    $idCliente = (int) $vehiculo['id_cliente'];
    $stmt = $conn->prepare(
        'INSERT INTO notificacion (mensaje, fecha_envio, estado, id_cliente, id_servicio)
         VALUES (?, NOW(), ?, ?, ?)'
    );
    $stmt->bind_param('ssii', $mensajeNotificacion, $estadoNotificacion, $idCliente, $idServicio);
    $stmt->execute();

    if (!registrarActividadSistema(
        $conn,
        $idUsuario,
        'servicio_creado',
        'servicio',
        $idServicio,
        $idServicio,
        'Recepción registró un nuevo servicio.'
    )) {
        throw new mysqli_sql_exception('No se pudo registrar la auditoría del servicio.');
    }
    $conn->commit();
    header('Location: registro_servicios.php?mensaje=ok');
    exit;
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    error_log('Error al registrar servicio: ' . $error->getMessage());
    header('Location: registro_servicios.php?error=guardar');
    exit;
}

?>
