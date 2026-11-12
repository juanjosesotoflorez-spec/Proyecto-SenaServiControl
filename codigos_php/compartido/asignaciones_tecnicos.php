<?php
session_start();
$rol = $_SESSION['rol'] ?? '';
if (!isset($_SESSION['id_usuario']) || !in_array($rol, ['administrador', 'recepcionista', 'tecnico'], true)) {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';
require_once dirname(__DIR__) . '/compartido/auditoria_helper.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$idTecnicoSesion = null;
$idRecepcionista = null;
if ($rol === 'tecnico') {
    $stmt = $conn->prepare('SELECT id_tecnico FROM tecnico WHERE id_usuario=?');
    $stmt->bind_param('i', $_SESSION['id_usuario']);
    $stmt->execute();
    $idTecnicoSesion = (int)($stmt->get_result()->fetch_assoc()['id_tecnico'] ?? 0);
} elseif ($rol === 'recepcionista') {
    $stmt = $conn->prepare('SELECT id_recepcionista FROM recepcionista WHERE id_usuario=?');
    $stmt->bind_param('i', $_SESSION['id_usuario']);
    $stmt->execute();
    $idRecepcionista = (int)($stmt->get_result()->fetch_assoc()['id_recepcionista'] ?? 0);
}

$mensaje = '';
$tipoMensaje = 'danger';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $mensaje = 'La sesión venció. Recarga la página.';
    } elseif ($rol === 'administrador' && ($_POST['action'] ?? '') === 'asignar') {
        http_response_code(403);
        exit('El administrador solo puede consultar las asignaciones.');
    } elseif ($rol === 'tecnico' && ($_POST['action'] ?? '') === 'aceptar' && $idTecnicoSesion > 0) {
        $idAsignacion = (int)($_POST['id_asignacion'] ?? 0);
        $stmt = $conn->prepare(
            'SELECT id_servicio FROM historial_asignacion_tecnico
             WHERE id_asignacion=? AND id_tecnico_nuevo=?'
        );
        $stmt->bind_param('ii', $idAsignacion, $idTecnicoSesion);
        $stmt->execute();
        $servicioAsignado = $stmt->get_result()->fetch_assoc();
        $stmt = $conn->prepare(
            "UPDATE historial_asignacion_tecnico h JOIN servicio s ON s.id_servicio=h.id_servicio
             SET h.estado_aceptacion='Aceptada', h.fecha_aceptacion=NOW()
             WHERE h.id_asignacion=? AND h.id_tecnico_nuevo=? AND h.estado_aceptacion='Pendiente'
               AND s.id_tecnico=h.id_tecnico_nuevo AND s.estado NOT IN ('Finalizado','Entregado')"
        );
        $stmt->bind_param('ii', $idAsignacion, $idTecnicoSesion);
        if ($stmt->execute() && $stmt->affected_rows === 1) {
            $auditOk = registrarActividadSistema(
                $conn,
                (int)$_SESSION['id_usuario'],
                'asignacion_aceptada',
                'asignacion_tecnico',
                $idAsignacion,
                (int)$servicioAsignado['id_servicio'],
                'El técnico aceptó la asignación.'
            );
            $mensaje = $auditOk
                ? 'Asignación aceptada.'
                : 'La asignación se aceptó, pero no pudo registrarse la auditoría.';
            $tipoMensaje = $auditOk ? 'success' : 'warning';
        } else {
            $mensaje = 'La asignación no existe, ya fue respondida o ya no está vigente.';
        }
    } elseif ($rol === 'recepcionista' && ($_POST['action'] ?? '') === 'asignar') {
        $idServicio = (int)($_POST['id_servicio'] ?? 0);
        $idTecnico = (int)($_POST['id_tecnico'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');
        $autorId = $idRecepcionista;
        if ($idServicio <= 0 || $idTecnico <= 0 || !$autorId || $motivo === '') {
            $mensaje = 'Selecciona servicio, técnico e indica el motivo de la asignación.';
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare('SELECT id_tecnico, estado FROM servicio WHERE id_servicio=? FOR UPDATE');
                $stmt->bind_param('i', $idServicio);
                $stmt->execute();
                $servicio = $stmt->get_result()->fetch_assoc();
                $stmt = $conn->prepare('SELECT id_tecnico FROM tecnico WHERE id_tecnico=?');
                $stmt->bind_param('i', $idTecnico);
                $stmt->execute();
                $tecnicoExiste = $stmt->get_result()->num_rows === 1;

                if (!$servicio || !$tecnicoExiste || in_array($servicio['estado'], ['Finalizado','Entregado'], true)) {
                    throw new DomainException('Servicio/técnico no válido o servicio cerrado.');
                }
                $tecnicoAnterior = (int)$servicio['id_tecnico'];
                if ($tecnicoAnterior === $idTecnico) {
                    throw new DomainException('Ese técnico ya es el responsable actual.');
                }
                $stmt = $conn->prepare('UPDATE servicio SET id_tecnico=? WHERE id_servicio=?');
                $stmt->bind_param('ii', $idTecnico, $idServicio);
                $stmt->execute();
                $stmt = $conn->prepare(
                    "INSERT INTO historial_asignacion_tecnico
                     (id_servicio,id_tecnico_anterior,id_tecnico_nuevo,id_recepcionista,motivo)
                     VALUES (?,?,?,?,?)"
                );
                $stmt->bind_param('iiiis', $idServicio, $tecnicoAnterior, $idTecnico, $idRecepcionista, $motivo);
                $stmt->execute();
                $idAsignacion = (int)$conn->insert_id;
                if (!registrarActividadSistema(
                    $conn,
                    (int)$_SESSION['id_usuario'],
                    'tecnico_reasignado',
                    'servicio',
                    $idServicio,
                    $idServicio,
                    'Se reasignó el servicio a otro técnico. Asignación #' . $idAsignacion . '.'
                )) {
                    throw new mysqli_sql_exception('No se pudo registrar la auditoría de la asignación.');
                }
                $conn->commit();
                $mensaje = 'Técnico asignado. El técnico debe aceptar la asignación antes de registrar avances.';
                $tipoMensaje = 'success';
            } catch (DomainException $error) {
                $conn->rollback();
                $mensaje = $error->getMessage();
            } catch (mysqli_sql_exception $error) {
                $conn->rollback();
                error_log('Error al asignar técnico: '.$error->getMessage());
                $mensaje = 'No se pudo guardar la asignación.';
            }
        }
    } else {
        $mensaje = 'No tienes permiso para esta acción.';
    }
}

$result = $conn->query(
    "SELECT s.id_servicio,s.tipo_servicio,s.estado,s.fecha_ingreso,v.placa,
            c.nombre AS cliente,t.id_tecnico,t.nombre AS tecnico,t.apellido,t.especialidad,
            (SELECT COUNT(*) FROM servicio carga
             WHERE carga.id_tecnico=t.id_tecnico AND carga.estado NOT IN ('Finalizado','Entregado')) AS carga_actual,
            (SELECT h.estado_aceptacion FROM historial_asignacion_tecnico h
             WHERE h.id_servicio=s.id_servicio ORDER BY h.fecha_asignacion DESC,h.id_asignacion DESC LIMIT 1) AS estado_aceptacion,
            (SELECT h.id_asignacion FROM historial_asignacion_tecnico h
             WHERE h.id_servicio=s.id_servicio ORDER BY h.fecha_asignacion DESC,h.id_asignacion DESC LIMIT 1) AS id_asignacion
     FROM servicio s JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
     JOIN cliente c ON c.id_cliente=v.id_cliente JOIN tecnico t ON t.id_tecnico=s.id_tecnico
     ORDER BY s.fecha_ingreso DESC,s.id_servicio DESC"
);
$servicios = $result->fetch_all(MYSQLI_ASSOC);
$tecnicos = $conn->query('SELECT id_tecnico,nombre,apellido,especialidad FROM tecnico ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
$historial = [];
$historialResult = $conn->query(
    'SELECT h.id_servicio,h.fecha_asignacion,h.motivo,h.estado_aceptacion,
            a.nombre AS tecnico_anterior,a.apellido AS apellido_anterior,
            n.nombre AS tecnico_nuevo,n.apellido AS apellido_nuevo,
            COALESCE(CONCAT(ad.nombre," ",ad.apellido),CONCAT(r.nombre," ",r.apellido),
                     CONCAT(n.nombre," ",n.apellido)) AS responsable
     FROM historial_asignacion_tecnico h
     LEFT JOIN tecnico a ON a.id_tecnico=h.id_tecnico_anterior
     JOIN tecnico n ON n.id_tecnico=h.id_tecnico_nuevo
     LEFT JOIN administrador ad ON ad.id_administrador=h.id_administrador
     LEFT JOIN recepcionista r ON r.id_recepcionista=h.id_recepcionista
     ORDER BY h.fecha_asignacion DESC,h.id_asignacion DESC'
);
while ($row=$historialResult->fetch_assoc()) $historial[]=$row;
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Asignación de técnicos</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/module-pages.css?v=20261022"><script src="../js/form-validation.js?v=20261109-confirm" defer></script></head>
<body class="admin-body module-page module-assignments"><div class="admin-layout"><aside class="admin-sidebar"><div class="sidebar-brand"><h4>Asignaciones</h4></div><nav class="sidebar-nav">
<?php if($rol==='administrador'): ?><a href="../../index.html">Volver al panel</a><a href="admin_servicios.php">Servicios</a>
<?php elseif($rol==='recepcionista'): ?><a href="../../index.html">Volver al panel</a><a href="recepcionista_servicios.php">Servicios</a>
<?php else: ?><a href="../../index.html">Volver al panel</a><a href="tecnico_servicios.php">Servicios asignados</a><?php endif; ?>
<a href="asignaciones_tecnicos.php" class="active">Asignaciones</a><a href="logout.php">Salir</a></nav></aside>
<main class="admin-content"><h2 class="section-title">Asignaciones y carga de trabajo</h2>
<p class="texto-gris">La carga refleja servicios activos asignados; no equivale a disponibilidad confirmada.</p>
<?php if($mensaje!==''): ?><div class="alert alert-<?= htmlspecialchars($tipoMensaje) ?> alerta"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
<?php if($rol==='recepcionista'): ?><div class="dashboard-panel mb-4"><h5 class="section-title">Asignar o reasignar</h5>
<form method="POST" class="row g-3"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="asignar">
<div class="col-md-5"><label class="form-label">Servicio</label><select name="id_servicio" class="form-select" required><option value="">Seleccione</option>
<?php foreach($servicios as $s): if(!in_array($s['estado'],['Finalizado','Entregado'],true)): ?><option value="<?= (int)$s['id_servicio'] ?>">#<?= (int)$s['id_servicio'] ?> · <?= htmlspecialchars($s['placa'].' · '.$s['cliente'].' · actual: '.$s['tecnico'].' '.$s['apellido']) ?></option><?php endif; endforeach; ?>
</select></div><div class="col-md-4"><label class="form-label">Nuevo técnico</label><select name="id_tecnico" class="form-select" required><option value="">Seleccione</option>
<?php foreach($tecnicos as $t): ?><option value="<?= (int)$t['id_tecnico'] ?>"><?= htmlspecialchars($t['nombre'].' '.$t['apellido'].' · '.($t['especialidad']??'Especialidad no registrada')) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Motivo</label><input name="motivo" class="form-control" maxlength="1000" required></div><div class="col-12"><button class="btn btn-verde rounded-pill">Guardar asignación</button></div></form></div><?php endif; ?>
<?php if($rol==='tecnico'): ?><div class="dashboard-panel mb-4"><h5 class="section-title">Asignaciones pendientes</h5>
<?php foreach($servicios as $s): if((int)$s['id_tecnico']===$idTecnicoSesion && $s['estado_aceptacion']==='Pendiente'): ?><form method="POST" class="d-flex justify-content-between align-items-center border-bottom py-2"><span>#<?= (int)$s['id_servicio'] ?> · <?= htmlspecialchars($s['placa'].' · '.$s['tipo_servicio']) ?></span><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="aceptar"><input type="hidden" name="id_asignacion" value="<?= (int)$s['id_asignacion'] ?>"><button class="btn btn-sm btn-verde">Aceptar asignación</button></form><?php endif; endforeach; ?></div><?php endif; ?>
<div class="dashboard-panel mb-4"><h5 class="section-title">Servicios y responsable actual</h5><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Servicio</th><th>Cliente / vehículo</th><th>Técnico</th><th>Especialidad</th><th>Carga activa</th><th>Aceptación</th></tr></thead><tbody>
<?php foreach($servicios as $s): if($rol!=='tecnico'||(int)$s['id_tecnico']===$idTecnicoSesion): ?><tr><td>#<?= (int)$s['id_servicio'] ?> · <?= htmlspecialchars($s['tipo_servicio'].' · '.($s['estado']??'Sin estado')) ?></td><td><?= htmlspecialchars($s['cliente'].' · '.$s['placa']) ?></td><td><?= htmlspecialchars($s['tecnico'].' '.$s['apellido']) ?></td><td><?= htmlspecialchars($s['especialidad']??'No registrada') ?></td><td><?= (int)$s['carga_actual'] ?></td><td><?= htmlspecialchars($s['estado_aceptacion']??'Asignación histórica sin aceptación registrada') ?></td></tr><?php endif; endforeach; ?>
</tbody></table></div></div>
<?php if($rol!=='tecnico'): ?><div class="dashboard-panel"><h5 class="section-title">Historial de reasignaciones</h5><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Servicio</th><th>Fecha</th><th>Anterior</th><th>Nuevo</th><th>Responsable</th><th>Motivo</th><th>Aceptación</th></tr></thead><tbody>
<?php foreach($historial as $h): ?><tr><td>#<?= (int)$h['id_servicio'] ?></td><td><?= htmlspecialchars($h['fecha_asignacion']) ?></td><td><?= htmlspecialchars(trim(($h['tecnico_anterior']??'').' '.($h['apellido_anterior']??''))?:'Asignación inicial') ?></td><td><?= htmlspecialchars($h['tecnico_nuevo'].' '.$h['apellido_nuevo']) ?></td><td><?= htmlspecialchars($h['responsable']??'No disponible') ?></td><td><?= htmlspecialchars($h['motivo']??'') ?></td><td><?= htmlspecialchars($h['estado_aceptacion']) ?></td></tr><?php endforeach; ?>
<?php if(!$historial): ?><tr><td colspan="7" class="text-muted">No hay reasignaciones registradas.</td></tr><?php endif; ?></tbody></table></div></div><?php endif; ?>
</main></div></body></html>
