<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'recepcionista') {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$clientes = [];
$totalClientes = 0;
$where = '';
$like = '%' . $search . '%';

if ($search !== '') {
    $where = ' WHERE nombre LIKE ? OR apellido LIKE ? OR documento LIKE ? OR telefono LIKE ?';
    $count = $conn->prepare('SELECT COUNT(*) AS total FROM cliente' . $where);
    $count->bind_param('ssss', $like, $like, $like, $like);
    $count->execute();
    $totalClientes = (int)$count->get_result()->fetch_assoc()['total'];
} else {
    $totalClientes = (int)$conn->query('SELECT COUNT(*) AS total FROM cliente')->fetch_assoc()['total'];
}
$totalPages = max(1, (int)ceil($totalClientes / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$sql = 'SELECT id_cliente, nombre, apellido, documento, telefono, correo
        FROM cliente' . $where . ' ORDER BY nombre,apellido,id_cliente DESC LIMIT ? OFFSET ?';
$stmt = $conn->prepare($sql);
if ($search !== '') {
    $stmt->bind_param('ssssii', $like, $like, $like, $like, $perPage, $offset);
} else {
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$clientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clientes</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
</head>
<body class="admin-body module-page module-reception-clients">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4>Recepción</h4></div>
      <nav class="sidebar-nav">
        <a href="../../index.html">Volver al panel</a>
        <a href="recepcionista_clientes.php" class="active">Clientes</a>
        <a href="recepcionista_vehiculos.php">Vehículos</a>
        <a href="recepcionista_servicios.php">Servicios</a>
        <a href="registro_servicios.php">Registrar servicio</a>
        <a href="logout.php">Salir</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h2 class="section-title">Clientes</h2>
      <div class="dashboard-panel mb-4">
        <form method="GET" class="row g-2 align-items-end">
          <div class="col-md-8">
            <label class="form-label">Buscar cliente</label>
            <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Documento, nombre o teléfono">
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-verde rounded-pill flex-grow-1">Buscar</button>
            <a href="recepcionista_clientes.php" class="btn btn-outline-verde rounded-pill">Limpiar</a>
          </div>
        </form>
      </div>
      <div class="dashboard-panel">
        <?php if (empty($clientes)): ?>
          <div class="alert alert-warning alerta">No se encontraron clientes con los criterios de búsqueda.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead><tr><th>ID</th><th>Nombre</th><th>Documento</th><th>Teléfono</th><th>Correo</th><th>Historial</th></tr></thead>
              <tbody>
              <?php foreach ($clientes as $c): ?>
                <tr>
                  <td><?= $c['id_cliente'] ?></td>
                  <td><?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?></td>
                  <td><?= htmlspecialchars($c['documento']) ?></td>
                  <td><?= htmlspecialchars($c['telefono'] ?? '') ?></td>
                  <td><?= htmlspecialchars($c['correo'] ?? '') ?></td>
                  <td><a class="btn btn-sm btn-outline-verde" href="historial_servicios.php?cliente=<?= (int)$c['id_cliente'] ?>">Consultar</a></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php if ($totalPages > 1): ?>
            <nav aria-label="Paginación de clientes"><ul class="pagination justify-content-center mt-3 mb-0">
              <?php for ($number = 1; $number <= $totalPages; $number++): ?>
                <li class="page-item <?= $number === $page ? 'active' : '' ?>"><a class="page-link" href="?q=<?= urlencode($search) ?>&p=<?= $number ?>"><?= $number ?></a></li>
              <?php endfor; ?>
            </ul></nav>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>
