<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'recepcionista') {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensajes = [
    'fecha_actualizada' => ['success', 'Fecha estimada e historial actualizados; se creó una notificación interna.'],
    'sin_cambios' => ['info', 'La fecha estimada ya tenía ese valor; no se generó un registro duplicado.']
];
$errores = [
    'sesion' => 'La sesión del formulario venció. Recarga la página.',
    'fecha' => 'La fecha estimada debe ser válida y no anterior al ingreso.',
    'servicio' => 'El servicio no existe o ya está finalizado.',
    'guardar_fecha' => 'No se pudo actualizar la fecha estimada. Inténtalo nuevamente.'
];
$mensaje = $mensajes[$_GET['mensaje'] ?? ''] ?? null;
$error = $errores[$_GET['error'] ?? ''] ?? null;

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
$busquedaLike = '%' . $busqueda . '%';
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$servicios = [];
$query = "SELECT s.id_servicio, s.tipo_servicio, s.descripcion, s.estado, s.fecha_ingreso,
                 s.fecha_entrega, s.fecha_entrega_estimada, s.fecha_entrega_real,
                 v.placa, c.nombre AS cliente,c.apellido,
                 (SELECT sc.comentario FROM seguimiento sc
                  WHERE sc.id_servicio = s.id_servicio
                  ORDER BY sc.fecha DESC, sc.id_seguimiento DESC LIMIT 1) AS ultimo_comentario
          FROM servicio s
          JOIN vehiculo v ON s.id_vehiculo = v.id_vehiculo
          JOIN cliente c ON v.id_cliente = c.id_cliente
          WHERE (?='' OR CONCAT_WS(' ',s.tipo_servicio,s.descripcion,v.placa,c.nombre,c.apellido,c.documento,c.telefono) LIKE ?)
            AND (?='' OR s.estado=?)
            AND (?='' OR s.fecha_ingreso>=?)
            AND (?='' OR s.fecha_ingreso<=?)";
$totalServicios = 0;
if (!$errorFiltro) {
    $count = $conn->prepare(
        "SELECT COUNT(*) AS total FROM servicio s JOIN vehiculo v ON s.id_vehiculo=v.id_vehiculo
         JOIN cliente c ON v.id_cliente=c.id_cliente
         WHERE (?='' OR CONCAT_WS(' ',s.tipo_servicio,s.descripcion,v.placa,c.nombre,c.apellido,c.documento,c.telefono) LIKE ?)
           AND (?='' OR s.estado=?) AND (?='' OR s.fecha_ingreso>=?) AND (?='' OR s.fecha_ingreso<=?)"
    );
    $count->bind_param('ssssssss', $busqueda, $busquedaLike, $estadoFiltro, $estadoFiltro, $desde, $desde, $hasta, $hasta);
    $count->execute();
    $totalServicios = (int)$count->get_result()->fetch_assoc()['total'];
}
$totalPages = max(1, (int)ceil($totalServicios / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
if (!$errorFiltro) {
    $stmtServicios = $conn->prepare($query . ' ORDER BY s.fecha_ingreso DESC, s.id_servicio DESC LIMIT ? OFFSET ?');
    $stmtServicios->bind_param(
        'ssssssssii',
        $busqueda, $busquedaLike, $estadoFiltro, $estadoFiltro, $desde, $desde, $hasta, $hasta, $perPage, $offset
    );
    $stmtServicios->execute();
    $servicios = $stmtServicios->get_result()->fetch_all(MYSQLI_ASSOC);
}

$seguimientos = [];
$result = $conn->query(
    'SELECT sc.id_servicio, sc.estado, sc.comentario, sc.fecha, v.placa, s.tipo_servicio,
            c.nombre AS cliente
     FROM seguimiento sc
     JOIN servicio s ON s.id_servicio = sc.id_servicio
     JOIN vehiculo v ON v.id_vehiculo = s.id_vehiculo
     JOIN cliente c ON c.id_cliente = v.id_cliente
     ORDER BY sc.fecha DESC, sc.id_seguimiento DESC'
);
while ($row = $result->fetch_assoc()) {
    $seguimientos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Servicios</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css?v=20261113-brand-green">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261113-brand-green">
  <script src="../js/form-validation.js?v=20261109-confirm" defer></script>
</head>
<body class="admin-body module-page module-reception-services">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4>Recepción</h4></div>
      <nav class="sidebar-nav">
        <a href="../../index.html">Volver al panel</a>
        <a href="recepcionista_clientes.php">Clientes</a>
        <a href="recepcionista_vehiculos.php">Vehículos</a>
        <a href="historial_servicios.php">Historial</a>
        <a href="recepcionista_servicios.php" class="active">Servicios</a>
        <a href="mantenimientos.php">Mantenimientos</a>
        <a href="reparaciones.php">Reparaciones</a>
        <a href="asignaciones_tecnicos.php">Asignaciones</a>
        <a href="entregas.php">Entregas</a>
        <a href="notificaciones.php">Notificaciones</a>
        <a href="registro_servicios.php">Registrar servicio</a>
        <a href="logout.php">Salir</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h2 class="section-title">Servicios</h2>
      <?php if ($mensaje): ?><div class="alert alert-<?= htmlspecialchars($mensaje[0]) ?> alerta"><?= htmlspecialchars($mensaje[1]) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger alerta"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($errorFiltro): ?><div class="alert alert-warning">Revisa las fechas seleccionadas; deben ser válidas y la fecha inicial no puede superar la final.</div><?php endif; ?>
      <div class="dashboard-panel mb-4">
        <form method="GET" class="row g-2 align-items-end">
          <div class="col-lg-4"><label class="form-label" for="q">Buscar</label><input id="q" name="q" class="form-control" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Servicio, placa, cliente o teléfono"></div>
          <div class="col-md-4 col-lg-2"><label class="form-label" for="estado">Estado</label><select id="estado" name="estado" class="form-select"><option value="">Todos</option><?php foreach (['Pendiente','En proceso','Finalizado','Entregado'] as $estado): ?><option value="<?= htmlspecialchars($estado) ?>" <?= $estadoFiltro === $estado ? 'selected' : '' ?>><?= htmlspecialchars($estado) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-4 col-lg-2"><label class="form-label" for="desde">Desde</label><input id="desde" type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>"></div>
          <div class="col-md-4 col-lg-2"><label class="form-label" for="hasta">Hasta</label><input id="hasta" type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>"></div>
          <div class="col-lg-2 d-flex gap-2"><button class="btn btn-verde rounded-pill">Filtrar</button><a href="recepcionista_servicios.php" class="btn btn-outline-verde rounded-pill">Limpiar</a></div>
        </form>
      </div>
      <div class="dashboard-panel">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>ID</th><th>Servicio / descripción</th><th>Vehículo</th><th>Cliente</th><th>Estado</th><th>Ingreso</th><th>Fecha histórica</th><th>Entrega estimada</th><th>Entrega real</th><th>Última novedad</th><th>Planificar</th><th>Historial</th></tr></thead>
            <tbody>
            <?php foreach ($servicios as $s): ?>
              <tr>
                <td><?= $s['id_servicio'] ?></td>
                <td><strong><?= htmlspecialchars($s['tipo_servicio']) ?></strong><br><?= nl2br(htmlspecialchars($s['descripcion'] ?? '')) ?></td>
                <td><?= htmlspecialchars($s['placa']) ?></td>
                <td><?= htmlspecialchars($s['cliente'].' '.$s['apellido']) ?></td>
                <td><?= htmlspecialchars($s['estado'] ?? 'Sin estado') ?></td>
                <td><?= htmlspecialchars($s['fecha_ingreso']) ?></td>
                <td><?= htmlspecialchars($s['fecha_entrega'] ?? '—') ?></td>
                <td><?= htmlspecialchars($s['fecha_entrega_estimada'] ?? 'No definida') ?></td>
                <td><?= htmlspecialchars($s['fecha_entrega_real'] ?? (($s['estado'] ?? '') === 'Finalizado' ? 'Finalizado; fecha no registrada' : 'Pendiente')) ?></td>
                <td><?= htmlspecialchars($s['ultimo_comentario'] ?? 'Sin seguimiento') ?></td>
                <td>
                  <?php if ($s['estado'] !== 'Finalizado'): ?>
                    <form method="POST" action="actualizar_fecha_entrega.php" class="d-flex flex-column gap-1">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                      <input type="hidden" name="id_servicio" value="<?= (int) $s['id_servicio'] ?>">
                      <input type="date" name="fecha_entrega_estimada" class="form-control form-control-sm" min="<?= htmlspecialchars($s['fecha_ingreso']) ?>" value="<?= htmlspecialchars($s['fecha_entrega_estimada'] ?? '') ?>">
                      <button type="submit" class="btn btn-sm btn-outline-verde">Guardar fecha</button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted">Servicio finalizado</span>
                  <?php endif; ?>
                </td>
                <td><a class="btn btn-sm btn-outline-verde" href="historial_servicios.php?servicio=<?= (int)$s['id_servicio'] ?>">Consultar</a></td>
              </tr>
              <?php if (!empty($s['fecha_entrega']) && empty($s['fecha_entrega_estimada']) && empty($s['fecha_entrega_real'])): ?>
                <tr><td colspan="12" class="text-muted small">Servicio #<?= (int) $s['id_servicio'] ?>: fecha histórica sin clasificar, conservada como dato legado: <?= htmlspecialchars($s['fecha_entrega']) ?></td></tr>
              <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$servicios): ?><tr><td colspan="12" class="text-muted">No se encontraron servicios con esos filtros.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($totalPages > 1): ?><nav aria-label="Paginación de servicios"><ul class="pagination justify-content-center mb-0"><?php for ($number = 1; $number <= $totalPages; $number++): ?><li class="page-item <?= $number === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= http_build_query(['q'=>$busqueda,'estado'=>$estadoFiltro,'desde'=>$desde,'hasta'=>$hasta,'p'=>$number]) ?>"><?= $number ?></a></li><?php endfor; ?></ul></nav><?php endif; ?>
      </div>

      <div class="dashboard-panel mt-4">
        <h5 class="section-title">Historial de novedades</h5>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Fecha</th><th>Servicio</th><th>Vehículo</th><th>Cliente</th><th>Estado</th><th>Observación</th></tr></thead>
            <tbody>
              <?php foreach ($seguimientos as $track): ?>
                <tr>
                  <td><?= htmlspecialchars($track['fecha']) ?></td>
                  <td>#<?= (int) $track['id_servicio'] ?> · <?= htmlspecialchars($track['tipo_servicio']) ?></td>
                  <td><?= htmlspecialchars($track['placa']) ?></td>
                  <td><?= htmlspecialchars($track['cliente']) ?></td>
                  <td><?= htmlspecialchars($track['estado'] ?? 'Sin estado') ?></td>
                  <td><?= nl2br(htmlspecialchars($track['comentario'] ?? '')) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$seguimientos): ?><tr><td colspan="6" class="text-muted">Aún no hay novedades registradas.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
