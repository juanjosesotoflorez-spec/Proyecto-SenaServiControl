<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}
require __DIR__ . '/../db.php';

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$like = '%' . $search . '%';
$where = '';
$vehiculos = [];
if ($search !== '') {
    $where = ' WHERE v.placa LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ?
               OR c.nombre LIKE ? OR c.apellido LIKE ? OR c.documento LIKE ? OR c.telefono LIKE ?';
    $count = $conn->prepare(
        'SELECT COUNT(*) AS total FROM vehiculo v JOIN cliente c ON c.id_cliente=v.id_cliente' . $where
    );
    $count->bind_param('sssssss', $like, $like, $like, $like, $like, $like, $like);
    $count->execute();
    $totalVehiculos = (int)$count->get_result()->fetch_assoc()['total'];
} else {
    $totalVehiculos = (int)$conn->query('SELECT COUNT(*) AS total FROM vehiculo')->fetch_assoc()['total'];
}
$totalPages = max(1, (int)ceil($totalVehiculos / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$stmt = $conn->prepare(
    'SELECT v.id_vehiculo,v.placa,v.marca,v.modelo,v.color,v.anio,v.kilometraje,
            c.id_cliente,c.nombre AS cliente,c.apellido,c.documento,c.telefono
     FROM vehiculo v JOIN cliente c ON c.id_cliente=v.id_cliente'
    . $where . ' ORDER BY v.id_vehiculo DESC LIMIT ? OFFSET ?'
);
if ($search !== '') {
    $stmt->bind_param('sssssssii', $like, $like, $like, $like, $like, $like, $like, $perPage, $offset);
} else {
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$vehiculos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vehículos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
</head>
<body class="admin-body module-page module-admin-vehicles">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4>Administración</h4></div>
      <nav class="sidebar-nav">
        <a href="dashboard_admin.php">Volver al panel</a>
        <a href="admin_usuarios.php">Usuarios</a>
        <a href="admin_clientes.php">Clientes</a>
        <a href="admin_vehiculos.php" class="active">Vehículos</a>
        <a href="admin_servicios.php">Servicios</a>
        <a href="admin_repuestos.php">Repuestos</a>
        <a href="logout.php">Cerrar sesión</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h2 class="section-title">Gestión de vehículos</h2>
      <div class="dashboard-panel mb-4">
        <form method="GET" class="row g-2 align-items-end">
          <div class="col-md-8"><label for="q" class="form-label">Buscar vehículo</label><input id="q" name="q" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Placa, marca, modelo, propietario o teléfono"></div>
          <div class="col-md-4 d-flex gap-2"><button class="btn btn-verde rounded-pill flex-grow-1">Buscar</button><a href="admin_vehiculos.php" class="btn btn-outline-verde rounded-pill">Limpiar</a></div>
        </form>
      </div>
      <div class="dashboard-panel">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>ID</th><th>Placa</th><th>Marca</th><th>Modelo</th><th>Color / año / km</th><th>Propietario</th><th>Historial</th></tr></thead>
            <tbody>
            <?php foreach ($vehiculos as $v): ?>
              <tr>
                <td><?= $v['id_vehiculo'] ?></td>
                <td><?= htmlspecialchars($v['placa']) ?></td>
                <td><?= htmlspecialchars($v['marca']) ?></td>
                <td><?= htmlspecialchars($v['modelo']) ?></td>
                <td><?= htmlspecialchars(($v['color'] ?? '—').' / '.($v['anio'] ?? '—').' / '.($v['kilometraje'] === null ? 'Km no registrados' : number_format((int) $v['kilometraje'], 0, ',', '.') . ' km')) ?></td>
                <td><?= htmlspecialchars($v['cliente'].' '.$v['apellido']) ?><br><small><?= htmlspecialchars($v['documento']) ?> · <?= htmlspecialchars($v['telefono'] ?? '') ?></small></td>
                <td><a href="historial_servicios.php?vehiculo=<?= (int)$v['id_vehiculo'] ?>" class="btn btn-sm btn-outline-verde">Consultar</a></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$vehiculos): ?><tr><td colspan="7" class="text-muted">No se encontraron vehículos.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($totalPages > 1): ?>
          <nav aria-label="Paginación de vehículos"><ul class="pagination justify-content-center mb-0">
            <?php for ($number = 1; $number <= $totalPages; $number++): ?>
              <li class="page-item <?= $number === $page ? 'active' : '' ?>"><a class="page-link" href="?q=<?= urlencode($search) ?>&p=<?= $number ?>"><?= $number ?></a></li>
            <?php endfor; ?>
          </ul></nav>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>
