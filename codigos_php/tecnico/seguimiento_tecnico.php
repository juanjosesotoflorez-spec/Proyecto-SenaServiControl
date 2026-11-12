<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'tecnico') {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';
require_once dirname(__DIR__) . '/compartido/auditoria_helper.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$tecnico = null;
$tecnicosDisponibles = [];
$stmt = $conn->prepare('SELECT id_tecnico, nombre, apellido FROM tecnico WHERE id_usuario = ?');
$stmt->bind_param('i', $_SESSION['id_usuario']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $tecnico = $result->fetch_assoc();
}
$tecnicosDisponibles = $conn->query(
    'SELECT id_tecnico, nombre, apellido FROM tecnico ORDER BY nombre, apellido'
)->fetch_all(MYSQLI_ASSOC);

$servicios = [];
$antecedentes = [];
if ($tecnico) {
    $stmt = $conn->prepare(
        "SELECT s.id_servicio, s.tipo_servicio, s.estado, v.placa, c.nombre AS cliente
         FROM servicio s
         JOIN vehiculo v ON s.id_vehiculo = v.id_vehiculo
         JOIN cliente c ON v.id_cliente = c.id_cliente
         WHERE s.estado IS NOT NULL AND s.estado NOT IN ('Finalizado','Entregado')
         ORDER BY s.id_servicio DESC"
    );
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $servicios[] = $row;
    }

    $historyStmt = $conn->prepare(
        "SELECT actual.id_servicio AS servicio_asignado, anterior.id_servicio,
                anterior.tipo_servicio, anterior.descripcion, anterior.estado,
                anterior.fecha_ingreso, v.placa
         FROM servicio actual
         JOIN vehiculo v ON v.id_vehiculo = actual.id_vehiculo
         JOIN servicio anterior ON anterior.id_vehiculo = actual.id_vehiculo
         WHERE actual.estado IS NOT NULL
           AND actual.estado NOT IN ('Finalizado','Entregado')
           AND (anterior.fecha_ingreso < actual.fecha_ingreso
                OR (anterior.fecha_ingreso = actual.fecha_ingreso AND anterior.id_servicio < actual.id_servicio))
         ORDER BY actual.id_servicio DESC, anterior.fecha_ingreso ASC, anterior.id_servicio ASC"
    );
    $historyStmt->execute();
    $historyResult = $historyStmt->get_result();
    while ($row = $historyResult->fetch_assoc()) {
        $antecedentes[] = $row;
    }
}

$message = '';
$messageType = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'estado';
    $accion = is_string($accion) ? $accion : '';
    $serviceInput = $_POST['id_servicio'] ?? '';
    $performerInput = $_POST['id_tecnico_realizo'] ?? '';
    $stateInput = $_POST['estado'] ?? '';
    $apprenticeInput = $_POST['aprendiz'] ?? '';
    $commentInput = $_POST['comentario'] ?? '';
    $selectedServicio = is_string($serviceInput) ? filter_var($serviceInput, FILTER_VALIDATE_INT) : false;
    $idTecnicoRealizo = is_string($performerInput) ? filter_var($performerInput, FILTER_VALIDATE_INT) : false;
    $estado = is_string($stateInput) ? trim($stateInput) : '';
    $aprendiz = is_string($apprenticeInput) ? trim($apprenticeInput) : '';
    $comentario = is_string($commentInput) ? trim($commentInput) : '';
    $token = $_POST['csrf_token'] ?? '';
    $estadosPermitidos = ['Pendiente', 'En proceso', 'Finalizado'];
    $accionValida = $accion === 'actividad'
        ? $estado === ''
        : ($accion === 'estado' && in_array($estado, $estadosPermitidos, true));

    if (
        !is_string($token) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token) ||
        !$tecnico ||
        $selectedServicio === false || $selectedServicio <= 0 ||
        $idTecnicoRealizo === false || $idTecnicoRealizo <= 0 ||
        !$accionValida ||
        trim($comentario) === '' ||
        strlen($aprendiz) > 150 ||
        strlen($comentario) > 10000
    ) {
        $message = 'Revisa la sesión y todos los campos obligatorios.';
    } else {
        $conn->begin_transaction();
        try {
            $check = $conn->prepare(
                "SELECT s.id_servicio, s.estado, c.id_cliente
                 FROM servicio s
                 JOIN vehiculo v ON s.id_vehiculo = v.id_vehiculo
                 JOIN cliente c ON v.id_cliente = c.id_cliente
                 WHERE s.id_servicio = ?
                 FOR UPDATE"
            );
            $check->bind_param('i', $selectedServicio);
            $check->execute();
            $servicio = $check->get_result()->fetch_assoc();
            $performerCheck = $conn->prepare(
                'SELECT id_tecnico FROM tecnico WHERE id_tecnico = ? LIMIT 1'
            );
            $performerCheck->bind_param('i', $idTecnicoRealizo);
            $performerCheck->execute();
            $tecnicoRealizadorExiste = $performerCheck->get_result()->num_rows === 1;

            if (
                !$servicio ||
                !$tecnicoRealizadorExiste ||
                $servicio['estado'] === null ||
                trim((string) $servicio['estado']) === '' ||
                in_array($servicio['estado'], ['Finalizado', 'Entregado'], true)
            ) {
                $conn->rollback();
                $message = 'Verifica que el servicio esté activo y que el técnico seleccionado exista.';
            } else {
                if ($accion === 'actividad' && $servicio['estado'] !== 'En proceso') {
                    throw new DomainException('Las actividades técnicas se registran cuando el servicio está En proceso.');
                }
                if ($accion === 'estado' && $estado === 'Pendiente' && $servicio['estado'] !== 'Pendiente') {
                    throw new DomainException('El estado no puede retroceder de En proceso a Pendiente.');
                }
                $requirements = $conn->prepare(
                    'SELECT
                       (SELECT COUNT(*) FROM mantenimiento m WHERE m.id_servicio=? AND (m.estado_autorizacion IS NULL OR m.estado_autorizacion<>\'Aprobada\')) AS mantenimiento_no_autorizado,
                       (SELECT COUNT(*) FROM mantenimiento m WHERE m.id_servicio=? AND (m.estado_trabajo IS NULL OR m.estado_trabajo<>\'Completado\')) AS mantenimiento_pendiente,
                       (SELECT COUNT(*) FROM reparacion r WHERE r.id_servicio=? AND (r.estado_presupuesto IS NULL OR r.estado_presupuesto<>\'Aprobada\')) AS reparacion_no_autorizada,
                       (SELECT COUNT(*) FROM reparacion r WHERE r.id_servicio=? AND (r.estado_presupuesto IS NULL OR r.estado_presupuesto<>\'Aprobada\' OR r.estado IS NULL OR r.estado<>\'Completada\')) AS reparacion_pendiente'
                );
                $requirements->bind_param('iiii', $selectedServicio, $selectedServicio, $selectedServicio, $selectedServicio);
                $requirements->execute();
                $pending = $requirements->get_result()->fetch_assoc();
                if (
                    ($accion === 'actividad' || in_array($estado, ['En proceso', 'Finalizado'], true)) &&
                    ((int)$pending['mantenimiento_no_autorizado'] > 0 ||
                     (int)$pending['reparacion_no_autorizada'] > 0)
                ) {
                    throw new DomainException('No puede iniciar el trabajo hasta que el cliente autorice el mantenimiento o presupuesto.');
                }
                if ($estado === 'Finalizado' && (
                    (int)$pending['mantenimiento_pendiente'] > 0 ||
                    (int)$pending['reparacion_pendiente'] > 0
                )) {
                    throw new DomainException('No se puede finalizar: hay actividades de mantenimiento o reparación pendientes.');
                }

                $estadoSeguimiento = $accion === 'actividad' ? (string)$servicio['estado'] : $estado;
                $insert = $conn->prepare(
                    'INSERT INTO seguimiento (fecha, fecha_hora, estado, comentario, id_servicio, id_tecnico, aprendiz)
                     VALUES (CURDATE(), NOW(), ?, ?, ?, ?, ?)'
                );
                $insert->bind_param('ssiis', $estadoSeguimiento, $comentario, $selectedServicio, $idTecnicoRealizo, $aprendiz);
                $insert->execute();

                if ($accion === 'estado') {
                    $update = $conn->prepare(
                        "UPDATE servicio SET estado = ?
                         WHERE id_servicio = ? AND estado NOT IN ('Finalizado','Entregado')"
                    );
                    $update->bind_param('si', $estado, $selectedServicio);
                    $update->execute();
                }

                $mensajeNotificacion = 'Servicio #' . $selectedServicio . ': ' . $estadoSeguimiento . '. ' . $comentario;
                $notification = $conn->prepare(
                    "INSERT INTO notificacion (mensaje, fecha_envio, estado, id_cliente, id_servicio)
                     VALUES (?, NOW(), 'Interna', ?, ?)"
                );
                $idCliente = (int) $servicio['id_cliente'];
                $notification->bind_param('sii', $mensajeNotificacion, $idCliente, $selectedServicio);
                $notification->execute();

                if (!registrarActividadSistema(
                    $conn,
                    (int)$_SESSION['id_usuario'],
                    $accion === 'actividad' ? 'actividad_tecnica' : 'seguimiento_tecnico',
                    'servicio',
                    $selectedServicio,
                    $selectedServicio,
                    $accion === 'actividad'
                        ? 'El técnico registró una actividad en el servicio.'
                        : 'El técnico registró un avance con estado ' . $estado . '.'
                )) {
                    throw new mysqli_sql_exception('No se pudo registrar la auditoría del seguimiento.');
                }
                $conn->commit();
                $message = $accion === 'actividad'
                    ? 'Actividad técnica registrada y notificación interna creada.'
                    : 'Seguimiento registrado y notificación interna creada.';
                $messageType = 'success';
                foreach ($servicios as $index => $servicioAsignado) {
                    if ((int) $servicioAsignado['id_servicio'] === $selectedServicio) {
                        if ($accion === 'estado' && $estado === 'Finalizado') {
                            unset($servicios[$index]);
                        } elseif ($accion === 'estado') {
                            $servicios[$index]['estado'] = $estado;
                        }
                        break;
                    }
                }
                $servicios = array_values($servicios);
            }
        } catch (mysqli_sql_exception $error) {
            $conn->rollback();
            error_log('Error al guardar seguimiento: ' . $error->getMessage());
            $message = 'No se pudo guardar el seguimiento. No se enviaron mensajes externos.';
        } catch (DomainException $error) {
            $conn->rollback();
            $message = $error->getMessage();
        }
    }
}

$seguimientos = [];
if ($tecnico) {
    $stmt = $conn->prepare(
        'SELECT sc.id_seguimiento, sc.id_servicio, sc.aprendiz, sc.comentario, sc.estado, sc.fecha, sc.fecha_hora,
                s.tipo_servicio, v.placa, c.nombre AS cliente,
                CONCAT(t.nombre, \' \', t.apellido) AS tecnico_realizador
         FROM seguimiento sc
         JOIN servicio s ON sc.id_servicio = s.id_servicio
         JOIN vehiculo v ON s.id_vehiculo = v.id_vehiculo
         JOIN cliente c ON v.id_cliente = c.id_cliente
         LEFT JOIN tecnico t ON t.id_tecnico = sc.id_tecnico
         ORDER BY sc.fecha DESC, sc.id_seguimiento DESC
         LIMIT 30'
    );
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $seguimientos[] = $row;
    }
}

$nombre_tecnico = $tecnico ? $tecnico['nombre'] : 'técnico';
$idTecnicoSesion = (int) ($tecnico['id_tecnico'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seguimiento técnico</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
  <script src="../js/form-validation.js?v=20261109-confirm" defer></script>
</head>
<body class="admin-body module-page module-workshop-tracking">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4>Técnico</h4></div>
      <nav class="sidebar-nav">
        <a href="../../index.html">Volver al panel</a>
        <a href="tecnico_servicios.php">Servicios del taller</a>
        <a href="seguimiento_tecnico.php" class="active">Seguimiento</a>
        <a href="mantenimientos.php">Mantenimientos</a>
        <a href="reparaciones.php">Reparaciones</a>
        <a href="asignaciones_tecnicos.php">Asignaciones</a>
        <a href="tecnico_reparaciones.php">Reparaciones</a>
        <a href="logout.php">Salir</a>
      </nav>
    </aside>
    <main class="admin-content">
      <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
          <h2 class="section-title">Registro de seguimiento</h2>
          <p class="texto-gris mb-0">Consulta cualquier servicio activo, registra su avance y selecciona qué técnico realizó el trabajo.</p>
        </div>
      </div>

      <?php if ($message !== ''): ?>
        <div class="alert alert-<?= $messageType ?> alerta"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <div class="dashboard-panel mb-4">
        <form method="POST" class="row g-3">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <input type="hidden" name="accion" value="estado">
          <div class="col-md-6">
            <label class="form-label">Servicio</label>
            <select name="id_servicio" class="form-select" required>
              <option value="">Seleccione un servicio</option>
              <?php foreach ($servicios as $s): ?>
                <option value="<?= $s['id_servicio'] ?>">#<?= $s['id_servicio'] ?> - <?= htmlspecialchars($s['tipo_servicio']) ?> / <?= htmlspecialchars($s['placa']) ?> / <?= htmlspecialchars($s['cliente']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="tecnico_realizo_estado">Técnico que realizó el trabajo</label>
            <select name="id_tecnico_realizo" id="tecnico_realizo_estado" class="form-select" required>
              <option value="">Seleccione un técnico</option>
              <?php foreach ($tecnicosDisponibles as $tecnicoDisponible): ?>
                <option value="<?= (int) $tecnicoDisponible['id_tecnico'] ?>" <?= (int) $tecnicoDisponible['id_tecnico'] === $idTecnicoSesion ? 'selected' : '' ?>>
                  <?= htmlspecialchars($tecnicoDisponible['nombre'] . ' ' . $tecnicoDisponible['apellido']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Aprendiz que lo realizó</label>
            <input type="text" name="aprendiz" maxlength="150" class="form-control" placeholder="Nombre del aprendiz (si aplica)">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="estado">Estado actual</label>
            <select name="estado" id="estado" class="form-select" required>
              <option value="">Seleccione</option>
              <option value="Pendiente">Pendiente</option>
              <option value="En proceso">En proceso</option>
              <option value="Finalizado">Finalizado</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Comentario de seguimiento</label>
            <textarea name="comentario" class="form-control" rows="4" placeholder="Describe el avance realizado..." required></textarea>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-verde rounded-pill">Guardar seguimiento</button>
          </div>
        </form>
      </div>

      <div class="dashboard-panel mb-4">
        <h5 class="section-title">Registrar actividad técnica</h5>
        <p class="text-muted">Añade una actividad al historial sin cambiar el estado del servicio. Solo disponible en servicios En proceso.</p>
        <form method="POST" class="row g-3">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <input type="hidden" name="accion" value="actividad">
          <div class="col-md-6">
            <label class="form-label">Servicio en proceso</label>
            <select name="id_servicio" class="form-select" required>
              <option value="">Seleccione un servicio</option>
              <?php foreach ($servicios as $s): if ($s['estado'] === 'En proceso'): ?>
                <option value="<?= (int)$s['id_servicio'] ?>">#<?= (int)$s['id_servicio'] ?> · <?= htmlspecialchars($s['tipo_servicio'].' / '.$s['placa']) ?></option>
              <?php endif; endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="tecnico_realizo_actividad">Técnico que realizó la actividad</label>
            <select name="id_tecnico_realizo" id="tecnico_realizo_actividad" class="form-select" required>
              <option value="">Seleccione un técnico</option>
              <?php foreach ($tecnicosDisponibles as $tecnicoDisponible): ?>
                <option value="<?= (int) $tecnicoDisponible['id_tecnico'] ?>" <?= (int) $tecnicoDisponible['id_tecnico'] === $idTecnicoSesion ? 'selected' : '' ?>>
                  <?= htmlspecialchars($tecnicoDisponible['nombre'] . ' ' . $tecnicoDisponible['apellido']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Aprendiz (si aplica)</label><input type="text" name="aprendiz" maxlength="150" class="form-control"></div>
          <div class="col-12"><label class="form-label">Actividad realizada</label><textarea name="comentario" class="form-control" rows="3" maxlength="10000" required></textarea></div>
          <div class="col-12"><button type="submit" class="btn btn-outline-verde rounded-pill">Guardar actividad</button></div>
        </form>
      </div>

      <div class="dashboard-panel mb-4">
        <h5 class="section-title">Antecedentes de vehículos con servicios activos</h5>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Servicio actual</th><th>Vehículo</th><th>Servicio anterior</th><th>Fecha</th><th>Estado</th><th>Descripción</th></tr></thead>
            <tbody>
              <?php foreach ($antecedentes as $antecedente): ?>
                <tr>
                  <td>#<?= (int) $antecedente['servicio_asignado'] ?></td>
                  <td><?= htmlspecialchars($antecedente['placa']) ?></td>
                  <td>#<?= (int) $antecedente['id_servicio'] ?> · <?= htmlspecialchars($antecedente['tipo_servicio']) ?></td>
                  <td><?= htmlspecialchars($antecedente['fecha_ingreso']) ?></td>
                  <td><?= htmlspecialchars($antecedente['estado'] ?? 'Sin estado') ?></td>
                  <td><?= nl2br(htmlspecialchars($antecedente['descripcion'] ?? '')) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$antecedentes): ?><tr><td colspan="6" class="text-muted">No hay antecedentes previos para los servicios activos.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="dashboard-panel">
        <h5 class="section-title mb-3">Últimos seguimientos</h5>
        <?php if ($seguimientos): ?>
          <div class="row g-3">
            <?php foreach ($seguimientos as $track): ?>
              <div class="col-md-6">
                <div class="card card-custom h-100">
                  <div class="card-body">
                    <h6 class="fw-bold mb-1">Servicio #<?= $track['id_servicio'] ?> - <?= htmlspecialchars($track['placa']) ?></h6>
                    <p class="mb-1 text-muted small">Cliente: <?= htmlspecialchars($track['cliente']) ?> · <?= htmlspecialchars($track['tipo_servicio']) ?></p>
                    <span class="badge bg-success-subtle text-success mb-2"><?= htmlspecialchars($track['estado']) ?></span>
                    <p class="mb-1"><strong>Técnico:</strong> <?= htmlspecialchars($track['tecnico_realizador'] ?? 'No especificado') ?></p>
                    <p class="mb-1"><strong>Aprendiz:</strong> <?= htmlspecialchars($track['aprendiz']) ?></p>
                    <p class="mb-2"><?= nl2br(htmlspecialchars($track['comentario'])) ?></p>
                    <small class="text-muted">Registrado el <?= htmlspecialchars($track['fecha_hora'] ?? $track['fecha']) ?></small>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="mb-0 text-muted">Todavía no hay seguimientos registrados.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>
