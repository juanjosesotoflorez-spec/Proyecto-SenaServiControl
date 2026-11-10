<?php
session_start();
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol'] ?? '') !== 'recepcionista') {
    header('Location: login.php');
    exit;
}

require dirname(__DIR__, 2) . '/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$volverServicios = 'recepcionista_servicios.php';

$vehiculos = $conn->query("
    SELECT v.id_vehiculo, v.placa, v.marca, v.modelo, c.nombre AS cliente, c.apellido AS cliente_apellido
    FROM vehiculo v
    JOIN cliente c ON v.id_cliente = c.id_cliente
    ORDER BY v.placa
");

$tecnicos = $conn->query("
    SELECT id_tecnico, nombre, apellido
    FROM tecnico
    ORDER BY nombre
");

$recepcionistaActual = null;
$stmt = $conn->prepare('SELECT id_recepcionista, nombre, apellido FROM recepcionista WHERE id_usuario = ?');
$stmt->bind_param('i', $_SESSION['id_usuario']);
$stmt->execute();
$recepcionistaActual = $stmt->get_result()->fetch_assoc();

$vehiculoHistorial = filter_input(INPUT_GET, 'id_vehiculo', FILTER_VALIDATE_INT) ?: 0;
$historialVehiculo = [];
if ($vehiculoHistorial > 0) {
    $stmt = $conn->prepare(
        'SELECT id_servicio, tipo_servicio, descripcion, fecha_ingreso, estado
         FROM servicio WHERE id_vehiculo = ? ORDER BY fecha_ingreso ASC, id_servicio ASC'
    );
    $stmt->bind_param('i', $vehiculoHistorial);
    $stmt->execute();
    $historyResult = $stmt->get_result();
    while ($row = $historyResult->fetch_assoc()) {
        $historialVehiculo[] = $row;
    }
}

$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';
$errores = [
    'datos' => 'Revisa los campos, las fechas, el estado y el costo ingresados.',
    'perfil' => 'Tu cuenta no tiene un perfil de recepción asociado.',
    'relacion' => 'El vehículo o el técnico seleccionado ya no existe.',
    'guardar' => 'No se pudo registrar el servicio. Inténtalo nuevamente.',
    'sesion' => 'La sesión del formulario venció. Recarga la página e inténtalo nuevamente.'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrar servicio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
  <script src="../js/form-validation.js?v=20261109-confirm" defer></script>
</head>
<body class="admin-body module-page module-service-registration">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4>Recepción</h4></div>
      <nav class="sidebar-nav">
        <a href="dashboard_recepcionista.php">Volver al panel</a>
        <a href="recepcionista_clientes.php">Clientes</a>
        <a href="recepcionista_vehiculos.php">Vehículos</a>
        <a href="recepcionista_servicios.php">Servicios</a>
        <a href="notificaciones.php">Notificaciones</a>
        <a href="registro_servicios.php" class="active">Registrar servicio</a>
        <a href="logout.php">Salir</a>
      </nav>
    </aside>

    <main class="admin-content">
      <div class="admin-header align-items-start">
        <div>
          <span class="service-kicker">Nueva atencion</span>
          <h2 class="section-title mb-1">Registrar servicio</h2>
          <p class="texto-gris mb-0">Completa la informacion del ingreso, asigna responsable y deja listo el servicio para seguimiento.</p>
        </div>
        <a href="<?= $volverServicios ?>" class="btn btn-outline-verde rounded-pill">Ver servicios</a>
      </div>

      <?php if ($mensaje === 'ok'): ?>
        <div class="alert alert-success alerta">Servicio registrado correctamente.</div>
      <?php elseif ($error !== ''): ?>
        <div class="alert alert-danger alerta"><?= htmlspecialchars($errores[$error] ?? 'Ocurrió un error al procesar el servicio.') ?></div>
      <?php endif; ?>

      <div class="dashboard-panel mb-4">
        <h5 class="section-title">Consultar antecedentes del vehículo</h5>
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-8">
            <label class="form-label" for="vehiculo_historial">Vehículo</label>
            <select id="vehiculo_historial" name="id_vehiculo" class="form-select" required>
              <option value="">Seleccione un vehículo</option>
              <?php
              $historyVehicles = $conn->query(
                  'SELECT v.id_vehiculo, v.placa, v.marca, v.modelo, c.nombre, c.apellido
                   FROM vehiculo v JOIN cliente c ON c.id_cliente = v.id_cliente ORDER BY v.placa'
              );
              while ($v = $historyVehicles->fetch_assoc()):
              ?>
                <option value="<?= (int) $v['id_vehiculo'] ?>" <?= $vehiculoHistorial === (int) $v['id_vehiculo'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($v['placa'] . ' - ' . $v['marca'] . ' ' . $v['modelo'] . ' / ' . $v['nombre'] . ' ' . $v['apellido']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-outline-verde rounded-pill">Consultar historial</button>
          </div>
        </form>
        <?php if ($vehiculoHistorial > 0): ?>
          <div class="table-responsive mt-3">
            <table class="table table-sm align-middle">
              <thead><tr><th>Fecha</th><th>Servicio</th><th>Descripción</th><th>Estado</th></tr></thead>
              <tbody>
                <?php if ($historialVehiculo): ?>
                  <?php foreach ($historialVehiculo as $historial): ?>
                    <tr>
                      <td><?= htmlspecialchars($historial['fecha_ingreso']) ?></td>
                      <td><?= htmlspecialchars($historial['tipo_servicio']) ?></td>
                      <td><?= nl2br(htmlspecialchars($historial['descripcion'] ?? '')) ?></td>
                      <td><?= htmlspecialchars($historial['estado'] ?? 'Sin estado') ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="4" class="text-muted">No hay servicios anteriores para este vehículo.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="service-form-shell">
        <div class="service-form-intro">
          <h3>Datos del servicio</h3>
          <p>Usa una descripcion clara para que recepcion, tecnico y cliente puedan entender el estado del vehiculo.</p>
        </div>

        <form action="guardar_servicios.php" method="POST" class="service-form" id="service-form">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Tipo de servicio</label>
              <select name="tipo_servicio" class="form-select" required>
                <option value="">Seleccione</option>
                <option value="Mantenimiento">Mantenimiento</option>
                <option value="Reparacion">Reparacion</option>
                <option value="Revision general">Revision general</option>
                <option value="Diagnostico">Diagnostico</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Fecha de ingreso</label>
              <input type="date" name="fecha_ingreso" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Fecha estimada de entrega (opcional)</label>
              <input type="date" name="fecha_entrega_estimada" class="form-control" min="<?= date('Y-m-d') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label" for="estado">Estado inicial</label>
              <select name="estado" id="estado" class="form-select" required>
                <option value="Pendiente">Pendiente</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Costo estimado</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" name="costo" step="0.01" min="0" max="99999999.99" class="form-control" placeholder="0.00" required>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label">Vehiculo</label>
              <select name="id_vehiculo" class="form-select" required>
                <option value="">Seleccione un vehiculo</option>
                <?php while ($v = $vehiculos->fetch_assoc()): ?>
                  <option value="<?= (int) $v['id_vehiculo'] ?>">
                    <?= htmlspecialchars($v['placa'] . ' - ' . $v['marca'] . ' ' . $v['modelo'] . ' / ' . $v['cliente'] . ' ' . $v['cliente_apellido']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Tecnico asignado</label>
              <select name="id_tecnico" class="form-select" required>
                <option value="">Seleccione un tecnico</option>
                <?php while ($t = $tecnicos->fetch_assoc()): ?>
                  <option value="<?= (int) $t['id_tecnico'] ?>">
                    <?= htmlspecialchars($t['nombre'] . ' ' . $t['apellido']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Recepcionista responsable</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars(($recepcionistaActual['nombre'] ?? '') . ' ' . ($recepcionistaActual['apellido'] ?? '')) ?>" readonly>
            </div>

            <div class="col-12">
              <label class="form-label">Descripcion</label>
              <textarea name="descripcion" class="form-control" rows="5" placeholder="Describe sintomas, solicitud del cliente o trabajo a realizar" required></textarea>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2 pt-2">
              <button type="submit" class="btn btn-verde rounded-pill">Registrar servicio</button>
              <a href="<?= $volverServicios ?>" class="btn btn-outline-secondary rounded-pill">Cancelar</a>
            </div>
          </div>
        </form>
      </div>
    </main>
  </div>
  <script>
    const ingresoInput = document.querySelector('[name="fecha_ingreso"]');
    const estimadaInput = document.querySelector('[name="fecha_entrega_estimada"]');
    const actualizarFechaEstimada = () => {
      estimadaInput.min = ingresoInput.value;
      estimadaInput.setCustomValidity(
        estimadaInput.value && estimadaInput.value < ingresoInput.value
          ? 'La fecha estimada no puede ser anterior a la fecha de ingreso.'
          : ''
      );
    };
    ingresoInput.addEventListener('change', actualizarFechaEstimada);
    estimadaInput.addEventListener('change', actualizarFechaEstimada);
    actualizarFechaEstimada();
  </script>
</body>
</html>
