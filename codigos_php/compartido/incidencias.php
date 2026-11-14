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

$mensaje = '';
$tipoMensaje = 'danger';
$idUsuario = (int)$_SESSION['id_usuario'];
$tecnicoId = 0;
if ($rol === 'tecnico') {
    $stmt = $conn->prepare('SELECT id_tecnico FROM tecnico WHERE id_usuario=?');
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $tecnicoId = (int)($stmt->get_result()->fetch_assoc()['id_tecnico'] ?? 0);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($rol === 'administrador') {
        http_response_code(403);
        exit('El administrador solo puede consultar las incidencias.');
    }

    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $mensaje = 'La sesión venció. Recarga la página.';
    } elseif (($_POST['accion'] ?? '') === 'crear' && in_array($rol, ['recepcionista', 'tecnico'], true)) {
        $descripcion = trim($_POST['descripcion'] ?? '');
        $idServicio = (int)($_POST['id_servicio'] ?? 0);
        if ($descripcion === '' || strlen($descripcion) > 10000 || $idServicio < 0) {
            $mensaje = 'Describe la incidencia y selecciona un servicio válido si corresponde.';
        } else {
            if ($idServicio > 0 && $rol === 'tecnico') {
                if ($tecnicoId <= 0) {
                    $idServicio = -1;
                } else {
                    $stmt = $conn->prepare('SELECT id_servicio FROM servicio WHERE id_servicio=? AND id_tecnico=?');
                    $stmt->bind_param('ii', $idServicio, $tecnicoId);
                    $stmt->execute();
                    if (!$stmt->get_result()->fetch_assoc()) {
                        $idServicio = -1;
                    }
                }
            }
            if ($idServicio < 0) {
                $mensaje = 'Solo puedes reportar incidencias de servicios asignados a ti.';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO incidencia (id_usuario_reporta,id_servicio,descripcion)
                     VALUES (?,NULLIF(?,0),?)"
                );
                $stmt->bind_param('iis', $idUsuario, $idServicio, $descripcion);
                if ($stmt->execute()) {
                    $idIncidencia = (int)$stmt->insert_id;
                    $auditOk = registrarActividadSistema(
                        $conn,
                        $idUsuario,
                        'incidencia_creada',
                        'incidencia',
                        $idIncidencia,
                        $idServicio > 0 ? $idServicio : null,
                        'Se registró una incidencia operativa.'
                    );
                    $mensaje = $auditOk
                        ? 'Incidencia registrada.'
                        : 'La incidencia se guardó, pero no pudo registrarse en el historial de auditoría.';
                    $tipoMensaje = $auditOk ? 'success' : 'warning';
                } else {
                    $mensaje = 'No se pudo registrar la incidencia.';
                }
            }
        }
    } elseif ($rol === 'recepcionista' && ($_POST['accion'] ?? '') === 'actualizar') {
        $idIncidencia = (int)($_POST['id_incidencia'] ?? 0);
        $estado = $_POST['estado'] ?? '';
        $nota = trim($_POST['nota_atencion'] ?? '');
        $estadosPermitidos = ['Abierta', 'En seguimiento', 'Resuelta'];
        if ($idIncidencia <= 0 || !in_array($estado, $estadosPermitidos, true) || strlen($nota) > 10000) {
            $mensaje = 'Los datos de atención no son válidos.';
        } else {
            $actual = $conn->prepare(
                'SELECT estado,nota_atencion,id_usuario_atendio,id_servicio
                 FROM incidencia WHERE id_incidencia=?'
            );
            $actual->bind_param('i', $idIncidencia);
            $actual->execute();
            $incidenciaActual = $actual->get_result()->fetch_assoc();
            if (!$incidenciaActual) {
                $mensaje = 'No se encontró la incidencia solicitada.';
            } elseif (
                $incidenciaActual['estado'] === $estado &&
                (string)($incidenciaActual['nota_atencion'] ?? '') === $nota &&
                (int)($incidenciaActual['id_usuario_atendio'] ?? 0) === $idUsuario
            ) {
                $mensaje = 'La incidencia ya tiene esos datos; no se realizaron cambios.';
                $tipoMensaje = 'success';
            } else {
                $stmt = $conn->prepare(
                    'UPDATE incidencia SET estado=?,nota_atencion=?,id_usuario_atendio=? WHERE id_incidencia=?'
                );
                $stmt->bind_param('ssii', $estado, $nota, $idUsuario, $idIncidencia);
                if ($stmt->execute() && $stmt->affected_rows === 1) {
                    $auditOk = registrarActividadSistema(
                        $conn,
                        $idUsuario,
                        'incidencia_actualizada',
                        'incidencia',
                        $idIncidencia,
                        $incidenciaActual['id_servicio'] === null
                            ? null
                            : (int)$incidenciaActual['id_servicio'],
                        'Se actualizó el estado de atención de una incidencia a ' . $estado . '.'
                    );
                    $mensaje = $auditOk
                        ? 'Atención de incidencia actualizada.'
                        : 'La incidencia se actualizó, pero no pudo registrarse en el historial de auditoría.';
                    $tipoMensaje = $auditOk ? 'success' : 'warning';
                } else {
                    $mensaje = 'No se pudo actualizar la incidencia solicitada.';
                }
            }
        }
    } else {
        $mensaje = 'No tienes permiso para esta acción.';
    }
}

$sqlIncidencias =
    'SELECT i.id_incidencia,i.id_servicio,i.descripcion,i.estado,i.nota_atencion,
            i.fecha_registro,i.fecha_actualizacion,
            reporta.usuario AS usuario_reporta,atendio.usuario AS usuario_atendio
     FROM incidencia i
     JOIN usuario reporta ON reporta.id_usuario=i.id_usuario_reporta
     LEFT JOIN usuario atendio ON atendio.id_usuario=i.id_usuario_atendio';
if ($rol === 'tecnico') {
    $sqlIncidencias .= ' WHERE i.id_usuario_reporta=? OR i.id_servicio IN (SELECT id_servicio FROM servicio WHERE id_tecnico=?)';
    $stmtIncidencias = $conn->prepare(
        $sqlIncidencias . ' ORDER BY FIELD(i.estado,"Abierta","En seguimiento","Resuelta"),i.fecha_registro DESC'
    );
    $stmtIncidencias->bind_param('ii', $idUsuario, $tecnicoId);
    $stmtIncidencias->execute();
    $incidencias = $stmtIncidencias->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $incidencias = $conn->query(
        $sqlIncidencias . ' ORDER BY FIELD(i.estado,"Abierta","En seguimiento","Resuelta"),i.fecha_registro DESC'
    )->fetch_all(MYSQLI_ASSOC);
}
$sqlServicios = "SELECT id_servicio,estado FROM servicio
                 WHERE (estado IS NULL OR estado NOT IN ('Finalizado','Entregado'))";
if ($rol === 'tecnico') {
    $stmtServicios = $conn->prepare($sqlServicios . ' AND id_tecnico=? ORDER BY fecha_ingreso DESC');
    $stmtServicios->bind_param('i', $tecnicoId);
    $stmtServicios->execute();
    $servicios = $stmtServicios->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $servicios = $conn->query($sqlServicios . ' ORDER BY fecha_ingreso DESC')->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Incidencias</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="../css/style.css?v=20261113-brand-green"><link rel="stylesheet" href="../css/module-pages.css?v=20261113-brand-green"><script src="../js/form-validation.js?v=20261109-confirm" defer></script></head>
<body class="admin-body module-page module-incidents"><div class="admin-layout">
<aside class="admin-sidebar"><div class="sidebar-brand"><h4>Incidencias</h4></div><nav class="sidebar-nav">
<?php if ($rol === 'administrador'): ?><a href="../../index.html">Volver al panel</a>
<?php elseif ($rol === 'recepcionista'): ?><a href="../../index.html">Volver al panel</a>
<?php else: ?><a href="../../index.html">Volver al panel</a><?php endif; ?>
<a href="reportes.php">Reportes e indicadores</a><?php if ($rol !== 'tecnico'): ?><a href="historial_actividades.php">Historial de actividades</a><?php endif; ?>
<a href="incidencias.php" class="active">Incidencias</a><a href="logout.php">Salir</a>
</nav></aside>
<main class="admin-content"><h2 class="section-title">Incidencias y novedades</h2>
<?php if ($mensaje !== ''): ?><div class="alert alert-<?= htmlspecialchars($tipoMensaje) ?>"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
<?php if ($rol !== 'administrador'): ?><div class="dashboard-panel mb-4"><h5 class="section-title">Registrar incidencia</h5>
<form method="POST" class="row g-3"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="accion" value="crear">
<div class="col-md-4"><label class="form-label">Servicio (opcional)</label><select name="id_servicio" class="form-select"><option value="0">Sin servicio asociado</option><?php foreach ($servicios as $servicio): ?><option value="<?= (int)$servicio['id_servicio'] ?>">#<?= (int)$servicio['id_servicio'] ?> · <?= htmlspecialchars($servicio['estado'] ?? 'Sin estado') ?></option><?php endforeach; ?></select></div>
<div class="col-md-8"><label class="form-label">Descripción</label><textarea name="descripcion" class="form-control" rows="2" maxlength="10000" required></textarea></div>
<div class="col-12"><button class="btn btn-verde rounded-pill">Guardar incidencia</button></div></form></div><?php endif; ?>
<div class="dashboard-panel"><div class="table-responsive"><table class="table table-sm align-middle">
<thead><tr><th>Fecha</th><th>Reportó</th><th>Servicio</th><th>Descripción</th><th>Estado</th><th>Atención</th><?php if ($rol === 'recepcionista'): ?><th>Actualizar</th><?php endif; ?></tr></thead><tbody>
<?php foreach ($incidencias as $incidencia): ?><tr><td><?= htmlspecialchars($incidencia['fecha_registro']) ?></td><td><?= htmlspecialchars($incidencia['usuario_reporta']) ?></td><td><?= $incidencia['id_servicio'] === null ? '—' : '#'.(int)$incidencia['id_servicio'] ?></td><td><?= nl2br(htmlspecialchars($incidencia['descripcion'])) ?></td><td><?= htmlspecialchars($incidencia['estado']) ?></td><td><?= htmlspecialchars($incidencia['nota_atencion'] ?? '') ?><br><small><?= htmlspecialchars($incidencia['usuario_atendio'] ?? '') ?> · <?= htmlspecialchars($incidencia['fecha_actualizacion']) ?></small></td>
<?php if ($rol === 'recepcionista'): ?><td><form method="POST" class="d-grid gap-1"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="accion" value="actualizar"><input type="hidden" name="id_incidencia" value="<?= (int)$incidencia['id_incidencia'] ?>"><select name="estado" class="form-select form-select-sm"><option <?= $incidencia['estado']==='Abierta'?'selected':'' ?>>Abierta</option><option <?= $incidencia['estado']==='En seguimiento'?'selected':'' ?>>En seguimiento</option><option <?= $incidencia['estado']==='Resuelta'?'selected':'' ?>>Resuelta</option></select><textarea name="nota_atencion" class="form-control form-control-sm" maxlength="10000" placeholder="Nota de atención"><?= htmlspecialchars($incidencia['nota_atencion'] ?? '') ?></textarea><button class="btn btn-sm btn-outline-verde">Actualizar</button></form></td><?php endif; ?></tr><?php endforeach; ?>
<?php if (!$incidencias): ?><tr><td colspan="<?= $rol === 'recepcionista' ? 7 : 6 ?>" class="text-muted">No hay incidencias registradas.</td></tr><?php endif; ?>
</tbody></table></div></div></main></div></body></html>
