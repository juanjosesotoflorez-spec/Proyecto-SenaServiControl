<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'tecnico') {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';

$reparaciones = [];
$stmt = $conn->prepare('SELECT id_tecnico FROM tecnico WHERE id_usuario=?');
$stmt->bind_param('i', $_SESSION['id_usuario']);
$stmt->execute();
$idTecnicoActual = (int)($stmt->get_result()->fetch_assoc()['id_tecnico'] ?? 0);
if ($idTecnicoActual > 0) {
    $stmt = $conn->prepare(
        'SELECT r.id_reparacion,r.dano_detectado,r.solucion,s.tipo_servicio,v.placa
         FROM reparacion r JOIN servicio s ON s.id_servicio=r.id_servicio
         JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
         WHERE s.id_tecnico=? ORDER BY r.id_reparacion DESC'
    );
    $stmt->bind_param('i', $idTecnicoActual);
    $stmt->execute();
    $reparaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reparaciones</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
</head>
<body class="admin-body module-page module-repairs-tech">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4>Técnico</h4></div>
      <nav class="sidebar-nav">
        <a href="dashboard_tecnico.php">Volver al panel</a>
        <a href="tecnico_servicios.php">Servicios asignados</a>
        <a href="seguimiento_tecnico.php">Seguimiento</a>
        <a href="mantenimientos.php">Mantenimientos</a>
        <a href="reparaciones.php">Gestión de reparaciones</a>
        <a href="asignaciones_tecnicos.php">Asignaciones</a>
        <a href="tecnico_reparaciones.php" class="active">Reparaciones</a>
        <a href="logout.php">Salir</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h2 class="section-title">Reparaciones</h2>
      <div class="dashboard-panel">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>ID</th><th>Servicio</th><th>Vehículo</th><th>Daño detectado</th><th>Solución</th></tr></thead>
            <tbody>
            <?php foreach ($reparaciones as $r): ?>
              <tr>
                <td><?= $r['id_reparacion'] ?></td>
                <td><?= htmlspecialchars($r['tipo_servicio']) ?></td>
                <td><?= htmlspecialchars($r['placa']) ?></td>
                <td><?= htmlspecialchars($r['dano_detectado']) ?></td>
                <td><?= htmlspecialchars($r['solucion']) ?></td>
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
