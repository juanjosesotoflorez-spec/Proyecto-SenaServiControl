<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'tecnico') {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';

$logoutPath = 'logout.php';

$tecnico = null;
$stmt = $conn->prepare('SELECT id_tecnico, nombre, apellido FROM tecnico WHERE id_usuario = ?');
$stmt->bind_param('i', $_SESSION['id_usuario']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $tecnico = $result->fetch_assoc();
}

$servicios = [];
if ($tecnico) {
    $id = (int)$tecnico['id_tecnico'];
    $stmtServicios = $conn->prepare(
        'SELECT s.id_servicio,s.tipo_servicio,s.estado,s.fecha_ingreso,s.fecha_cita,v.placa,c.nombre AS cliente
         FROM servicio s JOIN vehiculo v ON s.id_vehiculo=v.id_vehiculo
         JOIN cliente c ON v.id_cliente=c.id_cliente
         WHERE s.id_tecnico=? ORDER BY s.fecha_ingreso DESC,s.id_servicio DESC LIMIT 8'
    );
    $stmtServicios->bind_param('i', $id);
    $stmtServicios->execute();
    $servicios = $stmtServicios->get_result()->fetch_all(MYSQLI_ASSOC);
}

$totalServiciosAsignados = 0;
$serviciosPendientes = 0;
$serviciosProceso = 0;
$serviciosFinalizados = 0;
$solicitudesPendientes = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM solicitud_servicio WHERE estado = 'Pendiente' AND id_servicio IS NULL"
)->fetch_assoc()['total'];
if ($tecnico) {
    $id = (int)$tecnico['id_tecnico'];
    $totales = $conn->prepare(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN estado='Pendiente' THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN estado='En proceso' THEN 1 ELSE 0 END) AS proceso,
                SUM(CASE WHEN estado='Finalizado' THEN 1 ELSE 0 END) AS finalizados
         FROM servicio WHERE id_tecnico=?"
    );
    $totales->bind_param('i', $id);
    $totales->execute();
    $stats = $totales->get_result()->fetch_assoc();
    $totalServiciosAsignados = (int)($stats['total'] ?? 0);
    $serviciosPendientes = (int)($stats['pendientes'] ?? 0);
    $serviciosProceso = (int)($stats['proceso'] ?? 0);
    $serviciosFinalizados = (int)($stats['finalizados'] ?? 0);
}

$nombre_tecnico = $tecnico ? $tecnico['nombre'] : 'técnico';
$dashboardRoleLabel = 'Técnico';
$dashboardUserName = $tecnico ? trim($tecnico['nombre'] . ' ' . $tecnico['apellido']) : ($_SESSION['correo'] ?? 'Técnico');
$dashboardNavigation = [
    'Inicio' => [
        ['icon' => '⌂', 'label' => 'Resumen de trabajo', 'href' => 'dashboard_tecnico.php', 'active' => true]
    ],
    'Trabajo' => [
        ['icon' => '♙', 'label' => 'Mi perfil', 'href' => 'perfil_usuario.php'],
        ['icon' => '◷', 'label' => 'Solicitudes de clientes', 'href' => 'solicitudes_servicio.php'],
        ['icon' => '▤', 'label' => 'Servicios del taller', 'href' => 'tecnico_servicios.php'],
        ['icon' => '✓', 'label' => 'Registrar seguimiento', 'href' => 'seguimiento_tecnico.php'],
        ['icon' => '↻', 'label' => 'Mantenimientos', 'href' => 'mantenimientos.php'],
        ['icon' => '⚙', 'label' => 'Consultar reparaciones', 'href' => 'reparaciones.php'],
        ['icon' => '⚒', 'label' => 'Gestión técnica de reparaciones', 'href' => 'tecnico_reparaciones.php'],
        ['icon' => '⇄', 'label' => 'Asignaciones', 'href' => 'asignaciones_tecnicos.php']
    ],
    'Consultas' => [
        ['icon' => '◷', 'label' => 'Historial de servicios', 'href' => 'historial_servicios.php'],
        ['icon' => '▥', 'label' => 'Indicadores', 'href' => 'reportes.php'],
        ['icon' => '!', 'label' => 'Reportar incidencia', 'href' => 'incidencias.php']
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel del técnico</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-app">
  <div class="dashboard-layout">
    <?php require dirname(__DIR__) . '/compartido/dashboard_sidebar.php'; ?>
    <main class="dashboard-main">
      <header class="dashboard-topbar">
        <div class="dashboard-topbar-start">
          <button class="btn btn-outline-verde dashboard-menu-toggle" type="button" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Contraer o abrir el menú">☰</button>
          <div class="dashboard-topbar-title"><strong>SENA ServiControl</strong><small>Área técnica · Servicios del taller</small></div>
        </div>
        <div class="dashboard-user"><span class="dashboard-user-name"><?= htmlspecialchars($dashboardUserName) ?></span><a href="<?= $logoutPath ?>" class="btn btn-outline-verde btn-sm rounded-pill">Cerrar sesión</a></div>
      </header>
      <div class="dashboard-content">
        <section class="dashboard-page-heading"><h1>Mi trabajo en el taller</h1><p>Consulta los servicios activos del taller y registra el avance indicando qué técnico realizó cada trabajo.</p></section>
        <?php if (!$tecnico): ?><div class="alert alert-warning alerta">Tu usuario todavía no está asociado a un perfil técnico. Contacta al administrador para habilitar las consultas de trabajo.</div><?php endif; ?>
        <section class="dashboard-welcome mb-4">
          <div class="dashboard-welcome-main">
            <div class="dashboard-eyebrow">Espacio de trabajo técnico</div>
            <h1>Hola, <?= htmlspecialchars($nombre_tecnico) ?>.</h1>
            <p>Consulta los servicios activos del taller, registra novedades e indica qué técnico realizó cada trabajo.</p>
            <div class="d-flex flex-wrap gap-2 mt-4">
              <a href="tecnico_servicios.php" class="btn btn-verde rounded-pill">Ver servicios activos</a>
              <a href="solicitudes_servicio.php" class="btn btn-outline-verde rounded-pill">Revisar solicitudes</a>
              <a href="seguimiento_tecnico.php" class="btn btn-outline-verde rounded-pill">Registrar seguimiento</a>
              <a href="historial_servicios.php" class="btn btn-outline-verde rounded-pill">Consultar historial</a>
            </div>
          </div>
        </section>
        <section class="dashboard-stat-grid dashboard-stat-grid--five mb-4" aria-label="Resumen del taller">
          <div class="dashboard-stat"><span class="dashboard-stat-label">Asignados</span><strong class="dashboard-stat-value"><?= $totalServiciosAsignados ?></strong></div>
          <div class="dashboard-stat"><span class="dashboard-stat-label">Pendientes</span><strong class="dashboard-stat-value"><?= $serviciosPendientes ?></strong></div>
          <div class="dashboard-stat"><span class="dashboard-stat-label">En proceso</span><strong class="dashboard-stat-value"><?= $serviciosProceso ?></strong></div>
          <div class="dashboard-stat"><span class="dashboard-stat-label">Finalizados</span><strong class="dashboard-stat-value"><?= $serviciosFinalizados ?></strong></div>
          <div class="dashboard-stat"><span class="dashboard-stat-label">Solicitudes por aceptar</span><strong class="dashboard-stat-value"><?= $solicitudesPendientes ?></strong></div>
        </section>
        <section class="card border-0 rounded-4">
          <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
              <div><h2 class="h5 card-title mb-1">Servicios asignados recientemente</h2><p class="small text-muted mb-0">Solo se muestran servicios asociados a tu perfil técnico.</p></div>
              <a href="tecnico_servicios.php" class="btn btn-sm btn-outline-verde rounded-pill">Ver lista completa</a>
            </div>
            <?php if ($servicios): ?>
              <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead><tr><th>ID</th><th>Tipo de servicio</th><th>Cliente</th><th>Vehículo</th><th>Estado</th><th>Ingreso / cita</th><th></th></tr></thead>
                <tbody>
                  <?php foreach ($servicios as $s): ?>
                    <tr>
                      <td><?= (int)$s['id_servicio'] ?></td><td><?= htmlspecialchars($s['tipo_servicio']) ?></td>
                      <td><?= htmlspecialchars($s['cliente']) ?></td><td><?= htmlspecialchars($s['placa']) ?></td>
                      <td><span class="badge bg-light text-dark"><?= htmlspecialchars($s['estado'] ?? 'Sin estado') ?></span></td>
                      <td><?= htmlspecialchars($s['fecha_ingreso']) ?><br><small>Cita: <?= htmlspecialchars($s['fecha_cita'] ?? 'No definida') ?></small></td>
                      <td><a class="btn btn-sm btn-outline-verde rounded-pill" href="historial_servicios.php?servicio=<?= (int)$s['id_servicio'] ?>">Historial</a></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table></div>
            <?php else: ?><p class="mb-0 text-muted">No tienes servicios asignados por el momento.</p><?php endif; ?>
          </div>
        </section>
      </div>
      <footer class="footer-sena py-3 mt-4"><div class="container-fluid small text-center">SENA ServiControl · Herramientas técnicas para cada reparación.</div></footer>
    </main>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/main.js"></script>
</body>
</html>
