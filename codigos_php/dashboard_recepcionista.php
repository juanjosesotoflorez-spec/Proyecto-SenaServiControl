<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'recepcionista') {
    header('Location: login.php');
    exit;
}
require __DIR__ . '/../db.php';

$logoutPath = 'logout.php';

$nombre_recepcionista = isset($_SESSION['correo']) ? explode('@', $_SESSION['correo'])[0] : 'recepcionista';

$clientes = [];
if ($result = $conn->query('SELECT c.id_cliente, c.nombre, c.apellido, c.documento, c.telefono, c.correo FROM cliente c ORDER BY c.id_cliente DESC LIMIT 10')) {
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
}

$vehiculos = [];
if ($result = $conn->query('SELECT v.id_vehiculo, v.placa, v.marca, v.modelo, c.nombre AS cliente FROM vehiculo v JOIN cliente c ON v.id_cliente = c.id_cliente ORDER BY v.id_vehiculo DESC LIMIT 10')) {
    while ($row = $result->fetch_assoc()) {
        $vehiculos[] = $row;
    }
}

$totalClientes = (int) $conn->query('SELECT COUNT(*) AS total FROM cliente')->fetch_assoc()['total'];
$totalVehiculos = (int) $conn->query('SELECT COUNT(*) AS total FROM vehiculo')->fetch_assoc()['total'];
$totalServicios = (int) $conn->query('SELECT COUNT(*) AS total FROM servicio')->fetch_assoc()['total'];
$serviciosPendientes = (int) $conn->query("SELECT COUNT(*) AS total FROM servicio WHERE estado IN ('Pendiente', 'En proceso')")->fetch_assoc()['total'];
$solicitudesPendientes = (int) $conn->query("SELECT COUNT(*) AS total FROM solicitud_servicio WHERE estado = 'Pendiente'")->fetch_assoc()['total'];

$serviciosRecientes = [];
if ($result = $conn->query('SELECT s.id_servicio, s.tipo_servicio, s.estado, s.fecha_ingreso, v.placa, c.nombre AS cliente FROM servicio s JOIN vehiculo v ON s.id_vehiculo = v.id_vehiculo JOIN cliente c ON v.id_cliente = c.id_cliente ORDER BY s.id_servicio DESC LIMIT 6')) {
    while ($row = $result->fetch_assoc()) {
        $serviciosRecientes[] = $row;
    }
}

$dashboardRoleLabel = 'Recepcionista';
$dashboardUserName = $_SESSION['correo'] ?? 'Recepcionista';
$dashboardNavigation = [
    'Inicio' => [
        ['icon' => '⌂', 'label' => 'Resumen diario', 'href' => 'dashboard_recepcionista.php', 'active' => true]
    ],
    'Atención' => [
        ['icon' => '+', 'label' => 'Registrar cliente', 'href' => 'registro_cliente.php'],
        ['icon' => '◎', 'label' => 'Consultar clientes', 'href' => 'recepcionista_clientes.php'],
        ['icon' => '▣', 'label' => 'Consultar vehículos', 'href' => 'recepcionista_vehiculos.php'],
        ['icon' => '+', 'label' => 'Registrar vehículo', 'href' => 'registro_recepcionista.php'],
        ['icon' => '▤', 'label' => 'Registrar servicio', 'href' => 'registro_servicios.php'],
        ['icon' => '◷', 'label' => 'Consultar solicitudes', 'href' => 'solicitudes_servicio.php']
    ],
    'Operación' => [
        ['icon' => '♙', 'label' => 'Mi perfil', 'href' => 'perfil_usuario.php'],
        ['icon' => '◷', 'label' => 'Servicios', 'href' => 'recepcionista_servicios.php'],
        ['icon' => '↻', 'label' => 'Mantenimientos', 'href' => 'mantenimientos.php'],
        ['icon' => '⚙', 'label' => 'Reparaciones', 'href' => 'reparaciones.php'],
        ['icon' => '⇄', 'label' => 'Asignaciones', 'href' => 'asignaciones_tecnicos.php'],
        ['icon' => '✓', 'label' => 'Entregas', 'href' => 'entregas.php']
    ],
    'Consultas' => [
        ['icon' => '◷', 'label' => 'Historial de servicios', 'href' => 'historial_servicios.php'],
        ['icon' => '▥', 'label' => 'Reportes', 'href' => 'reportes.php'],
        ['icon' => '◴', 'label' => 'Actividades', 'href' => 'historial_actividades.php'],
        ['icon' => '!', 'label' => 'Incidencias', 'href' => 'incidencias.php'],
        ['icon' => '◉', 'label' => 'Notificaciones', 'href' => 'notificaciones.php']
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel del recepcionista</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-app">
  <div class="dashboard-layout">
    <?php require __DIR__ . '/dashboard_sidebar.php'; ?>
    <main class="dashboard-main">
      <header class="dashboard-topbar">
        <div class="dashboard-topbar-start">
          <button class="btn btn-outline-verde dashboard-menu-toggle" type="button" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Contraer o abrir el menú">☰</button>
          <div class="dashboard-topbar-title"><strong>SENA ServiControl</strong><small>Recepción · Atención y registro</small></div>
        </div>
        <div class="dashboard-user"><span class="dashboard-user-name"><?= htmlspecialchars($dashboardUserName) ?></span><a href="<?= $logoutPath ?>" class="btn btn-outline-verde btn-sm rounded-pill">Cerrar sesión</a></div>
      </header>
      <div class="dashboard-content">
        <section class="dashboard-page-heading"><h1>Atención de recepción</h1><p>Registra información, consulta los datos de clientes y vehículos y da seguimiento a los servicios.</p></section>
        <section class="dashboard-welcome mb-4">
          <div class="dashboard-welcome-main">
            <div class="dashboard-eyebrow">Gestión diaria</div>
            <h1>Hola, <?= htmlspecialchars($nombre_recepcionista) ?>.</h1>
            <p>Empieza una atención nueva o continúa con una consulta desde los accesos directos.</p>
            <div class="d-flex flex-wrap gap-2 mt-4">
              <a href="registro_cliente.php" class="btn btn-verde rounded-pill">Registrar cliente</a>
              <a href="registro_recepcionista.php" class="btn btn-outline-verde rounded-pill">Registrar vehículo</a>
              <a href="registro_servicios.php" class="btn btn-outline-verde rounded-pill">Registrar servicio</a>
              <a href="solicitudes_servicio.php" class="btn btn-outline-verde rounded-pill">Consultar solicitudes</a>
              <a href="recepcionista_clientes.php" class="btn btn-outline-verde rounded-pill">Buscar cliente</a>
              <a href="recepcionista_vehiculos.php" class="btn btn-outline-verde rounded-pill">Consultar vehículos</a>
              <a href="recepcionista_servicios.php" class="btn btn-outline-verde rounded-pill">Consultar servicios</a>
            </div>
          </div>
        </section>
        <section class="row g-3 mb-4" aria-label="Resumen de recepción">
          <div class="col-6 col-xl-3"><div class="dashboard-stat"><span class="dashboard-stat-label">Clientes registrados</span><strong class="dashboard-stat-value"><?= $totalClientes ?></strong></div></div>
          <div class="col-6 col-xl-3"><div class="dashboard-stat"><span class="dashboard-stat-label">Vehículos</span><strong class="dashboard-stat-value"><?= $totalVehiculos ?></strong></div></div>
          <div class="col-6 col-xl-3"><div class="dashboard-stat"><span class="dashboard-stat-label">Servicios registrados</span><strong class="dashboard-stat-value"><?= $totalServicios ?></strong></div></div>
          <div class="col-6 col-xl-3"><div class="dashboard-stat"><span class="dashboard-stat-label">Pendientes o en proceso</span><strong class="dashboard-stat-value"><?= $serviciosPendientes ?></strong></div></div>
          <div class="col-6 col-xl-3"><a class="dashboard-stat text-decoration-none" href="solicitudes_servicio.php"><span class="dashboard-stat-label">Solicitudes nuevas</span><strong class="dashboard-stat-value"><?= $solicitudesPendientes ?></strong></a></div>
        </section>
        <section class="row g-4 mb-4">
          <div class="col-lg-6">
            <div class="card border-0 rounded-4 h-100"><div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><div><h2 class="h5 card-title mb-1">Clientes recientes</h2><p class="small text-muted mb-0">Consulta datos de contacto y busca otros registros.</p></div><a href="recepcionista_clientes.php" class="btn btn-sm btn-outline-verde rounded-pill">Ver clientes</a></div>
              <?php if ($clientes): ?><ul class="list-group list-group-flush">
                <?php foreach ($clientes as $c): ?><li class="list-group-item px-0"><strong><?= htmlspecialchars($c['nombre'].' '.$c['apellido']) ?></strong><br><small class="text-muted">Documento: <?= htmlspecialchars($c['documento']) ?> · <?= htmlspecialchars($c['telefono'] ?? 'Sin teléfono') ?></small></li><?php endforeach; ?>
              </ul><?php else: ?><p class="text-muted mb-0">No hay clientes registrados todavía.</p><?php endif; ?>
            </div></div>
          </div>
          <div class="col-lg-6">
            <div class="card border-0 rounded-4 h-100"><div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><div><h2 class="h5 card-title mb-1">Vehículos recientes</h2><p class="small text-muted mb-0">Revisa placas y propietarios.</p></div><a href="recepcionista_vehiculos.php" class="btn btn-sm btn-outline-verde rounded-pill">Ver vehículos</a></div>
              <?php if ($vehiculos): ?><ul class="list-group list-group-flush">
                <?php foreach ($vehiculos as $v): ?><li class="list-group-item px-0"><strong><?= htmlspecialchars($v['placa']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($v['marca'].' '.$v['modelo']) ?> · <?= htmlspecialchars($v['cliente']) ?></small></li><?php endforeach; ?>
              </ul><?php else: ?><p class="text-muted mb-0">No hay vehículos registrados todavía.</p><?php endif; ?>
            </div></div>
          </div>
        </section>
        <section class="card border-0 rounded-4">
          <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h2 class="h5 card-title mb-1">Servicios recientes</h2><p class="small text-muted mb-0">Revisa estado y fecha de ingreso.</p></div><a href="recepcionista_servicios.php" class="btn btn-sm btn-outline-verde rounded-pill">Ver todos los servicios</a></div>
            <?php if ($serviciosRecientes): ?><div class="table-responsive"><table class="table table-sm align-middle mb-0">
              <thead><tr><th>ID</th><th>Tipo</th><th>Cliente</th><th>Vehículo</th><th>Estado</th><th>Fecha</th></tr></thead>
              <tbody><?php foreach ($serviciosRecientes as $s): ?><tr><td><?= (int)$s['id_servicio'] ?></td><td><?= htmlspecialchars($s['tipo_servicio']) ?></td><td><?= htmlspecialchars($s['cliente']) ?></td><td><?= htmlspecialchars($s['placa']) ?></td><td><span class="badge bg-light text-dark"><?= htmlspecialchars($s['estado'] ?? 'Sin estado') ?></span></td><td><?= htmlspecialchars($s['fecha_ingreso']) ?></td></tr><?php endforeach; ?></tbody>
            </table></div><?php else: ?><p class="text-muted mb-0">No hay servicios registrados aún.</p><?php endif; ?>
          </div>
        </section>
      </div>
      <footer class="footer-sena py-3 mt-4"><div class="container-fluid small text-center">SENA ServiControl · Recepción organizada para cada atención.</div></footer>
    </main>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/main.js"></script>
</body>
</html>
