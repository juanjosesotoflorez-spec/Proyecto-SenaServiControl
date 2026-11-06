<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'recepcionista') {
    header('Location: login.php');
    exit;
}
require __DIR__ . '/../db.php';
require_once __DIR__ . '/auditoria_helper.php';

$message = '';
$messageType = 'danger';
$csrfToken = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

$clientes = [];
if ($result = $conn->query('SELECT id_cliente, nombre, apellido, documento FROM cliente ORDER BY nombre ASC')) {
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
}

$vehiculos = [];
if ($result = $conn->query('SELECT v.id_vehiculo, v.placa, v.marca, v.modelo, c.nombre AS cliente FROM vehiculo v JOIN cliente c ON v.id_cliente = c.id_cliente ORDER BY v.id_vehiculo DESC LIMIT 10')) {
    while ($row = $result->fetch_assoc()) {
        $vehiculos[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $readPost = static function (string $key): string {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    };
    $placa = strtoupper($readPost('placa'));
    $marca = $readPost('marca');
    $modelo = $readPost('modelo');
    $color = $readPost('color');
    $anioTexto = $readPost('anio');
    $kilometrajeTexto = $readPost('kilometraje');
    $anio = filter_var($anioTexto, FILTER_VALIDATE_INT);
    $kilometraje = $kilometrajeTexto === ''
        ? null
        : filter_var($kilometrajeTexto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 4294967295]]);
    $clienteInput = $readPost('cliente_id');
    $clienteId = filter_var($clienteInput, FILTER_VALIDATE_INT);
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $message = 'La sesión venció. Recarga la página e inténtalo nuevamente.';
    } elseif (
        $placa === '' || preg_match('/^[A-Z0-9-]{5,10}$/', $placa) !== 1 ||
        $marca === '' || mb_strlen($marca) > 50 ||
        $modelo === '' || mb_strlen($modelo) > 50 ||
        $color === '' || mb_strlen($color) > 30 ||
        $anio === false || $anio < 1900 || $anio > min(2100, (int) date('Y') + 1) ||
        $clienteId === false || $clienteId <= 0 ||
        ($kilometrajeTexto !== '' && $kilometraje === false)
    ) {
        $message = 'Completa todos los campos y selecciona un cliente.';
    } else {
        $check = $conn->prepare('SELECT id_vehiculo FROM vehiculo WHERE placa = ?');
        $check->bind_param('s', $placa);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = 'La placa ya está registrada en el sistema. No se puede registrar un vehículo existente.';
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO vehiculo (placa, marca, modelo, color, anio, kilometraje, id_cliente)
                 VALUES (?, ?, ?, ?, ?, NULLIF(?, \'\'), ?)'
            );
            $stmt->bind_param('ssssisi', $placa, $marca, $modelo, $color, $anio, $kilometrajeTexto, $clienteId);
            if ($stmt->execute()) {
                $idVehiculo = (int)$stmt->insert_id;
                $auditOk = registrarActividadSistema(
                    $conn,
                    (int)$_SESSION['id_usuario'],
                    'vehiculo_creado',
                    'vehiculo',
                    $idVehiculo,
                    null,
                    'Recepción registró un vehículo para un cliente.'
                );
                $message = $auditOk ? 'Vehículo registrado correctamente.' : 'El vehículo se registró, pero no pudo guardarse en la auditoría.';
                $messageType = $auditOk ? 'success' : 'warning';
                $vehiculos = [];
                $result = $conn->query('SELECT v.id_vehiculo, v.placa, v.marca, v.modelo, c.nombre AS cliente FROM vehiculo v JOIN cliente c ON v.id_cliente = c.id_cliente ORDER BY v.id_vehiculo DESC LIMIT 10');
                while ($row = $result->fetch_assoc()) {
                    $vehiculos[] = $row;
                }
            } else {
                $message = 'No se pudo registrar el vehículo en este momento.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro de vehículo nuevo - Recepción</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
</head>
<body class="bg-light module-page module-vehicle-registration">
  <div class="min-vh-100 d-flex flex-column">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom">
      <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold texto-verde" href="dashboard_recepcionista.php">Recepción SENA</a>
        <a class="btn btn-outline-verde rounded-pill" href="dashboard_recepcionista.php">Volver al panel</a>
      </div>
    </nav>

    <main class="flex-grow-1 py-4">
      <div class="container py-3">
        <?php if ($message !== ''): ?>
          <div class="alert alert-<?= $messageType ?> alerta mb-4"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="row justify-content-center">
          <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
              <div class="row g-0">
                <div class="col-lg-5 p-4 p-lg-5 bg-success-subtle d-flex flex-column justify-content-center">
                  <p class="text-uppercase fw-bold text-success mb-2">Nuevo vehículo</p>
                  <h2 class="fw-bold mb-3">Registra un vehículo nuevo para el cliente</h2>
                  <p class="text-muted mb-4">Este formulario se usa solo para vehículos nuevos. No edita ni vuelve a registrar vehículos que ya existen.</p>
                </div>
                <div class="col-lg-7 p-4 p-lg-5 bg-white">
                  <form method="POST" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="col-12">
                      <label class="form-label fw-semibold">Cliente</label>
                      <select name="cliente_id" class="form-select" required>
                        <option value="">Selecciona un cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                          <option value="<?= $cliente['id_cliente'] ?>"><?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido'] . ' - ' . $cliente['documento']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Placa</label>
                      <input type="text" name="placa" class="form-control" placeholder="ABC-123" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Marca</label>
                      <input type="text" name="marca" class="form-control" placeholder="Toyota" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Modelo</label>
                      <input type="text" name="modelo" class="form-control" placeholder="Corolla" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Color</label>
                      <input type="text" name="color" class="form-control" placeholder="Blanco" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Año</label>
                      <input type="number" name="anio" class="form-control" min="1900" max="2100" placeholder="2021" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Kilometraje</label>
                      <input type="number" name="kilometraje" class="form-control" min="0" max="4294967295" step="1" placeholder="Ej. 45000">
                    </div>
                    <div class="col-12">
                      <button type="submit" class="btn btn-verde rounded-pill">Guardar vehículo</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row justify-content-center mt-4">
          <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4">
              <div class="card-body">
                <h5 class="card-title text-success mb-3">Vehículos recientes</h5>
                <?php if ($vehiculos): ?>
                  <div class="row g-3">
                    <?php foreach ($vehiculos as $v): ?>
                      <div class="col-md-6 col-xl-4">
                        <div class="border rounded-3 p-3 bg-light h-100">
                          <div class="d-flex justify-content-between align-items-start">
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($v['placa']) ?></h6>
                            <span class="badge bg-success-subtle text-success">Activo</span>
                          </div>
                          <p class="mb-1 text-muted"><?= htmlspecialchars($v['marca']) ?> <?= htmlspecialchars($v['modelo']) ?></p>
                          <p class="mb-0 text-muted small">Cliente: <?= htmlspecialchars($v['cliente']) ?></p>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <p class="mb-0 text-muted">No hay vehículos recientes registrados.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <footer class="footer-sena bg-dark text-white py-3 mt-auto">
      <div class="container-fluid px-4 small text-center">Serviteca SENA · Recepción organizada para cada atención.</div>
    </footer>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
