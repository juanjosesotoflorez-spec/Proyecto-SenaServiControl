<?php
session_start();
$rol = $_SESSION['rol'] ?? '';
if (!isset($_SESSION['id_usuario']) || !in_array($rol, ['administrador', 'recepcionista', 'tecnico', 'cliente'], true)) {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';
require_once dirname(__DIR__) . '/compartido/auditoria_helper.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$clienteId = null;
$tecnicoId = null;
if ($rol === 'cliente') {
    $stmt = $conn->prepare('SELECT id_cliente FROM cliente WHERE id_usuario = ?');
    $stmt->bind_param('i', $_SESSION['id_usuario']);
    $stmt->execute();
    $clienteId = (int) ($stmt->get_result()->fetch_assoc()['id_cliente'] ?? 0);
}
if ($rol === 'tecnico') {
    $stmt = $conn->prepare('SELECT id_tecnico FROM tecnico WHERE id_usuario = ?');
    $stmt->bind_param('i', $_SESSION['id_usuario']);
    $stmt->execute();
    $tecnicoId = (int) ($stmt->get_result()->fetch_assoc()['id_tecnico'] ?? 0);
}

$mensaje = '';
$tipoMensaje = 'danger';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $mensaje = 'La sesión venció. Recarga la página.';
    } elseif ($rol === 'cliente' && ($_POST['action'] ?? '') === 'solicitar') {
        $idServicio = (int)($_POST['id_servicio'] ?? 0);
        $tipo = trim($_POST['tipo_mantenimiento'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');
        $tipos = ['Preventivo', 'Correctivo', 'General', 'Alineación'];
        $stmt = $conn->prepare(
            "SELECT s.id_servicio FROM servicio s
             JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
             LEFT JOIN mantenimiento m ON m.id_servicio=s.id_servicio
             WHERE s.id_servicio=? AND v.id_cliente=? AND s.tipo_servicio='Mantenimiento'
               AND s.estado NOT IN ('Finalizado','Entregado') AND m.id_mantenimiento IS NULL"
        );
        $stmt->bind_param('ii', $idServicio, $clienteId);
        $stmt->execute();
        if (!in_array($tipo, $tipos, true) || $observaciones === '' || !$stmt->get_result()->fetch_assoc()) {
            $mensaje = 'Selecciona un servicio propio disponible e indica el mantenimiento solicitado.';
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO mantenimiento (tipo_mantenimiento,observaciones,estado_autorizacion,estado_trabajo,id_servicio)
                 VALUES (?, ?, NULL, NULL, ?)'
            );
            $stmt->bind_param('ssi', $tipo, $observaciones, $idServicio);
            if ($stmt->execute()) {
                $auditOk = registrarActividadSistema(
                    $conn,
                    (int)$_SESSION['id_usuario'],
                    'mantenimiento_solicitado',
                    'mantenimiento',
                    (int)$stmt->insert_id,
                    $idServicio,
                    'El cliente solicitó un mantenimiento.'
                );
                $mensaje = $auditOk
                    ? 'Solicitud registrada. Recepción debe programarla y luego podrás autorizarla.'
                    : 'La solicitud se guardó, pero no pudo registrarse la auditoría.';
                $tipoMensaje = $auditOk ? 'success' : 'warning';
            } else {
                $mensaje = 'No se pudo registrar la solicitud.';
            }
        }
    } elseif ($rol === 'recepcionista' && ($_POST['action'] ?? '') === 'programar') {
        $idMantenimiento = (int)($_POST['id_mantenimiento'] ?? 0);
        $fecha = trim($_POST['fecha_programacion'] ?? '');
        $fechaObj = DateTime::createFromFormat('!Y-m-d', $fecha);
        if ($idMantenimiento <= 0 || !$fechaObj || $fechaObj->format('Y-m-d') !== $fecha || $fecha < date('Y-m-d')) {
            $mensaje = 'La fecha de programación debe ser válida y no anterior a hoy.';
        } else {
            $stmt = $conn->prepare(
                "UPDATE mantenimiento m JOIN servicio s ON s.id_servicio=m.id_servicio
                 SET m.fecha_programacion=?,m.estado_trabajo='Programado'
                 WHERE m.id_mantenimiento=? AND m.fecha_programacion IS NULL
                   AND s.estado NOT IN ('Finalizado','Entregado')"
            );
            $stmt->bind_param('si', $fecha, $idMantenimiento);
            if ($stmt->execute() && $stmt->affected_rows === 1) {
                $lookup = $conn->prepare('SELECT id_servicio FROM mantenimiento WHERE id_mantenimiento=?');
                $lookup->bind_param('i', $idMantenimiento);
                $lookup->execute();
                $idServicio = (int)($lookup->get_result()->fetch_assoc()['id_servicio'] ?? 0);
                $auditOk = registrarActividadSistema(
                    $conn,
                    (int)$_SESSION['id_usuario'],
                    'mantenimiento_programado',
                    'mantenimiento',
                    $idMantenimiento,
                    $idServicio,
                    'Recepción programó el mantenimiento.'
                );
                $mensaje = $auditOk ? 'Mantenimiento programado.' : 'El mantenimiento se programó, pero no pudo registrarse la auditoría.';
                $tipoMensaje = $auditOk ? 'success' : 'warning';
            } else {
                $mensaje = 'No se pudo programar: verifica el estado y que siga pendiente.';
            }
        }
    } elseif ($rol === 'recepcionista' && ($_POST['action'] ?? '') === 'crear') {
        $idServicio = (int) ($_POST['id_servicio'] ?? 0);
        $tipo = trim($_POST['tipo_mantenimiento'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');
        $fecha = trim($_POST['fecha_programacion'] ?? '');
        $fechaObj = DateTime::createFromFormat('!Y-m-d', $fecha);
        $tipos = ['Preventivo', 'Correctivo', 'General', 'Alineación'];
        if ($idServicio <= 0 || !in_array($tipo, $tipos, true) || $observaciones === '' ||
            !$fechaObj || $fechaObj->format('Y-m-d') !== $fecha || $fecha < date('Y-m-d')) {
            $mensaje = 'Completa la solicitud y la fecha de programación correctamente.';
        } else {
            $stmt = $conn->prepare(
                "SELECT s.estado FROM servicio s WHERE s.id_servicio = ? AND s.tipo_servicio = 'Mantenimiento'"
            );
            $stmt->bind_param('i', $idServicio);
            $stmt->execute();
            $servicio = $stmt->get_result()->fetch_assoc();
            if (!$servicio || $servicio['estado'] === 'Finalizado' || $servicio['estado'] === 'Entregado') {
                $mensaje = 'El servicio no existe, no es de mantenimiento o ya terminó.';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO mantenimiento (tipo_mantenimiento, observaciones, fecha_programacion, estado_autorizacion, estado_trabajo, id_servicio)
                     VALUES (?, ?, ?, NULL, 'Programado', ?)"
                );
                $stmt->bind_param('sssi', $tipo, $observaciones, $fecha, $idServicio);
                if ($stmt->execute()) {
                    $auditOk = registrarActividadSistema(
                        $conn,
                        (int)$_SESSION['id_usuario'],
                        'mantenimiento_registrado',
                        'mantenimiento',
                        (int)$stmt->insert_id,
                        $idServicio,
                        'Recepción registró y programó un mantenimiento.'
                    );
                    $mensaje = $auditOk
                        ? 'Mantenimiento programado. Queda pendiente de autorización del cliente.'
                        : 'El mantenimiento se guardó, pero no pudo registrarse la auditoría.';
                    $tipoMensaje = $auditOk ? 'success' : 'warning';
                } else {
                    $mensaje = 'No se pudo guardar. Verifica que el servicio no tenga otro mantenimiento.';
                }
            }
        }
    } elseif ($rol === 'cliente' && ($_POST['action'] ?? '') === 'responder') {
        $idMantenimiento = (int) ($_POST['id_mantenimiento'] ?? 0);
        $respuesta = $_POST['respuesta'] ?? '';
        if ($idMantenimiento <= 0 || !in_array($respuesta, ['Aprobada', 'Rechazada'], true)) {
            $mensaje = 'Respuesta no válida.';
        } else {
            $stmt = $conn->prepare(
                'UPDATE mantenimiento m
                 JOIN servicio s ON s.id_servicio = m.id_servicio
                 JOIN vehiculo v ON v.id_vehiculo = s.id_vehiculo
                 SET m.estado_autorizacion = ?, m.fecha_respuesta_autorizacion = NOW()
                 WHERE m.id_mantenimiento = ? AND v.id_cliente = ? AND m.estado_autorizacion IS NULL
                   AND m.fecha_programacion IS NOT NULL'
            );
            $stmt->bind_param('sii', $respuesta, $idMantenimiento, $clienteId);
            if ($stmt->execute() && $stmt->affected_rows === 1) {
                $lookup = $conn->prepare('SELECT id_servicio FROM mantenimiento WHERE id_mantenimiento=?');
                $lookup->bind_param('i', $idMantenimiento);
                $lookup->execute();
                $idServicio = (int)($lookup->get_result()->fetch_assoc()['id_servicio'] ?? 0);
                $auditOk = registrarActividadSistema(
                    $conn,
                    (int)$_SESSION['id_usuario'],
                    'mantenimiento_autorizacion_respondida',
                    'mantenimiento',
                    $idMantenimiento,
                    $idServicio,
                    'El cliente respondió a la solicitud de mantenimiento: ' . $respuesta . '.'
                );
                $mensaje = $auditOk ? 'Respuesta guardada.' : 'La respuesta se guardó, pero no pudo registrarse la auditoría.';
                $tipoMensaje = $auditOk ? 'success' : 'warning';
            } else {
                $mensaje = 'No se pudo responder: el registro ya fue respondido o no pertenece a tu cuenta.';
            }
        }
    } elseif ($rol === 'tecnico' && ($_POST['action'] ?? '') === 'trabajo' && $tecnicoId > 0) {
        $idMantenimiento = (int)($_POST['id_mantenimiento'] ?? 0);
        $estadoTrabajo = $_POST['estado_trabajo'] ?? '';
        if ($idMantenimiento <= 0 || !in_array($estadoTrabajo, ['En ejecución', 'Completado'], true)) {
            $mensaje = 'Estado de ejecución no válido.';
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare(
                    'SELECT h.estado_aceptacion FROM historial_asignacion_tecnico h
                     JOIN mantenimiento m ON m.id_servicio=h.id_servicio
                     WHERE m.id_mantenimiento=? ORDER BY h.fecha_asignacion DESC,h.id_asignacion DESC LIMIT 1'
                );
                $stmt->bind_param('i', $idMantenimiento);
                $stmt->execute();
                $asignacion = $stmt->get_result()->fetch_assoc();
                if ($asignacion && $asignacion['estado_aceptacion'] !== 'Aceptada') {
                    throw new DomainException('Debes aceptar la asignación vigente antes de actualizar el mantenimiento.');
                }
                $stmt = $conn->prepare(
                    "UPDATE mantenimiento m JOIN servicio s ON s.id_servicio=m.id_servicio
                     SET m.estado_trabajo=?
                     WHERE m.id_mantenimiento=? AND s.id_tecnico=? AND m.estado_autorizacion='Aprobada'
                       AND s.estado NOT IN ('Finalizado','Entregado')
                       AND ((?='En ejecución' AND (m.estado_trabajo IS NULL OR m.estado_trabajo='Programado'))
                         OR (?='Completado' AND m.estado_trabajo='En ejecución'))"
                );
                $stmt->bind_param('siiss', $estadoTrabajo, $idMantenimiento, $tecnicoId, $estadoTrabajo, $estadoTrabajo);
                $stmt->execute();
                if ($stmt->affected_rows !== 1) {
                    throw new DomainException('Autorización pendiente, servicio no asignado o transición no válida.');
                }
                $stmt = $conn->prepare(
                    "SELECT m.id_servicio,s.estado FROM mantenimiento m JOIN servicio s ON s.id_servicio=m.id_servicio WHERE m.id_mantenimiento=?"
                );
                $stmt->bind_param('i', $idMantenimiento);
                $stmt->execute();
                $detalle = $stmt->get_result()->fetch_assoc();
                $comentario = 'Estado del trabajo de mantenimiento actualizado a: ' . $estadoTrabajo . '.';
                $stmt = $conn->prepare(
                    'INSERT INTO seguimiento (fecha,fecha_hora,estado,comentario,id_servicio,id_tecnico,aprendiz)
                     VALUES (CURDATE(),NOW(),?,?,?, ?, \'\')'
                );
                $stmt->bind_param('ssii', $detalle['estado'], $comentario, $detalle['id_servicio'], $tecnicoId);
                $stmt->execute();
                if (!registrarActividadSistema(
                    $conn,
                    (int)$_SESSION['id_usuario'],
                    'actividad_mantenimiento',
                    'mantenimiento',
                    $idMantenimiento,
                    (int)$detalle['id_servicio'],
                    'El técnico actualizó la actividad de mantenimiento a ' . $estadoTrabajo . '.'
                )) {
                    throw new mysqli_sql_exception('No se pudo registrar la auditoría de mantenimiento.');
                }
                $conn->commit();
                $mensaje = 'Estado del mantenimiento actualizado.';
                $tipoMensaje = 'success';
            } catch (DomainException $error) {
                $conn->rollback();
                $mensaje = $error->getMessage();
            } catch (mysqli_sql_exception $error) {
                $conn->rollback();
                error_log('Error al actualizar mantenimiento: '.$error->getMessage());
                $mensaje = 'No se pudo actualizar el mantenimiento.';
            }
        }
    } else {
        $mensaje = 'No tienes permiso para esta acción.';
    }
}

$sql = 'SELECT m.id_mantenimiento, m.tipo_mantenimiento, m.observaciones, m.fecha_programacion,
               m.estado_autorizacion, m.estado_trabajo, m.fecha_respuesta_autorizacion,
               s.id_servicio, s.estado AS estado_servicio, v.placa, c.nombre, c.apellido
        FROM mantenimiento m JOIN servicio s ON s.id_servicio=m.id_servicio
        JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
        JOIN cliente c ON c.id_cliente=v.id_cliente';
if ($rol === 'cliente') {
    $stmt = $conn->prepare($sql . ' WHERE v.id_cliente=? ORDER BY m.fecha_programacion DESC');
    $stmt->bind_param('i', $clienteId);
} elseif ($rol === 'tecnico') {
    $stmt = $conn->prepare($sql . ' WHERE s.id_tecnico=? ORDER BY m.fecha_programacion DESC');
    $stmt->bind_param('i', $tecnicoId);
} else {
    $stmt = $conn->prepare($sql . ' ORDER BY m.fecha_programacion DESC');
}
$stmt->execute();
$registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$serviciosDisponibles = [];
if ($rol === 'recepcionista') {
    $result = $conn->query(
        "SELECT s.id_servicio, v.placa, c.nombre, c.apellido
         FROM servicio s JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
         JOIN cliente c ON c.id_cliente=v.id_cliente
         LEFT JOIN mantenimiento m ON m.id_servicio=s.id_servicio
         WHERE s.tipo_servicio='Mantenimiento' AND s.estado NOT IN ('Finalizado','Entregado')
           AND m.id_mantenimiento IS NULL ORDER BY s.fecha_ingreso DESC"
    );
    $serviciosDisponibles = $result->fetch_all(MYSQLI_ASSOC);
}
$solicitudesSinProgramar = [];
if ($rol === 'recepcionista') {
    $result = $conn->query(
        'SELECT m.id_mantenimiento,m.tipo_mantenimiento,m.observaciones,s.id_servicio,v.placa,c.nombre,c.apellido
         FROM mantenimiento m JOIN servicio s ON s.id_servicio=m.id_servicio
         JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo JOIN cliente c ON c.id_cliente=v.id_cliente
         WHERE m.fecha_programacion IS NULL AND s.estado NOT IN (\'Finalizado\',\'Entregado\')
         ORDER BY m.id_mantenimiento'
    );
    $solicitudesSinProgramar = $result->fetch_all(MYSQLI_ASSOC);
}
$serviciosCliente = [];
if ($rol === 'cliente' && $clienteId > 0) {
    $stmt = $conn->prepare(
        "SELECT s.id_servicio,v.placa FROM servicio s JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
         LEFT JOIN mantenimiento m ON m.id_servicio=s.id_servicio
         WHERE v.id_cliente=? AND s.tipo_servicio='Mantenimiento'
           AND s.estado NOT IN ('Finalizado','Entregado') AND m.id_mantenimiento IS NULL
         ORDER BY s.fecha_ingreso DESC"
    );
    $stmt->bind_param('i', $clienteId);
    $stmt->execute();
    $serviciosCliente = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mantenimientos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
    <script src="../js/form-validation.js?v=20261109-confirm" defer></script>
</head><body class="admin-body module-page module-maintenance"><div class="admin-layout">
  <aside class="admin-sidebar"><div class="sidebar-brand"><h4>Mantenimientos</h4></div><nav class="sidebar-nav">
    <?php if ($rol === 'cliente'): ?><a href="../../index.html">Volver al panel</a><a href="cliente_servicios.php">Mis servicios</a>
    <?php elseif ($rol === 'tecnico'): ?><a href="../../index.html">Volver al panel</a><a href="tecnico_servicios.php">Servicios asignados</a>
    <?php elseif ($rol === 'administrador'): ?><a href="../../index.html">Volver al panel</a><a href="admin_servicios.php">Servicios</a>
    <?php else: ?><a href="../../index.html">Volver al panel</a><a href="recepcionista_servicios.php">Servicios</a><?php endif; ?>
    <a href="mantenimientos.php" class="active">Mantenimientos</a><a href="logout.php">Salir</a>
  </nav></aside>
  <main class="admin-content">
    <h2 class="section-title">Gestión de mantenimientos</h2>
    <?php if ($mensaje !== ''): ?><div class="alert alert-<?= htmlspecialchars($tipoMensaje) ?> alerta"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($rol === 'recepcionista'): ?>
      <?php if($solicitudesSinProgramar): ?><div class="dashboard-panel mb-4"><h5 class="section-title">Solicitudes por programar</h5>
        <?php foreach($solicitudesSinProgramar as $solicitud): ?><form method="POST" class="row g-2 align-items-end border-bottom py-2">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="programar"><input type="hidden" name="id_mantenimiento" value="<?= (int)$solicitud['id_mantenimiento'] ?>">
          <div class="col-md-6"><strong>#<?= (int)$solicitud['id_servicio'] ?> · <?= htmlspecialchars($solicitud['placa'].' · '.$solicitud['nombre'].' '.$solicitud['apellido']) ?></strong><br><small><?= htmlspecialchars($solicitud['tipo_mantenimiento'].' · '.$solicitud['observaciones']) ?></small></div>
          <div class="col-md-4"><label class="form-label">Fecha de programación</label><input type="date" name="fecha_programacion" min="<?= date('Y-m-d') ?>" class="form-control" required></div>
          <div class="col-md-2"><button class="btn btn-outline-verde">Programar</button></div>
        </form><?php endforeach; ?></div><?php endif; ?>
      <div class="dashboard-panel mb-4"><h5 class="section-title">Programar mantenimiento</h5>
        <form method="POST" class="row g-3">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="crear">
          <div class="col-md-6"><label class="form-label">Servicio / vehículo</label><select name="id_servicio" class="form-select" required>
            <option value="">Seleccione</option><?php foreach ($serviciosDisponibles as $s): ?><option value="<?= (int)$s['id_servicio'] ?>">#<?= (int)$s['id_servicio'] ?> · <?= htmlspecialchars($s['placa'].' · '.$s['nombre'].' '.$s['apellido']) ?></option><?php endforeach; ?>
          </select></div>
          <div class="col-md-3"><label class="form-label">Tipo</label><select name="tipo_mantenimiento" class="form-select" required><option value="">Seleccione</option><option>Preventivo</option><option>Correctivo</option><option>General</option><option>Alineación</option></select></div>
          <div class="col-md-3"><label class="form-label">Programación</label><input type="date" name="fecha_programacion" min="<?= date('Y-m-d') ?>" class="form-control" required></div>
          <div class="col-12"><label class="form-label">Observaciones iniciales</label><textarea name="observaciones" class="form-control" rows="3" required></textarea></div>
          <div class="col-12"><button class="btn btn-verde rounded-pill">Guardar solicitud</button></div>
        </form>
      </div>
    <?php endif; ?>
    <?php if($rol==='cliente'): ?><div class="dashboard-panel mb-4"><h5 class="section-title">Solicitar mantenimiento</h5>
      <form method="POST" class="row g-3"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="solicitar">
        <div class="col-md-5"><label class="form-label">Mi servicio / vehículo</label><select name="id_servicio" class="form-select" required><option value="">Seleccione</option><?php foreach($serviciosCliente as $s): ?><option value="<?= (int)$s['id_servicio'] ?>">#<?= (int)$s['id_servicio'] ?> · <?= htmlspecialchars($s['placa']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label">Tipo</label><select name="tipo_mantenimiento" class="form-select" required><option value="">Seleccione</option><option>Preventivo</option><option>Correctivo</option><option>General</option><option>Alineación</option></select></div>
        <div class="col-md-4"><label class="form-label">Solicitud / observaciones</label><input name="observaciones" class="form-control" required></div>
        <div class="col-12"><button class="btn btn-verde rounded-pill">Enviar solicitud</button></div>
      </form></div><?php endif; ?>
    <div class="dashboard-panel"><div class="table-responsive"><table class="table table-sm align-middle">
      <thead><tr><th>Servicio</th><th>Vehículo</th><?php if ($rol !== 'cliente'): ?><th>Cliente</th><?php endif; ?><th>Tipo</th><th>Programación</th><th>Observaciones</th><th>Autorización</th><th>Trabajo</th><?php if ($rol==='cliente'): ?><th>Respuesta</th><?php endif; ?><?php if($rol==='tecnico'): ?><th>Acción técnica</th><?php endif; ?></tr></thead>
      <tbody><?php foreach ($registros as $m): ?><tr>
        <td>#<?= (int)$m['id_servicio'] ?> · <?= htmlspecialchars($m['estado_servicio'] ?? 'Sin estado') ?></td><td><?= htmlspecialchars($m['placa']) ?></td>
        <?php if ($rol !== 'cliente'): ?><td><?= htmlspecialchars($m['nombre'].' '.$m['apellido']) ?></td><?php endif; ?>
        <td><?= htmlspecialchars($m['tipo_mantenimiento'] ?? 'No clasificado') ?></td><td><?= htmlspecialchars($m['fecha_programacion'] ?? 'Sin programar') ?></td>
        <td><?= nl2br(htmlspecialchars($m['observaciones'] ?? '')) ?></td><td><?= htmlspecialchars($m['estado_autorizacion'] ?? 'Pendiente de respuesta') ?></td><td><?= htmlspecialchars($m['estado_trabajo'] ?? 'No clasificado') ?></td>
        <?php if($rol==='cliente'): ?><td><?php if ($m['estado_autorizacion'] === null && $m['fecha_programacion'] !== null): ?><form method="POST" class="d-flex gap-1">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="responder"><input type="hidden" name="id_mantenimiento" value="<?= (int)$m['id_mantenimiento'] ?>">
          <button name="respuesta" value="Aprobada" class="btn btn-sm btn-verde">Autorizar</button><button name="respuesta" value="Rechazada" class="btn btn-sm btn-outline-danger">Rechazar</button>
        </form><?php elseif($m['estado_autorizacion']===null): ?>Pendiente de programación<?php else: ?>Respondido <?= htmlspecialchars($m['fecha_respuesta_autorizacion'] ?? '') ?><?php endif; ?></td><?php endif; ?>
        <?php if($rol==='tecnico'): ?><td><?php if($m['estado_autorizacion']==='Aprobada' && !in_array($m['estado_trabajo'],['Completado'],true) && !in_array($m['estado_servicio'],['Finalizado','Entregado'],true)): ?>
          <form method="POST" class="d-flex gap-1"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="trabajo"><input type="hidden" name="id_mantenimiento" value="<?= (int)$m['id_mantenimiento'] ?>">
            <?php if($m['estado_trabajo']==='En ejecución'): ?><button name="estado_trabajo" value="Completado" class="btn btn-sm btn-verde">Marcar completado</button><?php else: ?><button name="estado_trabajo" value="En ejecución" class="btn btn-sm btn-outline-verde">Iniciar autorizado</button><?php endif; ?>
          </form><?php else: ?>—<?php endif; ?></td><?php endif; ?>
      </tr><?php endforeach; ?><?php if (!$registros): ?><tr><td colspan="8" class="text-muted">No hay mantenimientos registrados.</td></tr><?php endif; ?></tbody>
    </table></div></div>
  </main>
</div></body></html>
