<?php
session_start();
$rol = $_SESSION['rol'] ?? '';
if (!isset($_SESSION['id_usuario']) || !in_array($rol, ['administrador', 'recepcionista', 'cliente'], true)) {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';
require_once dirname(__DIR__) . '/compartido/auditoria_helper.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$clienteId = null;
$recepcionistaId = null;
if ($rol === 'cliente') {
    $stmt = $conn->prepare('SELECT id_cliente FROM cliente WHERE id_usuario=?');
    $stmt->bind_param('i', $_SESSION['id_usuario']);
    $stmt->execute();
    $clienteId = (int)($stmt->get_result()->fetch_assoc()['id_cliente'] ?? 0);
} elseif ($rol === 'recepcionista') {
    $stmt = $conn->prepare('SELECT id_recepcionista FROM recepcionista WHERE id_usuario=?');
    $stmt->bind_param('i', $_SESSION['id_usuario']);
    $stmt->execute();
    $recepcionistaId = (int)($stmt->get_result()->fetch_assoc()['id_recepcionista'] ?? 0);
}

$mensaje = '';
$tipoMensaje = 'danger';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $mensaje = 'La sesión venció. Recarga la página.';
    } elseif ($rol === 'recepcionista' && ($_POST['action'] ?? '') === 'iniciar') {
        $idServicio = (int)($_POST['id_servicio'] ?? 0);
        if ($idServicio <= 0 || !$recepcionistaId) {
            $mensaje = 'Selecciona un servicio válido.';
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare(
                    "SELECT s.estado FROM servicio s WHERE s.id_servicio=? FOR UPDATE"
                );
                $stmt->bind_param('i', $idServicio);
                $stmt->execute();
                $servicio = $stmt->get_result()->fetch_assoc();
                $checks = $conn->prepare(
                    "SELECT
                       (SELECT COUNT(*) FROM mantenimiento m WHERE m.id_servicio=? AND (m.estado_autorizacion IS NULL OR m.estado_autorizacion<>'Aprobada' OR m.estado_trabajo IS NULL OR m.estado_trabajo<>'Completado')) AS mantenimiento_pendiente,
                       (SELECT COUNT(*) FROM reparacion r WHERE r.id_servicio=? AND (r.estado_presupuesto IS NULL OR r.estado_presupuesto<>'Aprobada' OR r.estado IS NULL OR r.estado<>'Completada')) AS reparacion_pendiente"
                );
                $checks->bind_param('ii', $idServicio, $idServicio);
                $checks->execute();
                $pendientes = $checks->get_result()->fetch_assoc();
                if (!$servicio || $servicio['estado'] !== 'Finalizado') {
                    throw new DomainException('El trabajo técnico debe estar finalizado antes de iniciar la entrega.');
                }
                if ((int)$pendientes['mantenimiento_pendiente'] > 0 || (int)$pendientes['reparacion_pendiente'] > 0) {
                    throw new DomainException('Hay actividades sin autorización o sin completar; no se puede entregar el vehículo.');
                }
                $stmt = $conn->prepare(
                    "INSERT INTO entrega_vehiculo (id_servicio,id_recepcionista,estado_confirmacion_cliente)
                     VALUES (?,?,'Pendiente')"
                );
                $stmt->bind_param('ii', $idServicio, $recepcionistaId);
                $stmt->execute();
                if (!registrarActividadSistema(
                    $conn,
                    (int)$_SESSION['id_usuario'],
                    'entrega_iniciada',
                    'entrega_vehiculo',
                    (int)$conn->insert_id,
                    $idServicio,
                    'Recepción inició la entrega para confirmación del cliente.'
                )) {
                    throw new mysqli_sql_exception('No se pudo registrar la auditoría de la entrega.');
                }
                $conn->commit();
                $mensaje = 'Entrega pendiente de confirmación del cliente. La salida se registrará al confirmar la recepción.';
                $tipoMensaje = 'success';
            } catch (DomainException $error) {
                $conn->rollback();
                $mensaje = $error->getMessage();
            } catch (mysqli_sql_exception $error) {
                $conn->rollback();
                error_log('Error al iniciar entrega: '.$error->getMessage());
                $mensaje = 'No se pudo registrar la entrega; puede que ya exista una para este servicio.';
            }
        }
    } elseif ($rol === 'cliente' && ($_POST['action'] ?? '') === 'confirmar' && $clienteId > 0) {
        $idEntrega = (int)($_POST['id_entrega'] ?? 0);
        if ($idEntrega <= 0) {
            $mensaje = 'Entrega no válida.';
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare(
                    "SELECT e.id_servicio,s.estado,v.id_cliente
                     FROM entrega_vehiculo e JOIN servicio s ON s.id_servicio=e.id_servicio
                     JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
                     WHERE e.id_entrega=? FOR UPDATE"
                );
                $stmt->bind_param('i', $idEntrega);
                $stmt->execute();
                $entrega = $stmt->get_result()->fetch_assoc();
                if (!$entrega || (int)$entrega['id_cliente'] !== $clienteId || $entrega['estado'] !== 'Finalizado') {
                    throw new DomainException('La entrega no existe, no pertenece a tu cuenta o el servicio no está finalizado.');
                }
                $stmt = $conn->prepare(
                    "UPDATE entrega_vehiculo SET estado_confirmacion_cliente='Aceptada',
                     fecha_confirmacion_cliente=NOW(),fecha_salida=NOW()
                     WHERE id_entrega=? AND estado_confirmacion_cliente='Pendiente'"
                );
                $stmt->bind_param('i', $idEntrega);
                $stmt->execute();
                if ($stmt->affected_rows !== 1) {
                    throw new DomainException('La recepción ya fue confirmada.');
                }
                $idServicio = (int)$entrega['id_servicio'];
                $estado = 'Entregado';
                $stmt = $conn->prepare(
                    "UPDATE servicio SET estado=?,fecha_entrega_real=CURDATE()
                     WHERE id_servicio=? AND estado='Finalizado'"
                );
                $stmt->bind_param('si', $estado, $idServicio);
                $stmt->execute();
                if ($stmt->affected_rows !== 1) {
                    throw new DomainException('El servicio ya no está disponible para completar su entrega.');
                }
                $comentario = 'Cliente confirmó la recepción del vehículo. Entrega completada.';
                $stmt = $conn->prepare(
                    'INSERT INTO seguimiento (fecha,fecha_hora,estado,comentario,id_servicio,id_tecnico,aprendiz)
                     VALUES (CURDATE(),NOW(),?,?,?,NULL,\'\')'
                );
                $stmt->bind_param('ssi', $estado, $comentario, $idServicio);
                $stmt->execute();
                $mensajeNotificacion = 'Servicio #'.$idServicio.': vehículo entregado y recepción confirmada.';
                $estadoNotificacion = 'Interna';
                $stmt = $conn->prepare(
                    'INSERT INTO notificacion (mensaje,fecha_envio,estado,id_cliente,id_servicio)
                     VALUES (?,NOW(),?,?,?)'
                );
                $stmt->bind_param('ssii', $mensajeNotificacion, $estadoNotificacion, $clienteId, $idServicio);
                $stmt->execute();
                if (!registrarActividadSistema(
                    $conn,
                    (int)$_SESSION['id_usuario'],
                    'entrega_confirmada',
                    'entrega_vehiculo',
                    $idEntrega,
                    $idServicio,
                    'El cliente confirmó la recepción del vehículo.'
                )) {
                    throw new mysqli_sql_exception('No se pudo registrar la auditoría de la confirmación.');
                }
                $conn->commit();
                $mensaje = 'Recepción confirmada. El servicio ahora figura como Entregado.';
                $tipoMensaje = 'success';
            } catch (DomainException $error) {
                $conn->rollback();
                $mensaje = $error->getMessage();
            } catch (mysqli_sql_exception $error) {
                $conn->rollback();
                error_log('Error al confirmar entrega: '.$error->getMessage());
                $mensaje = 'No se pudo completar la entrega.';
            }
        }
    } else {
        $mensaje = 'No tienes permiso para esta acción.';
    }
}

$select = 'SELECT e.id_entrega,e.id_servicio,e.fecha_registro,e.fecha_salida,
                  e.estado_confirmacion_cliente,e.fecha_confirmacion_cliente,
                  s.estado AS estado_servicio,s.tipo_servicio,v.placa,c.id_cliente,c.nombre,c.apellido,
                  r.nombre AS recepcionista,r.apellido AS apellido_recepcionista
           FROM entrega_vehiculo e JOIN servicio s ON s.id_servicio=e.id_servicio
           JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo JOIN cliente c ON c.id_cliente=v.id_cliente
           JOIN recepcionista r ON r.id_recepcionista=e.id_recepcionista';
if ($rol === 'cliente') {
    $stmt = $conn->prepare($select.' WHERE c.id_cliente=? ORDER BY e.fecha_registro DESC');
    $stmt->bind_param('i', $clienteId);
} else {
    $stmt = $conn->prepare($select.' ORDER BY e.fecha_registro DESC');
}
$stmt->execute();
$entregas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$serviciosFinalizados = [];
if ($rol === 'recepcionista') {
    $result = $conn->query(
        "SELECT s.id_servicio,v.placa,c.nombre,c.apellido FROM servicio s
         JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo JOIN cliente c ON c.id_cliente=v.id_cliente
         LEFT JOIN entrega_vehiculo e ON e.id_servicio=s.id_servicio
         WHERE s.estado='Finalizado' AND e.id_entrega IS NULL ORDER BY s.fecha_ingreso"
    );
    $serviciosFinalizados = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Entrega de vehículos</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/module-pages.css?v=20261022"><script src="../js/form-validation.js?v=20261109-confirm" defer></script></head>
<body class="admin-body module-page module-deliveries"><div class="admin-layout"><aside class="admin-sidebar"><div class="sidebar-brand"><h4>Entregas</h4></div><nav class="sidebar-nav">
<?php if ($rol==='cliente'): ?><a href="dashboard_cliente.php">Volver al panel</a><a href="cliente_servicios.php">Mis servicios</a>
<?php elseif ($rol==='administrador'): ?><a href="dashboard_admin.php">Volver al panel</a><a href="admin_servicios.php">Servicios</a>
<?php else: ?><a href="dashboard_recepcionista.php">Volver al panel</a><a href="recepcionista_servicios.php">Servicios</a><?php endif; ?>
<a href="entregas.php" class="active">Entregas</a><a href="logout.php">Salir</a></nav></aside>
<main class="admin-content"><h2 class="section-title">Entrega de vehículos</h2>
<p class="texto-gris">Finalización técnica, registro administrativo y confirmación del cliente son etapas separadas.</p>
<?php if($mensaje!==''): ?><div class="alert alert-<?= htmlspecialchars($tipoMensaje) ?> alerta"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
<?php if($rol==='recepcionista'): ?><div class="dashboard-panel mb-4"><h5 class="section-title">Registrar entrega pendiente</h5>
<form method="POST" class="row g-3"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="iniciar">
<div class="col-md-8"><label class="form-label">Servicio finalizado</label><select name="id_servicio" class="form-select" required><option value="">Seleccione</option><?php foreach($serviciosFinalizados as $s): ?><option value="<?= (int)$s['id_servicio'] ?>">#<?= (int)$s['id_servicio'] ?> · <?= htmlspecialchars($s['placa'].' · '.$s['nombre'].' '.$s['apellido']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4 d-flex align-items-end"><button class="btn btn-verde rounded-pill">Registrar para confirmación</button></div></form></div><?php endif; ?>
<div class="dashboard-panel"><div class="table-responsive"><table class="table table-sm align-middle">
<thead><tr><th>Servicio</th><th>Vehículo</th><?php if($rol!=='cliente'): ?><th>Cliente</th><th>Recepcionista</th><?php endif; ?><th>Registro</th><th>Salida</th><th>Estado</th><?php if($rol==='cliente'): ?><th>Confirmar recepción</th><?php endif; ?></tr></thead><tbody>
<?php foreach($entregas as $e): ?><tr><td>#<?= (int)$e['id_servicio'] ?> · <?= htmlspecialchars($e['estado_servicio']) ?></td><td><?= htmlspecialchars($e['placa']) ?></td>
<?php if($rol!=='cliente'): ?><td><?= htmlspecialchars($e['nombre'].' '.$e['apellido']) ?></td><td><?= htmlspecialchars($e['recepcionista'].' '.$e['apellido_recepcionista']) ?></td><?php endif; ?>
<td><?= htmlspecialchars($e['fecha_registro']) ?></td><td><?= htmlspecialchars($e['fecha_salida']??'Pendiente de confirmación') ?></td><td><?= htmlspecialchars($e['estado_confirmacion_cliente']) ?></td>
<?php if($rol==='cliente'): ?><td><?php if($e['estado_confirmacion_cliente']==='Pendiente'): ?><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="confirmar"><input type="hidden" name="id_entrega" value="<?= (int)$e['id_entrega'] ?>"><button class="btn btn-sm btn-verde">Confirmar recepción</button></form><?php else: ?>Confirmada <?= htmlspecialchars($e['fecha_confirmacion_cliente']??'') ?><?php endif; ?></td><?php endif; ?></tr><?php endforeach; ?>
<?php if(!$entregas): ?><tr><td colspan="8" class="text-muted">No hay entregas registradas.</td></tr><?php endif; ?></tbody></table></div></div></main></div></body></html>
