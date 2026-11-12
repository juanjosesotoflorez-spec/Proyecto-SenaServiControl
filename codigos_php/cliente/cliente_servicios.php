<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'cliente') {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';

$cliente = null;
$stmt = $conn->prepare('SELECT id_cliente FROM cliente WHERE id_usuario = ?');
$stmt->bind_param('i', $_SESSION['id_usuario']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $cliente = $result->fetch_assoc();
}

$servicios = [];
$seguimiento = [];
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

if ($cliente && !$errorFiltro) {
    $id = (int) $cliente['id_cliente'];
    $stmt = $conn->prepare(
        "SELECT s.id_servicio, s.tipo_servicio, s.descripcion, s.estado, s.fecha_ingreso,
                s.fecha_entrega, s.fecha_entrega_estimada, s.fecha_entrega_real,
                v.placa,t.nombre AS tecnico,
                COALESCE(
                    (SELECT sc.estado FROM seguimiento sc
                     WHERE sc.id_servicio = s.id_servicio
                     ORDER BY sc.fecha DESC, sc.id_seguimiento DESC LIMIT 1),
                    s.estado
                ) AS estado_actual
         FROM servicio s
         JOIN vehiculo v ON s.id_vehiculo = v.id_vehiculo
         LEFT JOIN tecnico t ON s.id_tecnico = t.id_tecnico
         WHERE v.id_cliente = ?
           AND (?='' OR CONCAT_WS(' ',s.tipo_servicio,s.descripcion,v.placa,t.nombre) LIKE ?)
           AND (?='' OR s.estado=?)
           AND (?='' OR s.fecha_ingreso>=?)
           AND (?='' OR s.fecha_ingreso<=?)
         ORDER BY s.fecha_ingreso ASC, s.id_servicio ASC"
    );
    $stmt->bind_param('issssssss', $id, $busqueda, $like, $estadoFiltro, $estadoFiltro, $desde, $desde, $hasta, $hasta);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $servicios[] = $row;
    }

    $stmt = $conn->prepare(
        'SELECT sc.id_seguimiento, sc.id_servicio, sc.aprendiz, sc.comentario, sc.estado, sc.fecha,
                CONCAT(t.nombre, \' \', t.apellido) AS tecnico_realizador,
                COALESCE(sc.fecha_hora, TIMESTAMP(sc.fecha)) AS fecha_mostrada
         FROM seguimiento sc
         JOIN servicio s ON s.id_servicio = sc.id_servicio
         JOIN vehiculo v ON v.id_vehiculo = s.id_vehiculo
         LEFT JOIN tecnico t ON t.id_tecnico = sc.id_tecnico
         WHERE v.id_cliente = ?
         ORDER BY sc.fecha ASC, sc.id_seguimiento ASC'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res2 = $stmt->get_result();
    while ($row = $res2->fetch_assoc()) {
        $seguimiento[$row['id_servicio']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis servicios</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
</head>
<body class="admin-body module-page module-customer-services">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4>Cliente</h4></div>
      <nav class="sidebar-nav">
        <a href="../../index.html">Volver al panel</a>
        <a href="cliente_vehiculos.php">Mis vehículos</a>
        <a href="cliente_servicios.php" class="active">Mis servicios</a>
        <a href="historial_servicios.php">Historial</a>
        <a href="notificaciones.php">Notificaciones</a>
        <a href="mantenimientos.php">Mantenimientos</a>
        <a href="reparaciones.php">Reparaciones</a>
        <a href="entregas.php">Entregas</a>
        <a href="logout.php">Salir</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h2 class="section-title">Mis servicios</h2>
      <?php if ($errorFiltro): ?><div class="alert alert-warning">Revisa las fechas seleccionadas; deben ser válidas y la fecha inicial no puede superar la final.</div><?php endif; ?>
      <div class="dashboard-panel mb-4"><form method="GET" class="row g-2 align-items-end">
        <div class="col-lg-5"><label for="q" class="form-label">Buscar servicio</label><input id="q" name="q" class="form-control" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Tipo, descripción, placa o técnico"></div>
        <div class="col-md-4 col-lg-2"><label for="estado" class="form-label">Estado</label><select id="estado" name="estado" class="form-select"><option value="">Todos</option><?php foreach (['Pendiente','En proceso','Finalizado','Entregado'] as $estado): ?><option value="<?= htmlspecialchars($estado) ?>" <?= $estadoFiltro === $estado ? 'selected' : '' ?>><?= htmlspecialchars($estado) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4 col-lg-2"><label for="desde" class="form-label">Desde</label><input id="desde" type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>"></div>
        <div class="col-md-4 col-lg-2"><label for="hasta" class="form-label">Hasta</label><input id="hasta" type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>"></div>
        <div class="col-lg-1"><button class="btn btn-verde rounded-pill">Filtrar</button></div>
      </form></div>
      <div class="dashboard-panel">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>ID</th><th>Servicio / descripción</th><th>Vehículo</th><th>Técnico</th><th>Estado actual</th><th>Ingreso</th><th>Entrega estimada</th><th>Entrega real</th><th>Historial</th></tr></thead>
            <tbody>
            <?php foreach ($servicios as $s): ?>
              <tr>
                <td><?= $s['id_servicio'] ?></td>
                <td><strong><?= htmlspecialchars($s['tipo_servicio']) ?></strong><br><?= nl2br(htmlspecialchars($s['descripcion'] ?? '')) ?></td>
                <td><?= htmlspecialchars($s['placa']) ?></td>
                <td><?= htmlspecialchars($s['tecnico'] ?? 'Sin asignar') ?></td>
                <td><?= htmlspecialchars($s['estado_actual'] ?? 'Sin estado') ?></td>
                <td><?= htmlspecialchars($s['fecha_ingreso']) ?></td>
                <td><?= htmlspecialchars($s['fecha_entrega_estimada'] ?? 'No definida') ?></td>
                <td><?= htmlspecialchars($s['fecha_entrega_real'] ?? (($s['estado_actual'] ?? '') === 'Finalizado' ? 'Finalizado; fecha no registrada' : 'Pendiente')) ?></td>
                <td><a class="btn btn-sm btn-outline-verde" href="historial_servicios.php?servicio=<?= (int)$s['id_servicio'] ?>">Consultar</a></td>
              </tr>
              <?php if (!empty($s['fecha_entrega']) && empty($s['fecha_entrega_estimada']) && empty($s['fecha_entrega_real'])): ?>
                <tr><td colspan="9" class="text-muted small">Servicio #<?= (int) $s['id_servicio'] ?>: existe una fecha de entrega histórica sin clasificar; se conserva sin asumir si era estimada o efectiva (<?= htmlspecialchars($s['fecha_entrega']) ?>).</td></tr>
              <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$servicios): ?><tr><td colspan="9" class="text-muted">No se encontraron servicios con esos filtros.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($servicios): ?>
          <div class="mt-4">
            <h5 class="section-title">Seguimiento del servicio</h5>
            <?php foreach ($servicios as $s): ?>
              <div class="card card-custom mb-3">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                      <h6 class="fw-bold mb-1">Servicio #<?= $s['id_servicio'] ?> — <?= htmlspecialchars($s['placa']) ?></h6>
                      <small class="text-muted">Técnico: <?= htmlspecialchars($s['tecnico'] ?? 'Sin asignar') ?> · Estado actual: <?= htmlspecialchars($s['estado_actual'] ?? 'Sin estado') ?></small>
                    </div>
                  </div>
                  <?php if (!empty($seguimiento[$s['id_servicio']] ?? [])): ?>
                    <ul class="list-group list-group-flush">
                      <?php foreach ($seguimiento[$s['id_servicio']] as $track): ?>
                        <li class="list-group-item">
                          <div class="d-flex justify-content-between">
                            <span class="text-success fw-semibold"><?= htmlspecialchars($track['estado'] ?? 'Sin estado') ?></span>
                            <small class="text-muted"><?= htmlspecialchars($track['fecha_mostrada']) ?></small>
                          </div>
                          <?php if (!empty($track['aprendiz'])): ?><p class="mb-1"><strong>Aprendiz:</strong> <?= htmlspecialchars($track['aprendiz']) ?></p><?php endif; ?>
                          <p class="mb-1"><strong>Técnico responsable:</strong> <?= htmlspecialchars($track['tecnico_realizador'] ?? 'No especificado') ?></p>
                          <p class="mb-0"><?= nl2br(htmlspecialchars($track['comentario'])) ?></p>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php else: ?>
                    <p class="mb-0 text-muted">No hay seguimiento registrado todavía para este servicio.</p>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>
