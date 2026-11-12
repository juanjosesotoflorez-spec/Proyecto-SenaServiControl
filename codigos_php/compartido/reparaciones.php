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

$idCliente = null;
$idTecnico = null;
if ($rol === 'cliente') {
    $stmt = $conn->prepare('SELECT id_cliente FROM cliente WHERE id_usuario=?');
    $stmt->bind_param('i', $_SESSION['id_usuario']);
    $stmt->execute();
    $idCliente = (int) ($stmt->get_result()->fetch_assoc()['id_cliente'] ?? 0);
}
if ($rol === 'tecnico') {
    $stmt = $conn->prepare('SELECT id_tecnico FROM tecnico WHERE id_usuario=?');
    $stmt->bind_param('i', $_SESSION['id_usuario']);
    $stmt->execute();
    $idTecnico = (int) ($stmt->get_result()->fetch_assoc()['id_tecnico'] ?? 0);
}

$mensaje = '';
$tipoMensaje = 'danger';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $mensaje = 'La sesión venció. Recarga la página.';
    } elseif ($rol === 'recepcionista' && ($_POST['action'] ?? '') === 'crear') {
        $idServicio = (int) ($_POST['id_servicio'] ?? 0);
        $problema = trim($_POST['problema_reportado'] ?? '');
        if ($idServicio <= 0 || $problema === '') {
            $mensaje = 'Indica un servicio válido y describe el problema reportado.';
        } else {
            $stmt = $conn->prepare(
                "SELECT id_servicio FROM servicio
                 WHERE id_servicio=? AND tipo_servicio='Reparacion' AND estado NOT IN ('Finalizado','Entregado')"
            );
            $stmt->bind_param('i', $idServicio);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                $mensaje = 'Servicio inexistente, no es una reparación o ya está cerrado.';
            } else {
                $estado = 'Solicitud recibida';
                $stmt = $conn->prepare(
                    'INSERT INTO reparacion (problema_reportado, dano_detectado, estado, id_servicio)
                     VALUES (?, NULL, ?, ?)'
                );
                $stmt->bind_param('ssi', $problema, $estado, $idServicio);
                if ($stmt->execute()) {
                    $auditOk = registrarActividadSistema(
                        $conn,
                        (int)$_SESSION['id_usuario'],
                        'reparacion_solicitada',
                        'reparacion',
                        (int)$stmt->insert_id,
                        $idServicio,
                        'Recepción registró una solicitud de reparación.'
                    );
                    $mensaje = $auditOk
                        ? 'Solicitud de reparación registrada; falta diagnóstico y presupuesto técnico.'
                        : 'La reparación se registró, pero no pudo guardarse en la auditoría.';
                    $tipoMensaje = $auditOk ? 'success' : 'warning';
                } else {
                    $mensaje = 'No se pudo guardar. El servicio podría tener una reparación registrada.';
                }
            }
        }
    } elseif ($rol === 'tecnico' && ($_POST['action'] ?? '') === 'diagnosticar' && $idTecnico > 0) {
        $idReparacion = (int) ($_POST['id_reparacion'] ?? 0);
        $falla = trim($_POST['dano_detectado'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');
        $presupuestoTexto = trim($_POST['presupuesto'] ?? '');
        $presupuesto = is_numeric($presupuestoTexto) ? (float)$presupuestoTexto : -1;
        if ($idReparacion <= 0 || $falla === '' || $presupuesto < 0 || !is_finite($presupuesto) || $presupuesto > 99999999.99) {
            $mensaje = 'Registra una falla identificada y un presupuesto válido.';
        } else {
            $stmt = $conn->prepare(
                'SELECT h.estado_aceptacion FROM historial_asignacion_tecnico h
                 JOIN reparacion r ON r.id_servicio=h.id_servicio
                 WHERE r.id_reparacion=? ORDER BY h.fecha_asignacion DESC,h.id_asignacion DESC LIMIT 1'
            );
            $stmt->bind_param('i', $idReparacion);
            $stmt->execute();
            $asignacion = $stmt->get_result()->fetch_assoc();
            if ($asignacion && $asignacion['estado_aceptacion'] !== 'Aceptada') {
                $mensaje = 'Debes aceptar la asignación vigente antes de diagnosticar.';
            } else {
                $stmt = $conn->prepare(
                    "UPDATE reparacion r JOIN servicio s ON s.id_servicio=r.id_servicio
                     SET r.dano_detectado=?, r.observaciones=?, r.presupuesto=?, r.estado='Pendiente de aprobación',
                         r.estado_presupuesto='Pendiente', r.fecha_respuesta_presupuesto=NULL
                     WHERE r.id_reparacion=? AND s.id_tecnico=? AND s.estado NOT IN ('Finalizado','Entregado')
                       AND r.estado IN ('Solicitud recibida','Pendiente de diagnóstico')"
                );
                $stmt->bind_param('ssdii', $falla, $observaciones, $presupuesto, $idReparacion, $idTecnico);
                if ($stmt->execute() && $stmt->affected_rows === 1) {
                    $lookup = $conn->prepare('SELECT id_servicio FROM reparacion WHERE id_reparacion=?');
                    $lookup->bind_param('i', $idReparacion);
                    $lookup->execute();
                    $idServicio = (int)($lookup->get_result()->fetch_assoc()['id_servicio'] ?? 0);
                    $auditOk = registrarActividadSistema(
                        $conn,
                        (int)$_SESSION['id_usuario'],
                        'diagnostico_reparacion',
                        'reparacion',
                        $idReparacion,
                        $idServicio,
                        'El técnico registró el diagnóstico y el presupuesto.'
                    );
                    $mensaje = $auditOk
                        ? 'Diagnóstico y presupuesto registrados. La reparación queda bloqueada hasta respuesta del cliente.'
                        : 'El diagnóstico y presupuesto se guardaron, pero no pudo registrarse la auditoría.';
                    $tipoMensaje = $auditOk ? 'success' : 'warning';
                } else {
                    $mensaje = 'No se pudo actualizar: revisa asignación, estado del servicio o si ya se presentó un presupuesto.';
                }
            }
        }
    } elseif ($rol === 'tecnico' && ($_POST['action'] ?? '') === 'completar' && $idTecnico > 0) {
        $idReparacion = (int) ($_POST['id_reparacion'] ?? 0);
        $solucion = trim($_POST['solucion'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');
        if ($idReparacion <= 0 || $solucion === '') {
            $mensaje = 'Describe la solución aplicada.';
        } else {
            $stmt = $conn->prepare(
                'SELECT h.estado_aceptacion FROM historial_asignacion_tecnico h
                 JOIN reparacion r ON r.id_servicio=h.id_servicio
                 WHERE r.id_reparacion=? ORDER BY h.fecha_asignacion DESC,h.id_asignacion DESC LIMIT 1'
            );
            $stmt->bind_param('i', $idReparacion);
            $stmt->execute();
            $asignacion = $stmt->get_result()->fetch_assoc();
            if ($asignacion && $asignacion['estado_aceptacion'] !== 'Aceptada') {
                $mensaje = 'Debes aceptar la asignación vigente antes de ejecutar la reparación.';
            } else {
            $stmt = $conn->prepare(
                "UPDATE reparacion r JOIN servicio s ON s.id_servicio=r.id_servicio
                 SET r.solucion=?, r.observaciones=?, r.estado='Completada'
                 WHERE r.id_reparacion=? AND s.id_tecnico=? AND s.estado NOT IN ('Finalizado','Entregado')
                   AND r.estado_presupuesto='Aprobada' AND r.dano_detectado IS NOT NULL"
            );
            $stmt->bind_param('ssii', $solucion, $observaciones, $idReparacion, $idTecnico);
            if ($stmt->execute() && $stmt->affected_rows === 1) {
                $lookup = $conn->prepare('SELECT id_servicio FROM reparacion WHERE id_reparacion=?');
                $lookup->bind_param('i', $idReparacion);
                $lookup->execute();
                $idServicio = (int)($lookup->get_result()->fetch_assoc()['id_servicio'] ?? 0);
                $auditOk = registrarActividadSistema(
                    $conn,
                    (int)$_SESSION['id_usuario'],
                    'reparacion_completada',
                    'reparacion',
                    $idReparacion,
                    $idServicio,
                    'El técnico registró la solución y completó la reparación.'
                );
                $mensaje = $auditOk
                    ? 'Solución documentada y reparación marcada como completada.'
                    : 'La reparación se completó, pero no pudo registrarse la auditoría.';
                $tipoMensaje = $auditOk ? 'success' : 'warning';
            } else {
                $mensaje = 'La reparación requiere presupuesto aprobado y asignación vigente antes de completarse.';
            }
            }
        }
    } elseif ($rol === 'cliente' && ($_POST['action'] ?? '') === 'responder') {
        $idReparacion = (int) ($_POST['id_reparacion'] ?? 0);
        $respuesta = $_POST['respuesta'] ?? '';
        if ($idReparacion <= 0 || !in_array($respuesta, ['Aprobada', 'Rechazada'], true)) {
            $mensaje = 'Respuesta no válida.';
        } else {
            $stmt = $conn->prepare(
                "UPDATE reparacion r
                 JOIN servicio s ON s.id_servicio=r.id_servicio
                 JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
                 SET r.estado_presupuesto=?, r.fecha_respuesta_presupuesto=NOW(),
                     r.estado=IF(?='Aprobada','Aprobada para ejecución','Presupuesto rechazado')
                 WHERE r.id_reparacion=? AND v.id_cliente=? AND r.estado_presupuesto='Pendiente'
                   AND r.presupuesto IS NOT NULL AND r.dano_detectado IS NOT NULL"
            );
            $stmt->bind_param('ssii', $respuesta, $respuesta, $idReparacion, $idCliente);
            if ($stmt->execute() && $stmt->affected_rows === 1) {
                $lookup = $conn->prepare('SELECT id_servicio FROM reparacion WHERE id_reparacion=?');
                $lookup->bind_param('i', $idReparacion);
                $lookup->execute();
                $idServicio = (int)($lookup->get_result()->fetch_assoc()['id_servicio'] ?? 0);
                $auditOk = registrarActividadSistema(
                    $conn,
                    (int)$_SESSION['id_usuario'],
                    'presupuesto_respondido',
                    'reparacion',
                    $idReparacion,
                    $idServicio,
                    'El cliente respondió al presupuesto: ' . $respuesta . '.'
                );
                $mensaje = $auditOk ? 'Respuesta al presupuesto registrada.' : 'La respuesta se guardó, pero no pudo registrarse la auditoría.';
                $tipoMensaje = $auditOk ? 'success' : 'warning';
            } else {
                $mensaje = 'No se pudo responder: presupuesto inexistente, ya contestado o no pertenece a tu cuenta.';
            }
        }
    } else {
        $mensaje = 'No tienes permiso para esta acción.';
    }
}

$sql = 'SELECT r.id_reparacion, r.problema_reportado, r.dano_detectado, r.solucion,
               r.observaciones, r.presupuesto, r.estado, r.estado_presupuesto,
               r.fecha_respuesta_presupuesto, s.id_servicio, s.estado AS estado_servicio,
               s.id_tecnico, v.placa, c.id_cliente, c.nombre, c.apellido
        FROM reparacion r JOIN servicio s ON s.id_servicio=r.id_servicio
        JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
        JOIN cliente c ON c.id_cliente=v.id_cliente';
if ($rol === 'cliente') {
    $stmt = $conn->prepare($sql . ' WHERE c.id_cliente=? ORDER BY r.id_reparacion DESC');
    $stmt->bind_param('i', $idCliente);
} elseif ($rol === 'tecnico') {
    $stmt = $conn->prepare($sql . ' WHERE s.id_tecnico=? ORDER BY r.id_reparacion DESC');
    $stmt->bind_param('i', $idTecnico);
} else {
    $stmt = $conn->prepare($sql . ' ORDER BY r.id_reparacion DESC');
}
$stmt->execute();
$registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$serviciosDisponibles = [];
if ($rol === 'recepcionista') {
    $result = $conn->query(
        "SELECT s.id_servicio,v.placa,c.nombre,c.apellido FROM servicio s
         JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo JOIN cliente c ON c.id_cliente=v.id_cliente
         LEFT JOIN reparacion r ON r.id_servicio=s.id_servicio
         WHERE s.tipo_servicio='Reparacion' AND s.estado NOT IN ('Finalizado','Entregado')
           AND r.id_reparacion IS NULL ORDER BY s.fecha_ingreso DESC"
    );
    $serviciosDisponibles = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reparaciones</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/module-pages.css?v=20261022"><script src="../js/form-validation.js?v=20261109-confirm" defer></script></head>
<body class="admin-body module-page module-repairs"><div class="admin-layout">
<aside class="admin-sidebar"><div class="sidebar-brand"><h4>Reparaciones</h4></div><nav class="sidebar-nav">
<?php if ($rol==='cliente'): ?><a href="../../index.html">Volver al panel</a><a href="cliente_servicios.php">Mis servicios</a>
<?php elseif ($rol==='tecnico'): ?><a href="../../index.html">Volver al panel</a><a href="tecnico_servicios.php">Servicios asignados</a>
<?php elseif ($rol==='administrador'): ?><a href="../../index.html">Volver al panel</a><a href="admin_servicios.php">Servicios</a>
<?php else: ?><a href="../../index.html">Volver al panel</a><a href="recepcionista_servicios.php">Servicios</a><?php endif; ?>
<a href="reparaciones.php" class="active">Reparaciones</a><a href="logout.php">Salir</a></nav></aside>
<main class="admin-content"><h2 class="section-title">Gestión de reparaciones</h2>
<p class="texto-gris">La reparación solo puede ejecutarse con presupuesto aprobado por el cliente.</p>
<?php if ($mensaje!==''): ?><div class="alert alert-<?= htmlspecialchars($tipoMensaje) ?> alerta"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
<?php if ($rol==='recepcionista'): ?><div class="dashboard-panel mb-4"><h5 class="section-title">Registrar problema reportado</h5>
<form method="POST" class="row g-3"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="crear">
<div class="col-md-6"><label class="form-label">Servicio / vehículo</label><select name="id_servicio" class="form-select" required><option value="">Seleccione</option>
<?php foreach ($serviciosDisponibles as $s): ?><option value="<?= (int)$s['id_servicio'] ?>">#<?= (int)$s['id_servicio'] ?> · <?= htmlspecialchars($s['placa'].' · '.$s['nombre'].' '.$s['apellido']) ?></option><?php endforeach; ?></select></div>
<div class="col-12"><label class="form-label">Problema reportado</label><textarea name="problema_reportado" class="form-control" rows="3" required></textarea></div>
<div class="col-12"><button class="btn btn-verde rounded-pill">Registrar solicitud</button></div></form></div><?php endif; ?>
<div class="dashboard-panel"><div class="table-responsive"><table class="table table-sm align-middle">
<thead><tr><th>Servicio / vehículo</th><?php if ($rol!=='cliente'): ?><th>Cliente</th><?php endif; ?><th>Problema</th><th>Falla identificada</th><th>Presupuesto</th><th>Estado / aprobación</th><th>Solución</th><?php if ($rol==='tecnico' || $rol==='cliente'): ?><th>Acción</th><?php endif; ?></tr></thead>
<tbody><?php foreach ($registros as $r): ?><tr><td>#<?= (int)$r['id_servicio'] ?> · <?= htmlspecialchars($r['placa']) ?></td>
<?php if ($rol!=='cliente'): ?><td><?= htmlspecialchars($r['nombre'].' '.$r['apellido']) ?></td><?php endif; ?>
<td><?= nl2br(htmlspecialchars($r['problema_reportado'] ?? 'Solicitud histórica sin detalle separado')) ?></td>
<td><?= nl2br(htmlspecialchars($r['dano_detectado'] ?? 'Sin diagnóstico')) ?></td>
<td><?= $r['presupuesto'] === null ? 'Sin cotizar' : '$'.htmlspecialchars(number_format((float)$r['presupuesto'],2)) ?></td>
<td><?= htmlspecialchars($r['estado'] ?? 'Histórico sin clasificar') ?> / <?= htmlspecialchars($r['estado_presupuesto'] ?? 'Sin respuesta') ?></td>
<td><?= nl2br(htmlspecialchars($r['solucion'] ?? 'Pendiente')) ?><br><small><?= nl2br(htmlspecialchars($r['observaciones'] ?? '')) ?></small></td>
<?php if ($rol==='tecnico' || $rol==='cliente'): ?><td>
<?php if ($rol==='tecnico' && in_array($r['estado'], ['Solicitud recibida','Pendiente de diagnóstico'], true)): ?>
<form method="POST" class="d-grid gap-1"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="diagnosticar"><input type="hidden" name="id_reparacion" value="<?= (int)$r['id_reparacion'] ?>">
<input name="dano_detectado" class="form-control form-control-sm" placeholder="Falla identificada" required><input type="number" name="presupuesto" class="form-control form-control-sm" min="0" max="99999999.99" step="0.01" placeholder="Presupuesto" required><textarea name="observaciones" class="form-control form-control-sm" placeholder="Observaciones"></textarea><button class="btn btn-sm btn-outline-verde">Enviar diagnóstico</button></form>
<?php elseif ($rol==='tecnico' && $r['estado_presupuesto']==='Aprobada' && $r['estado']!=='Completada'): ?>
<form method="POST" class="d-grid gap-1"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="completar"><input type="hidden" name="id_reparacion" value="<?= (int)$r['id_reparacion'] ?>"><textarea name="solucion" class="form-control form-control-sm" placeholder="Solución aplicada" required></textarea><textarea name="observaciones" class="form-control form-control-sm" placeholder="Observaciones"></textarea><button class="btn btn-sm btn-verde">Completar reparación</button></form>
<?php elseif ($rol==='cliente' && $r['estado_presupuesto']==='Pendiente'): ?>
<form method="POST" class="d-flex gap-1"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="responder"><input type="hidden" name="id_reparacion" value="<?= (int)$r['id_reparacion'] ?>"><button name="respuesta" value="Aprobada" class="btn btn-sm btn-verde">Aprobar</button><button name="respuesta" value="Rechazada" class="btn btn-sm btn-outline-danger">Rechazar</button></form>
<?php else: ?>—<?php endif; ?></td><?php endif; ?>
</tr><?php endforeach; ?><?php if (!$registros): ?><tr><td colspan="8" class="text-muted">No hay reparaciones registradas.</td></tr><?php endif; ?></tbody>
</table></div></div></main></div></body></html>
