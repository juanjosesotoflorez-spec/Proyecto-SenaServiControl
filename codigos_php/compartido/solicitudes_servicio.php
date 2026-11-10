<?php
session_start();
$rol = $_SESSION['rol'] ?? '';
if (!isset($_SESSION['id_usuario']) || !in_array($rol, ['administrador', 'recepcionista', 'tecnico'], true)) {
    header('Location: login.php');
    exit;
}

require dirname(__DIR__, 2) . '/db.php';
require_once dirname(__DIR__) . '/compartido/auditoria_helper.php';

$csrfToken = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$idUsuario = (int) $_SESSION['id_usuario'];
$esAdministrador = $rol === 'administrador';
$esTecnico = $rol === 'tecnico';
$panel = $esAdministrador
    ? 'dashboard_admin.php'
    : ($esTecnico ? 'dashboard_tecnico.php' : 'dashboard_recepcionista.php');
$message = '';
$messageType = 'danger';
$idTecnicoSesion = 0;

if ($esTecnico) {
    $stmt = $conn->prepare('SELECT id_tecnico FROM tecnico WHERE id_usuario = ? LIMIT 1');
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $idTecnicoSesion = (int) ($stmt->get_result()->fetch_assoc()['id_tecnico'] ?? 0);
    if ($idTecnicoSesion <= 0) {
        http_response_code(403);
        exit('La cuenta no tiene un perfil técnico válido.');
    }
} elseif ($rol === 'recepcionista') {
    $stmt = $conn->prepare('SELECT id_recepcionista FROM recepcionista WHERE id_usuario = ? LIMIT 1');
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    if ((int) ($stmt->get_result()->fetch_assoc()['id_recepcionista'] ?? 0) <= 0) {
        http_response_code(403);
        exit('La cuenta no tiene un perfil de recepción válido.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$esTecnico) {
        http_response_code(403);
        exit('Solo un técnico puede aceptar solicitudes de servicio.');
    }

    $token = $_POST['csrf_token'] ?? '';
    $requestInput = $_POST['id_solicitud'] ?? '';
    $idSolicitud = is_string($requestInput) ? filter_var($requestInput, FILTER_VALIDATE_INT) : false;
    $idTecnico = $idTecnicoSesion;

    if (!is_string($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $message = 'La sesión venció. Recarga la página e inténtalo nuevamente.';
    } elseif (
        $idSolicitud === false || $idSolicitud <= 0 ||
        $idTecnico <= 0
    ) {
        $message = 'Selecciona una solicitud válida.';
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare(
                "SELECT id_vehiculo, tipo_servicio, descripcion, fecha_solicitada
                 FROM solicitud_servicio
                 WHERE id_solicitud = ? AND estado = 'Pendiente' AND id_servicio IS NULL
                 FOR UPDATE"
            );
            $stmt->bind_param('i', $idSolicitud);
            $stmt->execute();
            $solicitud = $stmt->get_result()->fetch_assoc();

            $stmt = $conn->prepare('SELECT id_tecnico FROM tecnico WHERE id_tecnico = ? LIMIT 1');
            $stmt->bind_param('i', $idTecnico);
            $stmt->execute();
            $tecnicoExiste = $stmt->get_result()->num_rows === 1;

            if (!$solicitud) {
                $message = 'La solicitud ya fue atendida o no está disponible.';
            } elseif (!$tecnicoExiste) {
                $message = 'Tu perfil técnico ya no está disponible.';
            } else {
                $estadoServicio = 'Pendiente';
                $fechaIngreso = date('Y-m-d');
                $costo = 0.0;
                $tipoServicio = $solicitud['tipo_servicio'];
                $descripcionServicio = $solicitud['descripcion'];
                $fechaCita = $solicitud['fecha_solicitada'];
                $idVehiculo = (int) $solicitud['id_vehiculo'];
                $idRecepcionista = null;
                $stmt = $conn->prepare(
                    'INSERT INTO servicio
                     (tipo_servicio, descripcion, fecha_ingreso, fecha_cita, estado, costo, id_vehiculo, id_tecnico, id_recepcionista)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param(
                    'sssssdiii',
                    $tipoServicio,
                    $descripcionServicio,
                    $fechaIngreso,
                    $fechaCita,
                    $estadoServicio,
                    $costo,
                    $idVehiculo,
                    $idTecnico,
                    $idRecepcionista
                );
                $stmt->execute();
                $idServicio = (int) $conn->insert_id;

                $motivo = 'Servicio creado al aprobar la solicitud del cliente #' . $idSolicitud . '.';
                $stmt = $conn->prepare(
                    'INSERT INTO historial_asignacion_tecnico
                     (id_servicio, id_tecnico_anterior, id_tecnico_nuevo, id_recepcionista, motivo, estado_aceptacion, fecha_aceptacion)
                     VALUES (?, NULL, ?, NULL, ?, \'Aceptada\', NOW())'
                );
                $stmt->bind_param('iis', $idServicio, $idTecnico, $motivo);
                $stmt->execute();

                $comentario = 'Solicitud de servicio aprobada. Fecha preferida por el cliente: '
                    . $solicitud['fecha_solicitada'] . '. ' . $solicitud['descripcion'];
                $stmt = $conn->prepare(
                    'INSERT INTO seguimiento (fecha, fecha_hora, estado, comentario, id_servicio, id_tecnico, aprendiz)
                     VALUES (?, NOW(), ?, ?, ?, NULL, \'\')'
                );
                $stmt->bind_param('sssi', $fechaIngreso, $estadoServicio, $comentario, $idServicio);
                $stmt->execute();

                $stmt = $conn->prepare(
                    "UPDATE solicitud_servicio
                     SET estado = 'Aceptada', id_servicio = ?
                     WHERE id_solicitud = ? AND estado = 'Pendiente' AND id_servicio IS NULL"
                );
                $stmt->bind_param('ii', $idServicio, $idSolicitud);
                $stmt->execute();
                if ($stmt->affected_rows !== 1) {
                    throw new mysqli_sql_exception('La solicitud cambió mientras se procesaba.');
                }

                $stmt = $conn->prepare(
                    'SELECT v.id_cliente FROM solicitud_servicio ss
                     JOIN vehiculo v ON v.id_vehiculo = ss.id_vehiculo
                     WHERE ss.id_solicitud = ?'
                );
                $stmt->bind_param('i', $idSolicitud);
                $stmt->execute();
                $idCliente = (int) $stmt->get_result()->fetch_assoc()['id_cliente'];

                $mensaje = 'Tu solicitud #' . $idSolicitud . ' fue aceptada como servicio #' . $idServicio
                    . '. Fecha preferida: ' . $solicitud['fecha_solicitada'] . '.';
                $estadoNotificacion = 'Interna';
                $stmt = $conn->prepare(
                    'INSERT INTO notificacion (mensaje, fecha_envio, estado, id_cliente, id_servicio)
                     VALUES (?, NOW(), ?, ?, ?)'
                );
                $stmt->bind_param('ssii', $mensaje, $estadoNotificacion, $idCliente, $idServicio);
                $stmt->execute();

                if (!registrarActividadSistema(
                    $conn,
                    $idUsuario,
                    'solicitud_servicio_aceptada',
                    'solicitud_servicio',
                    (int) $idSolicitud,
                    $idServicio,
                    'La solicitud del cliente se convirtió en un servicio asignado.'
                )) {
                    throw new mysqli_sql_exception('No se pudo registrar la auditoría de la solicitud.');
                }

                $conn->commit();
                $message = 'Solicitud aceptada y convertida en servicio #' . $idServicio . '.';
                $messageType = 'success';
            }

            if ($conn->in_transaction) {
                $conn->rollback();
            }
        } catch (mysqli_sql_exception $error) {
            if ($conn->in_transaction) {
                $conn->rollback();
            }
            error_log('Error al aceptar una solicitud de servicio: ' . $error->getMessage());
            $message = 'No se pudo procesar la solicitud. Verifica las asignaciones e inténtalo nuevamente.';
        }
    }
}

$solicitudes = [];
$result = $conn->query(
    "SELECT ss.id_solicitud, ss.tipo_servicio, ss.descripcion, ss.fecha_solicitada,
            ss.fecha_solicitud, ss.estado, ss.id_servicio, v.placa, v.marca, v.modelo,
            c.nombre AS cliente, c.apellido, c.documento, c.telefono
     FROM solicitud_servicio ss
     JOIN vehiculo v ON v.id_vehiculo = ss.id_vehiculo
     JOIN cliente c ON c.id_cliente = v.id_cliente
     ORDER BY (ss.estado = 'Pendiente') DESC, ss.fecha_solicitud DESC, ss.id_solicitud DESC"
);
while ($row = $result->fetch_assoc()) {
    $solicitudes[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitudes de servicio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
  <script src="../js/form-validation.js?v=20261109-confirm" defer></script>
</head>
<body class="admin-body module-page module-service-requests">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4><?= $esAdministrador ? 'Administración' : ($esTecnico ? 'Técnico' : 'Recepción') ?></h4></div>
      <nav class="sidebar-nav">
        <a href="<?= htmlspecialchars($panel) ?>">Volver al panel</a>
        <a href="solicitudes_servicio.php" class="active">Solicitudes de servicio</a>
        <a href="<?= $esAdministrador ? 'admin_servicios.php' : ($esTecnico ? 'tecnico_servicios.php' : 'recepcionista_servicios.php') ?>">
          <?= $esTecnico ? 'Servicios del taller' : 'Servicios' ?>
        </a>
        <a href="logout.php">Cerrar sesión</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h1 class="section-title">Solicitudes de servicio</h1>
      <p class="text-muted"><?= $esTecnico
          ? 'Revisa las solicitudes y acepta las que atenderás; al aceptarlas, el servicio quedará asignado a tu perfil.'
          : 'Consulta las solicitudes de servicio. La aceptación y asignación corresponden exclusivamente a los técnicos.' ?></p>
      <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?> alerta" role="status"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>
      <section class="dashboard-panel">
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Solicitud</th><th>Vehículo y cliente</th><th>Fecha preferida</th><th>Descripción</th><th>Estado</th><th>Acción</th></tr></thead>
            <tbody>
              <?php foreach ($solicitudes as $solicitud): ?>
                <tr>
                  <td>#<?= (int) $solicitud['id_solicitud'] ?><br><small><?= htmlspecialchars($solicitud['fecha_solicitud']) ?></small></td>
                  <td><strong><?= htmlspecialchars($solicitud['placa']) ?></strong><br>
                    <?= htmlspecialchars($solicitud['marca'] . ' ' . $solicitud['modelo']) ?><br>
                    <small><?= htmlspecialchars($solicitud['cliente'] . ' ' . $solicitud['apellido'] . ' · ' . $solicitud['documento']) ?></small>
                  </td>
                  <td><?= htmlspecialchars($solicitud['fecha_solicitada']) ?></td>
                  <td><strong><?= htmlspecialchars($solicitud['tipo_servicio']) ?></strong><br><?= nl2br(htmlspecialchars($solicitud['descripcion'])) ?></td>
                  <td><?= htmlspecialchars($solicitud['estado']) ?>
                    <?php if ($solicitud['id_servicio'] !== null): ?><br><a href="historial_servicios.php?servicio=<?= (int) $solicitud['id_servicio'] ?>">Servicio #<?= (int) $solicitud['id_servicio'] ?></a><?php endif; ?>
                  </td>
                  <td>
                    <?php if ($solicitud['estado'] === 'Pendiente' && $solicitud['id_servicio'] === null): ?>
                      <?php if ($esTecnico): ?>
                        <form method="POST" class="d-grid gap-2">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                          <input type="hidden" name="id_solicitud" value="<?= (int) $solicitud['id_solicitud'] ?>">
                          <button class="btn btn-sm btn-verde rounded-pill" type="submit">Aceptar solicitud</button>
                        </form>
                      <?php else: ?>
                        <span class="text-muted small">Pendiente de aceptación por un técnico.</span>
                      <?php endif; ?>
                    <?php else: ?>—<?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$solicitudes): ?><tr><td colspan="6" class="text-muted">No hay solicitudes registradas.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
      <a href="<?= htmlspecialchars($panel) ?>" class="btn btn-outline-verde rounded-pill mt-3">Volver al panel</a>
    </main>
  </div>
</body>
</html>
