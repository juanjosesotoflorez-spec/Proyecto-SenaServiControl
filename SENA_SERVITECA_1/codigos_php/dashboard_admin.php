<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}
require __DIR__ . '/../db.php';

$basePath = '';
$logoutPath = 'logout.php';

$stats = ['usuarios' => 0, 'clientes' => 0, 'vehiculos' => 0, 'servicios' => 0, 'repuestos' => 0, 'solicitudes' => 0];

if ($result = $conn->query('SELECT COUNT(*) AS total FROM usuario')) {
    $stats['usuarios'] = (int) $result->fetch_assoc()['total'];
}
if ($result = $conn->query('SELECT COUNT(*) AS total FROM vehiculo')) {
    $stats['vehiculos'] = (int) $result->fetch_assoc()['total'];
}
if ($result = $conn->query('SELECT COUNT(*) AS total FROM cliente')) {
    $stats['clientes'] = (int) $result->fetch_assoc()['total'];
}
if ($result = $conn->query('SELECT COUNT(*) AS total FROM servicio')) {
    $stats['servicios'] = (int) $result->fetch_assoc()['total'];
}
if ($result = $conn->query('SELECT COUNT(*) AS total FROM repuesto')) {
    $stats['repuestos'] = (int) $result->fetch_assoc()['total'];
}
if ($result = $conn->query("SELECT COUNT(*) AS total FROM solicitud_servicio WHERE estado = 'Pendiente'")) {
    $stats['solicitudes'] = (int) $result->fetch_assoc()['total'];
}

$usuarios = [];
if ($result = $conn->query('SELECT u.id_usuario, u.usuario, u.correo, u.rol, u.estado FROM usuario u ORDER BY u.id_usuario DESC LIMIT 8')) {
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
}

$servicios = [];
if ($result = $conn->query('SELECT s.id_servicio, s.tipo_servicio, s.estado, s.fecha_ingreso, v.placa, c.nombre AS cliente, t.nombre AS tecnico FROM servicio s JOIN vehiculo v ON s.id_vehiculo = v.id_vehiculo JOIN cliente c ON v.id_cliente = c.id_cliente LEFT JOIN tecnico t ON s.id_tecnico = t.id_tecnico ORDER BY s.id_servicio DESC LIMIT 8')) {
    while ($row = $result->fetch_assoc()) {
        $servicios[] = $row;
    }
}

$dashboardRoleLabel = 'Administrador';
$dashboardUserName = $_SESSION['correo'] ?? 'Administrador';
$dashboardNavigation = [
    'Inicio' => [
        ['icon' => '⌂', 'label' => 'Resumen general', 'href' => 'dashboard_admin.php', 'active' => true]
    ],
    'Gestión' => [
        ['icon' => '♙', 'label' => 'Usuarios', 'href' => 'admin_usuarios.php'],
        ['icon' => '♙', 'label' => 'Mi perfil', 'href' => 'perfil_usuario.php'],
        ['icon' => '◎', 'label' => 'Consultar clientes', 'href' => 'admin_clientes.php'],
        ['icon' => '▣', 'label' => 'Consultar vehículos', 'href' => 'admin_vehiculos.php'],
        ['icon' => '▤', 'label' => 'Consultar servicios', 'href' => 'admin_servicios.php'],
        ['icon' => '◷', 'label' => 'Consultar solicitudes', 'href' => 'solicitudes_servicio.php'],
        ['icon' => '◈', 'label' => 'Consultar repuestos', 'href' => 'admin_repuestos.php']
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
  <title>Panel del administrador</title>
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
          <div class="dashboard-topbar-title"><strong>SENA ServiControl</strong><small>Administración · Resumen del sistema</small></div>
        </div>
        <div class="dashboard-user"><span class="dashboard-user-name"><?= htmlspecialchars($dashboardUserName) ?></span><a href="<?= $logoutPath ?>" class="btn btn-outline-verde btn-sm rounded-pill">Cerrar sesión</a></div>
      </header>
      <div class="dashboard-content">
        <section class="dashboard-page-heading"><h1>Resumen general</h1><p>Consulta indicadores, usuarios y datos del sistema. La atención de solicitudes corresponde al equipo técnico.</p></section>
        <section class="dashboard-stat-grid dashboard-stat-grid--six mb-4" aria-label="Indicadores del sistema">
          <div class="dashboard-stat"><span class="dashboard-stat-label">Usuarios</span><strong class="dashboard-stat-value"><?= $stats['usuarios'] ?></strong></div>
          <div class="dashboard-stat"><span class="dashboard-stat-label">Clientes</span><strong class="dashboard-stat-value"><?= $stats['clientes'] ?></strong></div>
          <div class="dashboard-stat"><span class="dashboard-stat-label">Vehículos</span><strong class="dashboard-stat-value"><?= $stats['vehiculos'] ?></strong></div>
          <div class="dashboard-stat"><span class="dashboard-stat-label">Servicios</span><strong class="dashboard-stat-value"><?= $stats['servicios'] ?></strong></div>
          <div class="dashboard-stat"><span class="dashboard-stat-label">Repuestos</span><strong class="dashboard-stat-value"><?= $stats['repuestos'] ?></strong></div>
          <a class="dashboard-stat text-decoration-none" href="solicitudes_servicio.php"><span class="dashboard-stat-label">Solicitudes pendientes</span><strong class="dashboard-stat-value"><?= $stats['solicitudes'] ?></strong></a>
        </section>
        <section class="dashboard-welcome mb-4">
          <div class="dashboard-welcome-main">
            <div class="dashboard-eyebrow">Control administrativo</div>
            <h1>Todo el sistema, en una sola vista.</h1>
            <p class="mb-0">Consulta la información del sistema, administra las cuentas de usuario y revisa los indicadores y reportes.</p>
            <div class="d-flex flex-wrap gap-2 mt-4">
              <a href="admin_usuarios.php" class="btn btn-verde rounded-pill">Gestionar usuarios</a>
              <a href="admin_servicios.php" class="btn btn-outline-verde rounded-pill">Consultar servicios</a>
              <a href="solicitudes_servicio.php" class="btn btn-outline-verde rounded-pill">Consultar solicitudes</a>
              <a href="reportes.php" class="btn btn-outline-verde rounded-pill">Ver reportes</a>
            </div>
          </div>
        </section>
        <section class="row g-4">
          <div class="col-xl-7">
            <div class="card border-0 rounded-4 h-100"><div class="card-body p-4">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div><h2 class="h5 card-title mb-1">Usuarios recientes</h2><p class="small text-muted mb-0">Últimas cuentas registradas.</p></div>
                <a href="admin_usuarios.php" class="btn btn-sm btn-outline-verde rounded-pill">Ver usuarios</a>
              </div>
              <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead><tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th></tr></thead>
                <tbody>
                  <?php foreach ($usuarios as $u): ?>
                    <tr><td><?= htmlspecialchars($u['usuario']) ?></td><td><?= htmlspecialchars($u['correo']) ?></td><td><?= htmlspecialchars($u['rol']) ?></td><td><?= htmlspecialchars($u['estado']) ?></td></tr>
                  <?php endforeach; ?>
                  <?php if (!$usuarios): ?><tr><td colspan="4" class="text-muted">No hay usuarios registrados.</td></tr><?php endif; ?>
                </tbody>
              </table></div>
            </div></div>
          </div>
          <div class="col-xl-5">
            <div class="card border-0 rounded-4 h-100"><div class="card-body p-4">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div><h2 class="h5 card-title mb-1">Servicios recientes</h2><p class="small text-muted mb-0">Actividad registrada en el taller.</p></div>
                <a href="admin_servicios.php" class="btn btn-sm btn-outline-verde rounded-pill">Ver servicios</a>
              </div>
              <?php if ($servicios): ?>
                <ul class="list-group list-group-flush">
                  <?php foreach ($servicios as $s): ?>
                    <li class="list-group-item px-0">
                      <div class="d-flex justify-content-between gap-2"><strong><?= htmlspecialchars($s['tipo_servicio']) ?></strong><span class="badge bg-light text-dark"><?= htmlspecialchars($s['estado'] ?? 'Sin estado') ?></span></div>
                      <small class="text-muted"><?= htmlspecialchars($s['placa']) ?> · <?= htmlspecialchars($s['cliente']) ?> · <?= htmlspecialchars($s['tecnico'] ?? 'Sin técnico asignado') ?> · <?= htmlspecialchars($s['fecha_ingreso']) ?></small>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?><p class="text-muted mb-0">Todavía no hay servicios registrados.</p><?php endif; ?>
            </div></div>
          </div>
        </section>
      </div>
    </main>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/main.js"></script>
</body>
</html>
