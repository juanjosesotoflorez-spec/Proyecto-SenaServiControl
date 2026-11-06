<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'cliente') {
    header('Location: login.php');
    exit;
}
require __DIR__ . '/../db.php';

$logoutPath = 'logout.php';

$message = '';
$messageType = 'danger';

$cliente = null;
$usuarioData = null;
$stmt = $conn->prepare('SELECT c.id_cliente, c.nombre, c.apellido, c.documento, c.telefono, c.direccion, c.correo, u.usuario FROM cliente c JOIN usuario u ON c.id_usuario = u.id_usuario WHERE c.id_usuario = ?');
$stmt->bind_param('i', $_SESSION['id_usuario']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $cliente = $result->fetch_assoc();
    $usuarioData = [
        'usuario' => $cliente['usuario'],
        'correo' => $cliente['correo']
    ];
}

$vehiculos = [];
$serviciosRecientes = [];
$resumenServicios = ['total' => 0, 'activos' => 0, 'finalizados' => 0];
if ($cliente) {
    $id = (int)$cliente['id_cliente'];
    $stmt = $conn->prepare(
        'SELECT id_vehiculo,placa,marca,modelo,color,anio,kilometraje
         FROM vehiculo WHERE id_cliente=? ORDER BY id_vehiculo DESC'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $vehiculos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(estado NOT IN ('Finalizado','Entregado') OR estado IS NULL),0) AS activos,
                COALESCE(SUM(estado IN ('Finalizado','Entregado')),0) AS finalizados
         FROM servicio s JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
         WHERE v.id_cliente=?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $resumenServicios = $stmt->get_result()->fetch_assoc() ?: $resumenServicios;

    $stmt = $conn->prepare(
        'SELECT s.id_servicio,s.tipo_servicio,s.estado,s.fecha_ingreso,v.placa
         FROM servicio s JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
         WHERE v.id_cliente=? ORDER BY s.fecha_ingreso DESC,s.id_servicio DESC LIMIT 5'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $serviciosRecientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$nombre_cliente = $cliente ? $cliente['nombre'] : 'cliente';
$dashboardRoleLabel = 'Cliente';
$dashboardUserName = $cliente ? trim($cliente['nombre'].' '.$cliente['apellido']) : ($_SESSION['correo'] ?? 'Cliente');
$dashboardNavigation = [
    'Inicio' => [
        ['icon' => '⌂', 'label' => 'Mi resumen', 'href' => 'dashboard_cliente.php', 'active' => true]
    ],
    'Mis datos' => [
        ['icon' => '▣', 'label' => 'Mis vehículos', 'href' => 'cliente_vehiculos.php'],
        ['icon' => '+', 'label' => 'Solicitar servicio', 'href' => 'cliente_solicitud_servicio.php'],
        ['icon' => '♙', 'label' => 'Mi perfil', 'href' => 'cliente_perfil.php']
    ],
    'Mis servicios' => [
        ['icon' => '▤', 'label' => 'Consultar servicios', 'href' => 'cliente_servicios.php'],
        ['icon' => '◷', 'label' => 'Historial', 'href' => 'historial_servicios.php'],
        ['icon' => '↻', 'label' => 'Mantenimientos', 'href' => 'mantenimientos.php'],
        ['icon' => '⚙', 'label' => 'Reparaciones', 'href' => 'reparaciones.php'],
        ['icon' => '✓', 'label' => 'Entregas', 'href' => 'entregas.php'],
        ['icon' => '◉', 'label' => 'Notificaciones', 'href' => 'notificaciones.php'],
        ['icon' => '▥', 'label' => 'Mi resumen', 'href' => 'reportes.php'],
        ['icon' => '◴', 'label' => 'Mi historial de actividad', 'href' => 'historial_actividades.php']
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel del cliente</title>
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
          <div class="dashboard-topbar-title"><strong>SENA ServiControl</strong><small>Mi espacio · Información personal y servicios</small></div>
        </div>
        <div class="dashboard-user">
          <span class="dashboard-user-name"><?= htmlspecialchars($dashboardUserName) ?></span>
          <a href="cliente_perfil.php" class="btn btn-outline-verde btn-sm rounded-pill">Mi perfil</a>
          <a href="<?= $logoutPath ?>" class="btn btn-outline-verde btn-sm rounded-pill">Salir</a>
        </div>
      </header>
      <div class="dashboard-content">
        <?php if ($message !== ''): ?><div class="alert alert-<?= htmlspecialchars($messageType) ?> alerta mb-4" role="status"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <section class="dashboard-page-heading"><h1>Mi resumen</h1><p>Desde aquí puedes consultar tus vehículos, servicios y datos personales.</p></section>
        <section class="dashboard-welcome mb-4">
          <div class="dashboard-welcome-main">
            <div class="dashboard-eyebrow">Bienvenido a tu espacio</div>
            <h1>Hola, <?= htmlspecialchars($nombre_cliente) ?>.</h1>
            <p>Consulta el estado de tus vehículos y revisa el historial de los trabajos realizados.</p>
            <div class="d-flex flex-wrap gap-2 mt-4">
              <a href="cliente_vehiculos.php" class="btn btn-verde rounded-pill">Ver mis vehículos</a>
              <a href="cliente_solicitud_servicio.php" class="btn btn-outline-verde rounded-pill">Solicitar servicio</a>
              <a href="cliente_servicios.php" class="btn btn-outline-verde rounded-pill">Consultar mis servicios</a>
              <a href="historial_servicios.php" class="btn btn-outline-verde rounded-pill">Ver historial</a>
            </div>
          </div>
        </section>
        <section class="row g-3 mb-4" aria-label="Resumen de mi cuenta">
          <div class="col-6 col-xl-3"><div class="dashboard-stat"><span class="dashboard-stat-label">Mis vehículos</span><strong class="dashboard-stat-value"><?= count($vehiculos) ?></strong></div></div>
          <div class="col-6 col-xl-3"><div class="dashboard-stat"><span class="dashboard-stat-label">Mis servicios</span><strong class="dashboard-stat-value"><?= (int)$resumenServicios['total'] ?></strong></div></div>
          <div class="col-6 col-xl-3"><div class="dashboard-stat"><span class="dashboard-stat-label">Activos o sin estado</span><strong class="dashboard-stat-value"><?= (int)$resumenServicios['activos'] ?></strong></div></div>
          <div class="col-6 col-xl-3"><div class="dashboard-stat"><span class="dashboard-stat-label">Finalizados o entregados</span><strong class="dashboard-stat-value"><?= (int)$resumenServicios['finalizados'] ?></strong></div></div>
        </section>
        <section class="row g-4 mb-4">
          <div class="col-lg-7">
            <div class="card border-0 rounded-4 h-100"><div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><div><h2 class="h5 card-title mb-1">Mis vehículos</h2><p class="small text-muted mb-0">Vehículos asociados a tu cuenta.</p></div><a href="cliente_vehiculos.php" class="btn btn-sm btn-outline-verde rounded-pill">Ver todos</a></div>
              <?php if ($vehiculos): ?><div class="row g-3">
                <?php foreach ($vehiculos as $v): ?><div class="col-md-6"><article class="dashboard-stat p-3"><strong><?= htmlspecialchars($v['placa']) ?></strong><p class="mb-1 mt-2"><?= htmlspecialchars($v['marca'].' '.$v['modelo']) ?></p><small class="text-muted">Color: <?= htmlspecialchars($v['color'] ?? 'No registrado') ?> · Año: <?= htmlspecialchars($v['anio'] ?? 'No registrado') ?> · Km: <?= $v['kilometraje'] === null ? 'No registrado' : htmlspecialchars(number_format((int) $v['kilometraje'], 0, ',', '.')) ?></small></article></div><?php endforeach; ?>
              </div><?php else: ?><p class="text-muted mb-0">Aún no tienes vehículos registrados.</p><?php endif; ?>
              <a href="registro_serviteca.php" class="btn btn-verde rounded-pill mt-3">Solicitar registro de vehículo</a>
            </div></div>
          </div>
          <div class="col-lg-5">
            <div class="card border-0 rounded-4 h-100"><div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><div><h2 class="h5 card-title mb-1">Mis datos personales</h2><p class="small text-muted mb-0">Información de tu cuenta.</p></div><a href="cliente_perfil.php" class="btn btn-sm btn-outline-verde rounded-pill">Editar perfil</a></div>
              <?php if ($cliente): ?>
                <p class="mb-2"><strong><?= htmlspecialchars($cliente['nombre'].' '.$cliente['apellido']) ?></strong></p>
                <p class="mb-2">Documento: <?= htmlspecialchars($cliente['documento']) ?></p>
                <p class="mb-2">Correo: <?= htmlspecialchars($cliente['correo']) ?></p>
                <p class="mb-2">Teléfono: <?= htmlspecialchars($cliente['telefono'] ?? 'No registrado') ?></p>
                <p class="mb-0">Dirección: <?= htmlspecialchars($cliente['direccion'] ?: 'Sin registrar') ?></p>
              <?php else: ?><p class="mb-0 text-muted">Aún no tienes datos completos disponibles.</p><?php endif; ?>
            </div></div>
          </div>
        </section>
        <section class="card border-0 rounded-4">
          <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h2 class="h5 card-title mb-1">Mis servicios recientes</h2><p class="small text-muted mb-0">Solo se muestran los servicios asociados a tus vehículos.</p></div><a href="cliente_servicios.php" class="btn btn-sm btn-outline-verde rounded-pill">Consultar servicios</a></div>
            <?php if ($serviciosRecientes): ?><div class="table-responsive"><table class="table table-sm align-middle mb-0">
              <thead><tr><th>Servicio</th><th>Vehículo</th><th>Estado</th><th>Fecha de ingreso</th></tr></thead>
              <tbody><?php foreach ($serviciosRecientes as $servicio): ?><tr><td><?= htmlspecialchars($servicio['tipo_servicio']) ?></td><td><?= htmlspecialchars($servicio['placa']) ?></td><td><span class="badge bg-light text-dark"><?= htmlspecialchars($servicio['estado'] ?? 'Sin estado') ?></span></td><td><?= htmlspecialchars($servicio['fecha_ingreso']) ?></td></tr><?php endforeach; ?></tbody>
            </table></div><?php else: ?><p class="text-muted mb-0">Todavía no hay servicios asociados a tus vehículos.</p><?php endif; ?>
          </div>
        </section>
      </div>
      <footer class="footer-sena py-3 mt-4"><div class="container-fluid small text-center">SENA ServiControl · Gestión sencilla para cada cliente.</div></footer>
    </main>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/main.js"></script>
</body>
</html>
