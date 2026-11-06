<?php
session_start();
$rol = $_SESSION['rol'] ?? '';
$rolesPermitidos = ['administrador', 'recepcionista', 'tecnico', 'cliente'];
if (!isset($_SESSION['id_usuario']) || !in_array($rol, $rolesPermitidos, true)) {
    header('Location: login.php');
    exit;
}
require __DIR__ . '/../db.php';

function rf101HistorialConsultar(mysqli $conn, string $sql, string $tipos, array $parametros): array
{
    $stmt = $conn->prepare($sql);
    if ($tipos !== '') {
        $argumentos = [$tipos];
        foreach ($parametros as $indice => $valor) {
            $parametros[$indice] = $valor;
            $argumentos[] = &$parametros[$indice];
        }
        call_user_func_array([$stmt, 'bind_param'], $argumentos);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function rf101HistorialFechaValida(string $fecha): bool
{
    $date = DateTime::createFromFormat('!Y-m-d', $fecha);
    return $date !== false && $date->format('Y-m-d') === $fecha;
}

$clienteId = 0;
$tecnicoId = 0;
if ($rol === 'cliente') {
    $perfil = rf101HistorialConsultar(
        $conn,
        'SELECT id_cliente FROM cliente WHERE id_usuario=?',
        'i',
        [(int)$_SESSION['id_usuario']]
    );
    $clienteId = (int)($perfil[0]['id_cliente'] ?? 0);
} elseif ($rol === 'tecnico') {
    $perfil = rf101HistorialConsultar(
        $conn,
        'SELECT id_tecnico FROM tecnico WHERE id_usuario=?',
        'i',
        [(int)$_SESSION['id_usuario']]
    );
    $tecnicoId = (int)($perfil[0]['id_tecnico'] ?? 0);
}

$busqueda = trim($_GET['q'] ?? '');
$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');
$filtroCliente = in_array($rol, ['administrador', 'recepcionista'], true) ? max(0, (int)($_GET['cliente'] ?? 0)) : 0;
$filtroVehiculo = in_array($rol, ['administrador', 'recepcionista'], true) ? max(0, (int)($_GET['vehiculo'] ?? 0)) : 0;
$filtroServicio = max(0, (int)($_GET['servicio'] ?? 0));
$errorFiltro = (($desde !== '' && !rf101HistorialFechaValida($desde))
    || ($hasta !== '' && !rf101HistorialFechaValida($hasta))
    || ($desde !== '' && $hasta !== '' && $desde > $hasta));

$eventosSql = "
    SELECT CAST(s.fecha_ingreso AS DATETIME) AS fecha_evento,'Ingreso de servicio' AS tipo_evento,
           s.id_servicio,v.id_vehiculo,v.id_cliente,s.id_tecnico,v.placa,
           CONCAT(c.nombre,' ',c.apellido) AS cliente,s.tipo_servicio,
           CONCAT('Servicio registrado. Estado: ',COALESCE(s.estado,'Sin estado'),'. ',COALESCE(s.descripcion,'')) AS detalle
    FROM servicio s JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
    JOIN cliente c ON c.id_cliente=v.id_cliente
    UNION ALL
    SELECT COALESCE(sc.fecha_hora,TIMESTAMP(sc.fecha)),'Seguimiento',
           s.id_servicio,v.id_vehiculo,v.id_cliente,sc.id_tecnico,v.placa,
           CONCAT(c.nombre,' ',c.apellido),s.tipo_servicio,
           CONCAT('Estado: ',COALESCE(sc.estado,'Sin estado'),'. ',COALESCE(sc.comentario,''))
    FROM seguimiento sc JOIN servicio s ON s.id_servicio=sc.id_servicio
    JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
    JOIN cliente c ON c.id_cliente=v.id_cliente
    UNION ALL
    SELECT COALESCE(m.fecha_respuesta_autorizacion,TIMESTAMP(m.fecha_programacion),TIMESTAMP(s.fecha_ingreso)),
           'Mantenimiento',s.id_servicio,v.id_vehiculo,v.id_cliente,s.id_tecnico,v.placa,
           CONCAT(c.nombre,' ',c.apellido),s.tipo_servicio,
           CONCAT('Tipo: ',COALESCE(m.tipo_mantenimiento,'No especificado'),
                  '. Trabajo: ',COALESCE(m.estado_trabajo,'Sin estado'),
                  '. Autorización: ',COALESCE(m.estado_autorizacion,'Sin estado'),
                  '. ',COALESCE(m.observaciones,''))
    FROM mantenimiento m JOIN servicio s ON s.id_servicio=m.id_servicio
    JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
    JOIN cliente c ON c.id_cliente=v.id_cliente
    UNION ALL
    SELECT COALESCE(r.fecha_respuesta_presupuesto,TIMESTAMP(s.fecha_ingreso)),
           'Reparación',s.id_servicio,v.id_vehiculo,v.id_cliente,s.id_tecnico,v.placa,
           CONCAT(c.nombre,' ',c.apellido),s.tipo_servicio,
           CONCAT('Daño: ',COALESCE(r.dano_detectado,r.problema_reportado,'No especificado'),
                  '. Solución: ',COALESCE(r.solucion,'Sin solución registrada'),
                  '. Estado: ',COALESCE(r.estado,'Sin estado'),
                  '. Presupuesto: ',COALESCE(r.estado_presupuesto,'Sin respuesta'),
                  '. ',COALESCE(r.observaciones,''))
    FROM reparacion r JOIN servicio s ON s.id_servicio=r.id_servicio
    JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
    JOIN cliente c ON c.id_cliente=v.id_cliente
    UNION ALL
    SELECT h.fecha_hora,'Actividad registrada',s.id_servicio,v.id_vehiculo,v.id_cliente,s.id_tecnico,v.placa,
           CONCAT(c.nombre,' ',c.apellido),s.tipo_servicio,
           CONCAT(h.accion,' · ',h.entidad,': ',h.descripcion)
    FROM historial_actividad_usuario h JOIN servicio s ON s.id_servicio=h.id_servicio
    JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
    JOIN cliente c ON c.id_cliente=v.id_cliente
";
$conditions = [];
$tipos = '';
$parametros = [];
if ($rol === 'cliente') {
    $conditions[] = 'h.id_cliente=?';
    $tipos .= 'i';
    $parametros[] = $clienteId;
}
if ($filtroCliente > 0) {
    $conditions[] = 'h.id_cliente=?';
    $tipos .= 'i';
    $parametros[] = $filtroCliente;
}
if ($filtroVehiculo > 0) {
    $conditions[] = 'h.id_vehiculo=?';
    $tipos .= 'i';
    $parametros[] = $filtroVehiculo;
}
if ($filtroServicio > 0) {
    $conditions[] = 'h.id_servicio=?';
    $tipos .= 'i';
    $parametros[] = $filtroServicio;
}
if ($busqueda !== '') {
    $conditions[] = "CONCAT_WS(' ',h.tipo_evento,h.placa,h.cliente,h.tipo_servicio,h.detalle) LIKE ?";
    $tipos .= 's';
    $parametros[] = '%' . $busqueda . '%';
}
if ($desde !== '' && !$errorFiltro) {
    $conditions[] = 'DATE(h.fecha_evento)>=?';
    $tipos .= 's';
    $parametros[] = $desde;
}
if ($hasta !== '' && !$errorFiltro) {
    $conditions[] = 'DATE(h.fecha_evento)<=?';
    $tipos .= 's';
    $parametros[] = $hasta;
}
if ($errorFiltro || ($rol === 'cliente' && $clienteId === 0) || ($rol === 'tecnico' && $tecnicoId === 0)) {
    $conditions[] = '1=0';
}
$whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$totalEventos = 0;
$eventos = [];
$perPage = 50;
$page = max(1, (int)($_GET['p'] ?? 1));
if (!$errorFiltro) {
    $countRows = rf101HistorialConsultar(
        $conn,
        'SELECT COUNT(*) AS total FROM (' . $eventosSql . ') h' . $whereSql,
        $tipos,
        $parametros
    );
    $totalEventos = (int)($countRows[0]['total'] ?? 0);
}
$totalPages = max(1, (int)ceil($totalEventos / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
if (!$errorFiltro) {
    $eventos = rf101HistorialConsultar(
        $conn,
        'SELECT h.* FROM (' . $eventosSql . ') h' . $whereSql
            . ' ORDER BY h.fecha_evento DESC,h.id_servicio DESC LIMIT ? OFFSET ?',
        $tipos . 'ii',
        array_merge($parametros, [$perPage, $offset])
    );
}

$titulo = $rol === 'cliente' ? 'Mi historial de servicios' : 'Historial de servicios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($titulo) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
</head>
<body class="admin-body module-page module-history">
<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="sidebar-brand"><h4><?= $rol === 'cliente' ? 'Cliente' : ($rol === 'tecnico' ? 'Técnico' : 'Serviteca') ?></h4></div>
    <nav class="sidebar-nav">
      <?php if ($rol === 'administrador'): ?>
        <a href="dashboard_admin.php">Volver al panel</a><a href="admin_clientes.php">Clientes</a><a href="admin_vehiculos.php">Vehículos</a><a href="admin_servicios.php">Servicios</a>
      <?php elseif ($rol === 'recepcionista'): ?>
        <a href="dashboard_recepcionista.php">Volver al panel</a><a href="recepcionista_clientes.php">Clientes</a><a href="recepcionista_vehiculos.php">Vehículos</a><a href="recepcionista_servicios.php">Servicios</a>
      <?php elseif ($rol === 'tecnico'): ?>
        <a href="dashboard_tecnico.php">Volver al panel</a><a href="tecnico_servicios.php">Servicios del taller</a><a href="seguimiento_tecnico.php">Seguimiento</a>
      <?php else: ?>
        <a href="dashboard_cliente.php">Volver al panel</a><a href="cliente_vehiculos.php">Mis vehículos</a><a href="cliente_servicios.php">Mis servicios</a>
      <?php endif; ?>
      <a href="historial_servicios.php" class="active">Historial</a><a href="logout.php">Salir</a>
    </nav>
  </aside>
  <main class="admin-content">
    <h2 class="section-title"><?= htmlspecialchars($titulo) ?></h2>
    <?php if ($errorFiltro): ?><div class="alert alert-warning">El rango de fechas no es válido. Corrígelo y vuelve a consultar.</div><?php endif; ?>
    <div class="dashboard-panel mb-4">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-lg-4"><label for="q" class="form-label">Buscar en historial</label><input id="q" name="q" class="form-control" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Placa, servicio o detalle"></div>
        <?php if (in_array($rol, ['administrador','recepcionista'], true)): ?>
          <div class="col-md-6 col-lg-2"><label for="cliente" class="form-label">ID cliente</label><input id="cliente" type="number" min="1" name="cliente" class="form-control" value="<?= $filtroCliente ?: '' ?>"></div>
          <div class="col-md-6 col-lg-2"><label for="vehiculo" class="form-label">ID vehículo</label><input id="vehiculo" type="number" min="1" name="vehiculo" class="form-control" value="<?= $filtroVehiculo ?: '' ?>"></div>
        <?php endif; ?>
        <div class="col-md-4 col-lg-2"><label for="desde" class="form-label">Desde</label><input id="desde" type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>"></div>
        <div class="col-md-4 col-lg-2"><label for="hasta" class="form-label">Hasta</label><input id="hasta" type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>"></div>
        <?php if ($filtroServicio > 0): ?><input type="hidden" name="servicio" value="<?= $filtroServicio ?>"><?php endif; ?>
        <div class="col-12 d-flex gap-2"><button class="btn btn-verde rounded-pill">Filtrar</button><a href="historial_servicios.php" class="btn btn-outline-verde rounded-pill">Limpiar</a></div>
      </form>
    </div>
    <div class="dashboard-panel">
      <?php if (!$eventos): ?>
        <p class="text-muted mb-0">No hay eventos de servicio para los filtros seleccionados.</p>
      <?php else: ?>
        <div class="table-responsive"><table class="table table-sm align-middle">
          <thead><tr><th>Fecha registrada</th><th>Evento</th><th>Servicio / placa</th><th>Cliente</th><th>Detalle</th></tr></thead>
          <tbody>
          <?php foreach ($eventos as $evento): ?>
            <tr>
              <td><?= htmlspecialchars($evento['fecha_evento']) ?></td>
              <td><?= htmlspecialchars($evento['tipo_evento']) ?></td>
              <td><a href="historial_servicios.php?servicio=<?= (int)$evento['id_servicio'] ?>">#<?= (int)$evento['id_servicio'] ?> · <?= htmlspecialchars($evento['tipo_servicio']) ?></a><br><small><?= htmlspecialchars($evento['placa']) ?></small></td>
              <td><?= htmlspecialchars($evento['cliente']) ?></td>
              <td><?= nl2br(htmlspecialchars(trim($evento['detalle']))) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
      <?php if ($totalPages > 1): ?>
        <nav aria-label="Paginación del historial"><ul class="pagination justify-content-center mb-0">
          <?php for ($number = 1; $number <= $totalPages; $number++): ?>
            <li class="page-item <?= $number === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= http_build_query(['q'=>$busqueda,'cliente'=>$filtroCliente,'vehiculo'=>$filtroVehiculo,'servicio'=>$filtroServicio,'desde'=>$desde,'hasta'=>$hasta,'p'=>$number]) ?>"><?= $number ?></a></li>
          <?php endfor; ?>
        </ul></nav>
      <?php endif; ?>
      <p class="small text-muted mt-3 mb-0">La fecha de reparación se toma de la respuesta del presupuesto; si no existe, se muestra la fecha de ingreso del servicio. Para mantenimientos se usa la respuesta de autorización, luego la programación y, como último recurso, el ingreso.</p>
    </div>
  </main>
</div>
</body>
</html>
