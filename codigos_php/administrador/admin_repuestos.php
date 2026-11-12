<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';

$repuestos = [];
if ($result = $conn->query('SELECT id_repuesto, nombre, descripcion, precio, stock FROM repuesto ORDER BY id_repuesto DESC')) {
    while ($row = $result->fetch_assoc()) {
        $repuestos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Repuestos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
</head>
<body class="admin-body module-page module-parts">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4>Administración</h4></div>
      <nav class="sidebar-nav">
        <a href="../../index.html">Volver al panel</a>
        <a href="admin_usuarios.php">Usuarios</a>
        <a href="admin_vehiculos.php">Vehículos</a>
        <a href="admin_servicios.php">Servicios</a>
        <a href="admin_repuestos.php" class="active">Repuestos</a>
        <a href="logout.php">Cerrar sesión</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h2 class="section-title">Consulta de repuestos</h2>
      <div class="dashboard-panel">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Precio</th><th>Stock</th></tr></thead>
            <tbody>
            <?php foreach ($repuestos as $r): ?>
              <tr>
                <td><?= $r['id_repuesto'] ?></td>
                <td><?= htmlspecialchars($r['nombre']) ?></td>
                <td><?= htmlspecialchars($r['descripcion']) ?></td>
                <td>$<?= number_format($r['precio'], 2) ?></td>
                <td><?= $r['stock'] ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
