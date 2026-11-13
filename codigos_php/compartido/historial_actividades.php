<?php
session_start();
$rol = $_SESSION['rol'] ?? '';
if (!isset($_SESSION['id_usuario']) || !in_array($rol, ['administrador', 'recepcionista', 'cliente'], true)) {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';

$clienteId = 0;
if ($rol === 'cliente') {
    $stmt = $conn->prepare('SELECT id_cliente FROM cliente WHERE id_usuario=?');
    $stmt->bind_param('i', $_SESSION['id_usuario']);
    $stmt->execute();
    $clienteId = (int)($stmt->get_result()->fetch_assoc()['id_cliente'] ?? 0);
}

$where = '';
$tipos = '';
$parametros = [];
if ($rol === 'recepcionista') {
    $where = " WHERE h.entidad IN ('cliente','vehiculo','servicio')";
} elseif ($rol === 'cliente') {
    $where = " WHERE (
        (h.id_servicio IS NOT NULL AND EXISTS (
            SELECT 1 FROM servicio s JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
            WHERE s.id_servicio=h.id_servicio AND v.id_cliente=?
        ))
        OR (h.entidad='cliente' AND h.id_registro=?)
        OR (h.entidad='vehiculo' AND EXISTS (
            SELECT 1 FROM vehiculo v WHERE v.id_vehiculo=h.id_registro AND v.id_cliente=?
        ))
    )";
    $tipos = 'iii';
    $parametros = [$clienteId, $clienteId, $clienteId];
}
$sql = 'SELECT h.fecha_hora,h.accion,h.entidad,h.id_registro,h.id_servicio,h.descripcion,
               u.usuario,u.rol
        FROM historial_actividad_usuario h
        LEFT JOIN usuario u ON u.id_usuario=h.id_usuario'
    . $where . ' ORDER BY h.fecha_hora DESC,h.id_actividad DESC LIMIT 250';
$stmt = $conn->prepare($sql);
if ($tipos !== '') {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$actividades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Historial de actividades</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="../css/style.css?v=20261113-brand-green"><link rel="stylesheet" href="../css/module-pages.css?v=20261113-brand-green"></head>
<body class="admin-body module-page module-activity"><div class="admin-layout">
<aside class="admin-sidebar"><div class="sidebar-brand"><h4>Actividad</h4></div><nav class="sidebar-nav">
<?php if ($rol === 'administrador'): ?><a href="../../index.html">Volver al panel</a>
<?php elseif ($rol === 'recepcionista'): ?><a href="../../index.html">Volver al panel</a>
<?php else: ?><a href="../../index.html">Volver al panel</a><a href="cliente_servicios.php">Mis servicios</a><?php endif; ?>
<a href="reportes.php">Reportes e indicadores</a><a href="historial_actividades.php" class="active">Historial de actividades</a><a href="logout.php">Salir</a>
</nav></aside>
<main class="admin-content"><h2 class="section-title">Historial de actividades</h2>
<p class="text-muted"><?= $rol === 'cliente' ? 'Acciones relacionadas únicamente con tus vehículos y servicios.' : 'Acciones registradas desde la activación de la auditoría.' ?></p>
<div class="dashboard-panel"><div class="table-responsive"><table class="table table-sm align-middle">
<thead><tr><th>Fecha y hora</th><?php if ($rol !== 'cliente'): ?><th>Usuario</th><th>Rol</th><?php endif; ?><th>Acción</th><th>Registro</th><th>Servicio</th><th>Detalle</th></tr></thead>
<tbody><?php foreach ($actividades as $actividad): ?><tr>
<td><?= htmlspecialchars($actividad['fecha_hora']) ?></td>
<?php if ($rol !== 'cliente'): ?><td><?= htmlspecialchars($actividad['usuario'] ?? 'Usuario no disponible') ?></td><td><?= htmlspecialchars($actividad['rol'] ?? '') ?></td><?php endif; ?>
<td><?= htmlspecialchars($actividad['accion']) ?></td>
<td><?= htmlspecialchars($actividad['entidad']) ?> #<?= htmlspecialchars($actividad['id_registro'] ?? '—') ?></td>
<td><?= $actividad['id_servicio'] === null ? '—' : '#'.(int)$actividad['id_servicio'] ?></td>
<td><?= nl2br(htmlspecialchars($actividad['descripcion'])) ?></td>
</tr><?php endforeach; ?>
<?php if (!$actividades): ?><tr><td colspan="<?= $rol === 'cliente' ? 5 : 7 ?>" class="text-muted">No hay actividades registradas para mostrar.</td></tr><?php endif; ?>
</tbody></table></div></div></main></div></body></html>
