<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'cliente') {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';
require_once dirname(__DIR__) . '/compartido/auditoria_helper.php';

$csrfToken = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$basePath = '';
$homePath = '../index.html';
$logoutPath = 'logout.php';

$message = '';
$messageType = 'danger';
$vehicleForm = ['placa' => '', 'marca' => '', 'modelo' => '', 'color' => '', 'anio' => '', 'kilometraje' => ''];
$registeredVehicleId = 0;

$cliente = null;
$stmt = $conn->prepare('SELECT c.id_cliente, c.nombre, c.apellido, c.correo, c.telefono, c.direccion FROM cliente c JOIN usuario u ON c.id_usuario = u.id_usuario WHERE c.id_usuario = ?');
$stmt->bind_param('i', $_SESSION['id_usuario']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $cliente = $result->fetch_assoc();
}

$editId = (int)($_GET['edit'] ?? 0);
$editingVehicle = null;
if ($editId > 0 && $cliente) {
    $stmtEdit = $conn->prepare('SELECT id_vehiculo, placa, marca, modelo, color, anio, kilometraje, id_cliente FROM vehiculo WHERE id_vehiculo = ? AND id_cliente = ?');
    $stmtEdit->bind_param('ii', $editId, $cliente['id_cliente']);
    $stmtEdit->execute();
    $editResult = $stmtEdit->get_result();
    if ($editResult->num_rows === 1) {
        $editingVehicle = $editResult->fetch_assoc();
    } else {
        $message = 'No tienes permisos para editar este vehículo.';
        $editId = 0;
    }
}

$vehiculos = [];
if ($cliente) {
    $id = $cliente['id_cliente'];
    $query = "SELECT id_vehiculo, placa, marca, modelo, color, anio, kilometraje FROM vehiculo WHERE id_cliente = $id ORDER BY id_vehiculo DESC";
    if ($res = $conn->query($query)) {
        while ($row = $res->fetch_assoc()) {
            $vehiculos[] = $row;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editInput = $_POST['edit_id'] ?? '';
    $editId = is_string($editInput) ? (int) $editInput : 0;
    foreach ($vehicleForm as $field => $value) {
        $input = $_POST[$field] ?? '';
        $vehicleForm[$field] = is_string($input) ? trim($input) : '';
    }
    $placa = strtoupper($vehicleForm['placa']);
    $marca = $vehicleForm['marca'];
    $modelo = $vehicleForm['modelo'];
    $color = $vehicleForm['color'];
    $anioTexto = $vehicleForm['anio'];
    $kilometrajeTexto = $vehicleForm['kilometraje'];
    $anio = filter_var($anioTexto, FILTER_VALIDATE_INT);
    $kilometraje = $kilometrajeTexto === ''
        ? null
        : filter_var($kilometrajeTexto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 4294967295]]);

    $submittedToken = $_POST['csrf_token'] ?? '';
    if (
        !is_string($submittedToken) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $message = 'La sesión venció. Recarga la página e inténtalo nuevamente.';
    } elseif ($cliente === null) {
        $message = 'No se pudo identificar el cliente autenticado.';
    } elseif (
        $placa === '' || preg_match('/^[A-Z0-9-]{5,10}$/', $placa) !== 1 ||
        $marca === '' || $modelo === '' || $color === '' ||
        mb_strlen($marca) > 50 || mb_strlen($modelo) > 50 || mb_strlen($color) > 30 ||
        $anio === false ||
        ($kilometrajeTexto !== '' && $kilometraje === false)
    ) {
        $message = 'Completa todos los campos para registrar el vehículo.';
    } elseif ($anio < 1900 || $anio > (int) date('Y') + 1 || $anio > 2100) {
        $message = 'El año del vehículo debe estar entre 1900 y 2100.';
    } else {
        if ($editId > 0) {
            $checkOwner = $conn->prepare(
                'SELECT id_vehiculo FROM vehiculo WHERE id_vehiculo = ? AND id_cliente = ? LIMIT 1'
            );
            $checkOwner->bind_param('ii', $editId, $cliente['id_cliente']);
            $checkOwner->execute();
            $checkOwner->store_result();

            if ($checkOwner->num_rows !== 1) {
                $message = 'No tienes permisos para editar este vehículo.';
            } else {
                $duplicate = $conn->prepare(
                    'SELECT id_vehiculo FROM vehiculo WHERE UPPER(placa) = ? AND id_vehiculo <> ? LIMIT 1'
                );
                $duplicate->bind_param('si', $placa, $editId);
                $duplicate->execute();
                $duplicate->store_result();

                if ($duplicate->num_rows > 0) {
                    $message = 'La placa ya está asociada a otro vehículo.';
                } else {
                    $update = $conn->prepare(
                        'UPDATE vehiculo SET placa = ?, marca = ?, modelo = ?, color = ?, anio = ?, kilometraje = NULLIF(?, \'\')
                         WHERE id_vehiculo = ? AND id_cliente = ?'
                    );
                    $update->bind_param('ssssisii', $placa, $marca, $modelo, $color, $anio, $kilometrajeTexto, $editId, $cliente['id_cliente']);
                    if ($update->execute()) {
                        $auditOk = registrarActividadSistema(
                            $conn,
                            (int) $_SESSION['id_usuario'],
                            'vehiculo_actualizado',
                            'vehiculo',
                            $editId,
                            null,
                            'El cliente actualizó los datos de un vehículo propio.'
                        );
                        $message = $auditOk
                            ? 'Vehículo actualizado correctamente.'
                            : 'El vehículo se actualizó, pero no pudo registrarse en la auditoría.';
                        $messageType = $auditOk ? 'success' : 'warning';
                        $editingVehicle = [
                            'id_vehiculo' => $editId,
                            'placa' => $placa,
                            'marca' => $marca,
                            'modelo' => $modelo,
                            'color' => $color,
                            'anio' => $anio,
                            'kilometraje' => $kilometraje
                        ];
                    } else {
                        $message = 'No se pudo actualizar el vehículo.';
                    }
                }
            }
        } else {
            $check = $conn->prepare('SELECT id_vehiculo FROM vehiculo WHERE UPPER(placa) = ?');
            $check->bind_param('s', $placa);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $message = 'La placa ya está registrada en el sistema.';
            } else {
                $stmtVehiculo = $conn->prepare(
                    'INSERT INTO vehiculo (placa, marca, modelo, color, anio, kilometraje, id_cliente)
                     VALUES (?, ?, ?, ?, ?, NULLIF(?, \'\'), ?)'
                );
                $stmtVehiculo->bind_param('ssssisi', $placa, $marca, $modelo, $color, $anio, $kilometrajeTexto, $cliente['id_cliente']);
                if ($stmtVehiculo->execute()) {
                    $idVehiculo = (int)$stmtVehiculo->insert_id;
                    $registeredVehicleId = $idVehiculo;
                    $auditOk = registrarActividadSistema(
                        $conn,
                        (int)$_SESSION['id_usuario'],
                        'vehiculo_creado',
                        'vehiculo',
                        $idVehiculo,
                        null,
                        'El cliente registró un vehículo propio.'
                    );
                    $message = $auditOk ? 'Vehículo registrado correctamente.' : 'El vehículo se registró, pero no pudo guardarse en la auditoría.';
                    $messageType = $auditOk ? 'success' : 'warning';
                    $vehiculos = [];
                    $refreshVehicles = $conn->prepare(
                        'SELECT id_vehiculo, placa, marca, modelo, color, anio, kilometraje
                         FROM vehiculo WHERE id_cliente = ? ORDER BY id_vehiculo DESC'
                    );
                    $refreshVehicles->bind_param('i', $cliente['id_cliente']);
                    $refreshVehicles->execute();
                    $vehiculos = $refreshVehicles->get_result()->fetch_all(MYSQLI_ASSOC);
                } else {
                    $message = 'No se pudo registrar el vehículo en este momento.';
                }
            }
        }
        if ($messageType === 'danger') {
            $vehicleForm = [
                'placa' => $placa,
                'marca' => $marca,
                'modelo' => $modelo,
                'color' => $color,
                'anio' => $anioTexto,
                'kilometraje' => $kilometrajeTexto
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro de vehículo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
  <script src="../js/form-validation.js?v=20261109-confirm" defer></script>
</head>
<body class="bg-light module-page module-customer-vehicle-registration">
  <div class="min-vh-100 d-flex flex-column">
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm border-bottom">
      <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="<?= $homePath ?>">Serviteca SENA</a>
        <div class="ms-auto">
          <a class="btn btn-light text-success rounded-pill" href="<?= $logoutPath ?>">Salir</a>
        </div>
      </div>
    </nav>

    <main class="flex-grow-1 py-4">
      <div class="container py-3">
        <?php if ($message !== ''): ?>
          <div class="alert alert-<?= $messageType ?> alerta mb-4"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($registeredVehicleId > 0): ?>
          <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>Si deseas, solicita una revisión o servicio para el vehículo que acabas de registrar.</span>
            <a href="cliente_solicitud_servicio.php?vehiculo=<?= $registeredVehicleId ?>" class="btn btn-outline-verde rounded-pill">Solicitar servicio</a>
          </div>
        <?php endif; ?>

        <div class="row justify-content-center">
          <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
              <div class="row g-0">
                <div class="col-lg-5 p-4 p-lg-5 bg-gradient-hero d-flex flex-column justify-content-center">
                  <p class="text-uppercase fw-bold texto-verde mb-2">Registro</p>
                  <h2 class="fw-bold mb-3">Registra tu vehículo en pocos pasos</h2>
                  <p class="text-muted mb-4">Completa el formulario para que tu auto quede asociado a tu cuenta y puedas seguir sus servicios con mayor organización.</p>
                  <div class="d-flex flex-wrap gap-2">
                    <a href="../../index.html" class="btn btn-outline-verde rounded-pill">Volver al panel</a>
                    <a href="cliente_vehiculos.php" class="btn btn-verde rounded-pill">Ver mis vehículos</a>
                  </div>
                </div>
                <div class="col-lg-7 p-4 p-lg-5 bg-white">
                  <form method="POST" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="edit_id" value="<?= (int)($editingVehicle['id_vehiculo'] ?? $editId) ?>">
                    <div class="col-12">
                      <label class="form-label fw-semibold">Placa</label>
                      <input type="text" name="placa" class="form-control" maxlength="10" pattern="[A-Za-z0-9-]{5,10}" value="<?= htmlspecialchars($editingVehicle['placa'] ?? $vehicleForm['placa']) ?>" placeholder="ABC-123" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Marca</label>
                      <input type="text" name="marca" class="form-control" maxlength="50" value="<?= htmlspecialchars($editingVehicle['marca'] ?? $vehicleForm['marca']) ?>" placeholder="Toyota" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Modelo</label>
                      <input type="text" name="modelo" class="form-control" maxlength="50" value="<?= htmlspecialchars($editingVehicle['modelo'] ?? $vehicleForm['modelo']) ?>" placeholder="Corolla" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Color</label>
                      <input type="text" name="color" class="form-control" maxlength="30" value="<?= htmlspecialchars($editingVehicle['color'] ?? $vehicleForm['color']) ?>" placeholder="Blanco" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Año</label>
                      <input type="number" name="anio" class="form-control" min="1900" max="<?= min(2100, (int) date('Y') + 1) ?>" value="<?= htmlspecialchars((string)($editingVehicle['anio'] ?? $vehicleForm['anio'])) ?>" placeholder="2021" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Kilometraje</label>
                      <input type="number" name="kilometraje" class="form-control" min="0" max="4294967295" step="1" value="<?= htmlspecialchars((string)($editingVehicle['kilometraje'] ?? $vehicleForm['kilometraje'])) ?>" placeholder="Ej. 45000">
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                      <button type="submit" class="btn btn-verde rounded-pill"><?= $editingVehicle ? 'Actualizar vehículo' : 'Guardar vehículo' ?></button>
                      <?php if ($editingVehicle): ?>
                        <a href="registro_serviteca.php" class="btn btn-outline-verde rounded-pill">Cancelar</a>
                      <?php endif; ?>
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
                <h5 class="card-title text-success mb-3">Vehículos registrados</h5>
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
                          <p class="mb-0 text-muted small">Color: <?= htmlspecialchars($v['color']) ?> · Año: <?= htmlspecialchars($v['anio']) ?> · Kilometraje: <?= htmlspecialchars($v['kilometraje'] === null ? 'No registrado' : number_format((int)$v['kilometraje'], 0, ',', '.').' km') ?></p>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <p class="mb-0 text-muted">Aún no tienes vehículos registrados.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <footer class="footer-sena bg-dark text-white py-3 mt-auto">
      <div class="container-fluid px-4 small text-center">
        Serviteca SENA · Gestión sencilla para cada cliente.
      </div>
    </footer>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
