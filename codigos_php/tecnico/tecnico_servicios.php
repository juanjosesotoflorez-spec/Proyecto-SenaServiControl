<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'tecnico') {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';

$tecnico = null;
$stmt = $conn->prepare('SELECT id_tecnico, nombre, apellido FROM tecnico WHERE id_usuario = ?');
$stmt->bind_param('i', $_SESSION['id_usuario']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $tecnico = $result->fetch_assoc();
}

$servicios = [];
$busqueda = trim($_GET['q'] ?? '');
$estadoFiltro = trim($_GET['estado'] ?? '');
$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');
$fechaValida = static function (string $fecha): bool {
    $date = DateTime::createFromFormat('!Y-m-d', $fecha);
    return $date !== false && $date->format('Y-m-d') === $fecha;
};
$errorFiltro = (($desde !== '' && !$fechaValida($desde)) || ($hasta !== '' && !$fechaValida($hasta))
    || ($desde !== '' && $hasta !== '' && $desde > $hasta));
$like = '%' . $busqueda . '%';
if ($tecnico) {
    $query = "SELECT s.id_servicio,s.tipo_servicio,s.descripcion,s.estado,s.fecha_ingreso,s.fecha_cita,
                     v.id_vehiculo,v.placa,c.nombre AS cliente,c.apellido,
                     t.nombre AS tecnico_asignado,t.apellido AS apellido_tecnico
              FROM servicio s JOIN vehiculo v ON s.id_vehiculo=v.id_vehiculo
              JOIN cliente c ON v.id_cliente=c.id_cliente
              LEFT JOIN tecnico t ON s.id_tecnico=t.id_tecnico
              WHERE s.estado IS NOT NULL AND s.estado NOT IN ('Finalizado','Entregado')
                AND (?='' OR CONCAT_WS(' ',s.tipo_servicio,s.descripcion,v.placa,c.nombre,c.apellido,t.nombre,t.apellido) LIKE ?)
                AND (?='' OR s.estado=?) AND (?='' OR s.fecha_ingreso>=?) AND (?='' OR s.fecha_ingreso<=?)
              ORDER BY s.fecha_ingreso DESC,s.id_servicio DESC";
    if (!$errorFiltro) {
        $stmtServicios = $conn->prepare($query);
        $stmtServicios->bind_param(
            'ssssssss',
            $busqueda, $like, $estadoFiltro, $estadoFiltro, $desde, $desde, $hasta, $hasta
        );
        $stmtServicios->execute();
        $servicios = $stmtServicios->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Servicios activos del taller</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
</head>
<body class="admin-body module-page module-workshop-services">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4>Técnico</h4></div>
      <nav class="sidebar-nav">
        <a href="../../index.html">Volver al panel</a>
        <a href="tecnico_servicios.php" class="active">Servicios del taller</a>
        <a href="seguimiento_tecnico.php">Seguimiento</a>
        <a href="mantenimientos.php">Mantenimientos</a>
        <a href="reparaciones.php">Reparaciones</a>
        <a href="asignaciones_tecnicos.php">Asignaciones</a>
        <a href="logout.php">Salir</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h2 class="section-title">Servicios activos del taller</h2>
      <p class="text-muted">Consulta los servicios activos del taller, aunque estén asignados a otro técnico.</p>
      <?php if ($errorFiltro): ?><div class="alert alert-warning">Revisa las fechas seleccionadas; deben ser válidas y la fecha inicial no puede superar la final.</div><?php endif; ?>
      <div class="dashboard-panel mb-4"><form method="GET" class="row g-2 align-items-end">
        <div class="col-lg-5"><label for="q" class="form-label">Buscar servicio</label><input id="q" name="q" class="form-control" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Tipo, descripción, placa o cliente"></div>
        <div class="col-md-4 col-lg-2"><label for="estado" class="form-label">Estado</label><select id="estado" name="estado" class="form-select"><option value="">Todos</option><?php foreach (['Pendiente','En proceso'] as $estado): ?><option value="<?= htmlspecialchars($estado) ?>" <?= $estadoFiltro === $estado ? 'selected' : '' ?>><?= htmlspecialchars($estado) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4 col-lg-2"><label for="desde" class="form-label">Desde</label><input id="desde" type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>"></div>
        <div class="col-md-4 col-lg-2"><label for="hasta" class="form-label">Hasta</label><input id="hasta" type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>"></div>
        <div class="col-lg-1"><button class="btn btn-verde rounded-pill">Filtrar</button></div>
      </form></div>
      <div class="dashboard-panel">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>ID</th><th>Servicio</th><th>Cliente</th><th>Vehículo</th><th>Técnico asignado</th><th>Estado</th><th>Ingreso / cita</th><th>Historial</th></tr></thead>
            <tbody>
            <?php foreach ($servicios as $s): ?>
              <tr>
                <td><?= $s['id_servicio'] ?></td>
                <td><?= htmlspecialchars($s['tipo_servicio']) ?><br><small><?= htmlspecialchars($s['descripcion'] ?? '') ?></small></td>
                <td><?= htmlspecialchars($s['cliente'].' '.$s['apellido']) ?></td>
                <td><?= htmlspecialchars($s['placa']) ?></td>
                <td><?= htmlspecialchars(trim(($s['tecnico_asignado'] ?? '') . ' ' . ($s['apellido_tecnico'] ?? '')) ?: 'Sin asignar') ?></td>
                <td><?= htmlspecialchars($s['estado']) ?></td>
                <td>Ingreso: <?= htmlspecialchars($s['fecha_ingreso']) ?><br><small>Cita: <?= htmlspecialchars($s['fecha_cita'] ?? 'No definida') ?></small></td>
                <td><a class="btn btn-sm btn-outline-verde" href="historial_servicios.php?servicio=<?= (int)$s['id_servicio'] ?>">Consultar</a></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$servicios): ?><tr><td colspan="8" class="text-muted">No se encontraron servicios activos con esos filtros.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
