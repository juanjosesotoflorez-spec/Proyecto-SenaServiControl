<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'cliente') {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';

$search = trim($_GET['q'] ?? '');
$cliente = null;
$stmt = $conn->prepare('SELECT id_cliente FROM cliente WHERE id_usuario = ?');
$stmt->bind_param('i', $_SESSION['id_usuario']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $cliente = $result->fetch_assoc();
}

$vehiculos = [];
if ($cliente) {
    $id = $cliente['id_cliente'];
    $query = "SELECT id_vehiculo, placa, marca, modelo, color, anio, kilometraje FROM vehiculo WHERE id_cliente = ?";
    $params = [$id];
    $types = 'i';

    if ($search !== '') {
        $query .= ' AND (placa LIKE ? OR marca LIKE ? OR modelo LIKE ? OR color LIKE ?)';
        $term = '%' . $search . '%';
        $params = [$id, $term, $term, $term, $term];
        $types = 'issss';
    }

    $query .= ' ORDER BY id_vehiculo DESC';
    $stmtVehiculos = $conn->prepare($query);
    $stmtVehiculos->bind_param($types, ...$params);
    $stmtVehiculos->execute();
    $res = $stmtVehiculos->get_result();
    while ($row = $res->fetch_assoc()) {
        $vehiculos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis vehículos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
</head>
<body class="admin-body module-page module-customer-vehicles">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4>Cliente</h4></div>
      <nav class="sidebar-nav">
        <a href="dashboard_cliente.php">Volver al panel</a>
        <a href="cliente_vehiculos.php" class="active">Mis vehículos</a>
        <a href="cliente_servicios.php">Mis servicios</a>
        <a href="logout.php">Salir</a>
      </nav>
    </aside>
    <main class="admin-content">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0">Mis vehículos</h2>
        <a href="registro_serviteca.php" class="btn btn-verde rounded-pill">Registrar vehículo</a>
      </div>
      <div class="dashboard-panel mb-4">
        <form method="GET" class="row g-2 align-items-end">
          <div class="col-md-8">
            <label class="form-label">Buscar vehículo</label>
            <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Placa, marca, modelo o color">
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-verde rounded-pill flex-grow-1">Buscar</button>
            <a href="cliente_vehiculos.php" class="btn btn-outline-verde rounded-pill">Limpiar</a>
          </div>
        </form>
      </div>
      <div class="dashboard-panel">
        <?php if (empty($vehiculos)): ?>
          <div class="alert alert-warning alerta">No se encontraron vehículos con los criterios de búsqueda.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead><tr><th>ID</th><th>Placa</th><th>Marca</th><th>Modelo</th><th>Color</th>              <th>Año / kilometraje</th><th>Acciones</th></tr></thead>
              <tbody>
              <?php foreach ($vehiculos as $v): ?>
                <tr>
                  <td><?= $v['id_vehiculo'] ?></td>
                  <td><?= htmlspecialchars($v['placa']) ?></td>
                  <td><?= htmlspecialchars($v['marca']) ?></td>
                  <td><?= htmlspecialchars($v['modelo']) ?></td>
                  <td><?= htmlspecialchars($v['color']) ?></td>
                  <td><?= htmlspecialchars($v['anio'] ?? '—') ?><br><small><?= $v['kilometraje'] === null ? 'Km no registrados' : htmlspecialchars(number_format((int) $v['kilometraje'], 0, ',', '.') . ' km') ?></small></td>
                  <td>
                    <a href="registro_serviteca.php?edit=<?= (int) $v['id_vehiculo'] ?>" class="btn btn-sm btn-outline-verde rounded-pill">Editar</a>
                    <a href="cliente_solicitud_servicio.php?vehiculo=<?= (int) $v['id_vehiculo'] ?>" class="btn btn-sm btn-verde rounded-pill">Solicitar servicio</a>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>
