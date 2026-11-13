<?php
session_start();
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$clientes = [];
$where = '';
$totalClientes = 0;
$like = '%' . $search . '%';

if ($search !== '') {
    $where = ' WHERE c.nombre LIKE ? OR c.apellido LIKE ? OR c.documento LIKE ?
               OR c.telefono LIKE ? OR c.correo LIKE ?';
    $count = $conn->prepare('SELECT COUNT(*) AS total FROM cliente c' . $where);
    $count->bind_param('sssss', $like, $like, $like, $like, $like);
    $count->execute();
    $totalClientes = (int)$count->get_result()->fetch_assoc()['total'];
} else {
    $totalClientes = (int)$conn->query('SELECT COUNT(*) AS total FROM cliente')->fetch_assoc()['total'];
}

$totalPages = max(1, (int)ceil($totalClientes / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$sql = 'SELECT c.id_cliente,c.nombre,c.apellido,c.documento,c.telefono,c.direccion,c.correo,
               u.usuario,u.rol,u.estado AS estado_cuenta
        FROM cliente c LEFT JOIN usuario u ON u.id_usuario=c.id_usuario'
    . $where . ' ORDER BY c.nombre,c.apellido,c.id_cliente DESC LIMIT ? OFFSET ?';
$stmt = $conn->prepare($sql);
if ($search !== '') {
    $stmt->bind_param('sssssii', $like, $like, $like, $like, $like, $perPage, $offset);
} else {
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$clientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Clientes - Administración</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css?v=20261113-brand-green">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261113-brand-green">
</head>
<body class="admin-body module-page module-admin-clients">
<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="sidebar-brand"><h4>Administración</h4></div>
    <nav class="sidebar-nav">
      <a href="../../index.html">Volver al panel</a>
      <a href="admin_clientes.php" class="active">Clientes</a>
      <a href="admin_vehiculos.php">Vehículos</a>
      <a href="admin_servicios.php">Servicios</a>
      <a href="historial_servicios.php">Historial de operaciones</a>
      <a href="logout.php">Cerrar sesión</a>
    </nav>
  </aside>
  <main class="admin-content">
    <h2 class="section-title">Consulta de clientes</h2>
    <div class="dashboard-panel mb-4">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-8">
          <label for="q" class="form-label">Buscar cliente</label>
          <input id="q" name="q" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Documento, nombre, teléfono o correo">
        </div>
        <div class="col-md-4 d-flex gap-2">
          <button class="btn btn-verde rounded-pill flex-grow-1">Buscar</button>
          <a href="admin_clientes.php" class="btn btn-outline-verde rounded-pill">Limpiar</a>
        </div>
      </form>
    </div>
    <div class="dashboard-panel">
      <?php if (!$clientes): ?>
        <p class="text-muted mb-0">No se encontraron clientes.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Cliente</th><th>Documento</th><th>Teléfono</th><th>Correo</th><th>Cuenta</th><th>Historial</th></tr></thead>
            <tbody>
            <?php foreach ($clientes as $cliente): ?>
              <tr>
                <td><?= htmlspecialchars($cliente['nombre'].' '.$cliente['apellido']) ?><br><small class="text-muted"><?= htmlspecialchars($cliente['direccion'] ?? '') ?></small></td>
                <td><?= htmlspecialchars($cliente['documento']) ?></td>
                <td><?= htmlspecialchars($cliente['telefono'] ?? '') ?></td>
                <td><?= htmlspecialchars($cliente['correo'] ?? '') ?></td>
                <td><?= htmlspecialchars($cliente['usuario'] ?? 'Sin cuenta') ?><?= $cliente['estado_cuenta'] ? ' · '.htmlspecialchars($cliente['estado_cuenta']) : '' ?></td>
                <td><a class="btn btn-sm btn-outline-verde" href="historial_servicios.php?cliente=<?= (int)$cliente['id_cliente'] ?>">Consultar</a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if ($totalPages > 1): ?>
          <nav aria-label="Paginación de clientes"><ul class="pagination justify-content-center mb-0">
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
