<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'recepcionista') {
    header('Location: login.php');
    exit;
}
require __DIR__ . '/../db.php';
require_once __DIR__ . '/auditoria_helper.php';

$csrfToken = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$message = '';
$messageType = 'danger';
$selectedClient = 0;
$tipoCliente = 'existente';
$clienteNombre = '';
$clienteApellido = '';
$clienteDocumento = '';
$clienteTelefono = '';
$clienteDireccion = '';
$clienteCorreo = '';
$auditoriaClienteOk = true;
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;

$editId = (int)($_GET['edit'] ?? 0);
$editingVehicle = null;
if ($editId > 0) {
    $stmtEdit = $conn->prepare('SELECT v.id_vehiculo, v.placa, v.marca, v.modelo, v.color, v.anio, v.kilometraje, v.id_cliente, c.nombre, c.apellido, c.documento FROM vehiculo v JOIN cliente c ON v.id_cliente = c.id_cliente WHERE v.id_vehiculo = ?');
    $stmtEdit->bind_param('i', $editId);
    $stmtEdit->execute();
    $editRes = $stmtEdit->get_result();
    if ($editRes->num_rows === 1) {
        $editingVehicle = $editRes->fetch_assoc();
        $selectedClient = (int) $editingVehicle['id_cliente'];
    } else {
        $message = 'El vehículo solicitado no existe o no está disponible para edición.';
        $editId = 0;
    }
}

$clientes = [];
if ($result = $conn->query('SELECT id_cliente, nombre, apellido, documento, correo FROM cliente ORDER BY id_cliente DESC')) {
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
}

$vehiculos = [];
$sqlVehiculos = 'SELECT v.id_vehiculo, v.placa, v.marca, v.modelo, v.color, v.anio, v.kilometraje,
                        c.id_cliente,c.nombre AS cliente,c.apellido,c.documento
                 FROM vehiculo v JOIN cliente c ON v.id_cliente = c.id_cliente';
$whereVehiculos = '';
$totalVehiculos = 0;
if ($search !== '') {
    $whereVehiculos = ' WHERE v.placa LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ?
                        OR c.nombre LIKE ? OR c.apellido LIKE ? OR c.documento LIKE ? OR c.telefono LIKE ?';
    $term = '%' . $search . '%';
    $countVehiculos = $conn->prepare(
        'SELECT COUNT(*) AS total FROM vehiculo v JOIN cliente c ON v.id_cliente=c.id_cliente' . $whereVehiculos
    );
    $countVehiculos->bind_param('sssssss', $term, $term, $term, $term, $term, $term, $term);
    $countVehiculos->execute();
    $totalVehiculos = (int)$countVehiculos->get_result()->fetch_assoc()['total'];
} else {
    $totalVehiculos = (int)$conn->query('SELECT COUNT(*) AS total FROM vehiculo')->fetch_assoc()['total'];
}
$totalPaginasVehiculos = max(1, (int)ceil($totalVehiculos / $perPage));
$page = min($page, $totalPaginasVehiculos);
$offsetVehiculos = ($page - 1) * $perPage;
$stmtVehiculos = $conn->prepare(
    $sqlVehiculos . $whereVehiculos . ' ORDER BY v.id_vehiculo DESC LIMIT ? OFFSET ?'
);
if ($search !== '') {
    $stmtVehiculos->bind_param('sssssssii', $term, $term, $term, $term, $term, $term, $term, $perPage, $offsetVehiculos);
} else {
    $stmtVehiculos->bind_param('ii', $perPage, $offsetVehiculos);
}
$stmtVehiculos->execute();
$result = $stmtVehiculos->get_result();
while ($row = $result->fetch_assoc()) {
    $vehiculos[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $readPost = static function (string $key): string {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    };
    $submittedToken = $_POST['csrf_token'] ?? '';
    $editId = (int) $readPost('edit_id');
    $tipoCliente = $readPost('tipo_cliente') ?: 'existente';
    $selectedClient = (int) $readPost('id_cliente');
    $clienteNombre = $readPost('cliente_nombre');
    $clienteApellido = $readPost('cliente_apellido');
    $clienteDocumento = $readPost('cliente_documento');
    $clienteTelefono = $readPost('cliente_telefono');
    $clienteDireccion = $readPost('cliente_direccion');
    $clienteCorreo = $readPost('cliente_correo');
    $placa = strtoupper($readPost('placa'));
    $marca = $readPost('marca');
    $modelo = $readPost('modelo');
    $color = $readPost('color');
    $anioInput = $readPost('anio');
    $kilometrajeTexto = $readPost('kilometraje');
    $anio = filter_var($anioInput, FILTER_VALIDATE_INT);
    $kilometraje = $kilometrajeTexto === ''
        ? null
        : filter_var($kilometrajeTexto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 4294967295]]);

    if (!is_string($submittedToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $message = 'La sesión venció. Recarga la página e inténtalo nuevamente.';
    } elseif ($editId <= 0) {
        $message = 'Para registrar un vehículo, utiliza el formulario independiente de registro.';
    } elseif (
        $placa === '' || preg_match('/^[A-Z0-9-]{5,10}$/', $placa) !== 1 ||
        $marca === '' || mb_strlen($marca) > 50 ||
        $modelo === '' || mb_strlen($modelo) > 50 ||
        $color === '' || mb_strlen($color) > 30 ||
        $anio === false || $anio < 1900 || $anio > 2100 || $selectedClient <= 0 ||
        ($kilometrajeTexto !== '' && $kilometraje === false)
    ) {
        $message = 'Todos los campos del formulario son obligatorios y el año debe ser válido.';
    } else {
        if ($anio < 1900 || $anio > 2100) {
            $message = 'El año del vehículo debe estar entre 1900 y 2100.';
        } elseif ($editId > 0) {
            $checkOwner = $conn->prepare('SELECT id_vehiculo FROM vehiculo WHERE id_vehiculo = ?');
            $checkOwner->bind_param('i', $editId);
            $checkOwner->execute();
            $checkOwner->store_result();

            if ($checkOwner->num_rows !== 1) {
                $message = 'El vehículo a actualizar no existe.';
            } else {
                $updateStmt = $conn->prepare('UPDATE vehiculo SET placa = ?, marca = ?, modelo = ?, color = ?, anio = ?, kilometraje = NULLIF(?, \'\'), id_cliente = ? WHERE id_vehiculo = ?');
                $updateStmt->bind_param('ssssisii', $placa, $marca, $modelo, $color, $anio, $kilometrajeTexto, $selectedClient, $editId);
                if ($updateStmt->execute()) {
                    $auditOk = registrarActividadSistema(
                        $conn,
                        (int)$_SESSION['id_usuario'],
                        'vehiculo_actualizado',
                        'vehiculo',
                        $editId,
                        null,
                        'Recepción actualizó los datos de un vehículo.'
                    );
                    $message = $auditOk ? 'Vehículo actualizado correctamente.' : 'El vehículo se actualizó, pero no pudo guardarse en la auditoría.';
                    $messageType = $auditOk ? 'success' : 'warning';
                    $editingVehicle = ['id_vehiculo' => $editId, 'placa' => $placa, 'marca' => $marca, 'modelo' => $modelo, 'color' => $color, 'anio' => $anio, 'kilometraje' => $kilometraje, 'id_cliente' => $selectedClient];
                } else {
                    $message = 'No se pudo actualizar el vehículo.';
                }
            }
        } elseif ($tipoCliente === 'nuevo') {
            if ($clienteNombre === '' || $clienteApellido === '' || $clienteDocumento === '') {
                $message = 'Complete los datos básicos del cliente nuevo.';
            } else {
                $duplicateDocument = $conn->prepare('SELECT id_cliente FROM cliente WHERE documento = ? LIMIT 1');
                $duplicateDocument->bind_param('s', $clienteDocumento);
                $duplicateDocument->execute();
                $duplicateDocument->store_result();

                if ($duplicateDocument->num_rows > 0) {
                    $message = 'El número de identificación ya está registrado en el sistema.';
                } else {
                    $stmtCliente = $conn->prepare('INSERT INTO cliente (nombre, apellido, documento, telefono, direccion, correo, id_usuario) VALUES (?, ?, ?, ?, ?, ?, NULL)');
                    $stmtCliente->bind_param('ssssss', $clienteNombre, $clienteApellido, $clienteDocumento, $clienteTelefono, $clienteDireccion, $clienteCorreo);

                    if ($stmtCliente->execute()) {
                        $selectedClient = $stmtCliente->insert_id;
                        $auditoriaClienteOk = registrarActividadSistema(
                            $conn,
                            (int)$_SESSION['id_usuario'],
                            'cliente_creado',
                            'cliente',
                            (int)$selectedClient,
                            null,
                            'Recepción registró un cliente nuevo.'
                        );
                    } else {
                        $message = 'No se pudo crear el cliente nuevo.';
                    }
                }
            }
        } elseif ($selectedClient <= 0) {
            $message = 'Seleccione un cliente existente o registre uno nuevo.';
        }

        if ($message === '' && $selectedClient > 0) {
            $check = $conn->prepare('SELECT id_vehiculo FROM vehiculo WHERE UPPER(placa) = ? AND id_vehiculo != ?');
            $check->bind_param('si', $placa, $editId);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $message = 'La placa ya está registrada en el sistema.';
            } else {
                if ($editId > 0) {
                    $stmt = $conn->prepare('UPDATE vehiculo SET placa = ?, marca = ?, modelo = ?, color = ?, anio = ?, kilometraje = NULLIF(?, \'\'), id_cliente = ? WHERE id_vehiculo = ?');
                    $stmt->bind_param('ssssisii', $placa, $marca, $modelo, $color, $anio, $kilometrajeTexto, $selectedClient, $editId);
                } else {
                    $stmt = $conn->prepare('INSERT INTO vehiculo (placa, marca, modelo, color, anio, kilometraje, id_cliente) VALUES (?, ?, ?, ?, ?, NULLIF(?, \'\'), ?)');
                    $stmt->bind_param('ssssisi', $placa, $marca, $modelo, $color, $anio, $kilometrajeTexto, $selectedClient);
                }

                if ($stmt->execute()) {
                    $idVehiculo = $editId > 0 ? $editId : (int)$stmt->insert_id;
                    $auditOk = registrarActividadSistema(
                        $conn,
                        (int)$_SESSION['id_usuario'],
                        $editId > 0 ? 'vehiculo_actualizado' : 'vehiculo_creado',
                        'vehiculo',
                        $idVehiculo,
                        null,
                        $editId > 0 ? 'Recepción actualizó los datos de un vehículo.' : 'Recepción registró un vehículo.'
                    );
                    $auditOk = $auditOk && $auditoriaClienteOk;
                    $message = $auditOk
                        ? ($editId > 0 ? 'Vehículo actualizado correctamente.' : 'Vehículo registrado correctamente.')
                        : 'El vehículo se guardó, pero una o más acciones no pudieron registrarse en la auditoría.';
                    $messageType = $auditOk ? 'success' : 'warning';

                    $vehiculos = [];
                    if ($result = $conn->query('SELECT v.id_vehiculo, v.placa, v.marca, v.modelo, v.color, v.anio, v.kilometraje, c.nombre AS cliente FROM vehiculo v JOIN cliente c ON v.id_cliente = c.id_cliente ORDER BY v.id_vehiculo DESC')) {
                        while ($row = $result->fetch_assoc()) {
                            $vehiculos[] = $row;
                        }
                    }
                } else {
                    $message = $editId > 0 ? 'No se pudo actualizar el vehículo. Intente nuevamente.' : 'No se pudo guardar el vehículo. Intente nuevamente.';
                }
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
  <title>Ingreso de vehículo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
</head>
<body class="admin-body module-page module-reception-vehicles">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4>Recepción</h4></div>
      <nav class="sidebar-nav">
        <a href="dashboard_recepcionista.php">Volver al panel</a>
        <a href="recepcionista_clientes.php">Clientes</a>
        <a href="recepcionista_vehiculos.php"class="active" >Vehículos</a>
        <a href="recepcionista_servicios.php">Servicios</a>
        <a href="registro_servicios.php" >Registrar servicio</a>
        <a href="logout.php">Salir</a>
      </nav>
    </aside>
    <main class="admin-content">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <p class="text-muted mb-1">Recepción</p>
          <h2 class="section-title mb-0">Vehículos registrados</h2>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <a href="dashboard_recepcionista.php" class="btn btn-outline-verde rounded-pill">Volver al panel</a>
          <a href="registro_cliente.php" class="btn btn-outline-verde rounded-pill">Registrar cliente</a>
          <a href="registro_recepcionista.php" class="btn btn-verde rounded-pill">Registrar vehículo</a>
        </div>
      </div>

      <?php if ($message !== '' && !$editingVehicle): ?>
        <div class="alert alert-<?= $messageType ?> alerta" role="status"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <div class="row g-4">
        <div class="col-12">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
              <h5 class="card-title text-success">Vehículos registrados</h5>
              <div class="mb-3">
                <form method="GET" class="row g-2 align-items-end">
                  <div class="col-md-8">
                    <label class="form-label">Buscar vehículo</label>
                    <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Placa, marca, modelo, propietario o teléfono">
                  </div>
                  <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-verde rounded-pill flex-grow-1">Buscar</button>
                    <a href="recepcionista_vehiculos.php" class="btn btn-outline-verde rounded-pill">Limpiar</a>
                  </div>
                </form>
              </div>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Placa</th>
                      <th>Marca</th>
                      <th>Modelo</th>
                      <th>Color</th>
                      <th>Año / km</th>
                      <th>Cliente</th>
                      <th>Acción</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($vehiculos as $v): ?>
                      <tr>
                        <td><?= $v['id_vehiculo'] ?></td>
                        <td><?= htmlspecialchars($v['placa']) ?></td>
                        <td><?= htmlspecialchars($v['marca']) ?></td>
                        <td><?= htmlspecialchars($v['modelo']) ?></td>
                        <td><?= htmlspecialchars($v['color']) ?></td>
                        <td><?= htmlspecialchars($v['anio'] ?? '—') ?><br><small><?= $v['kilometraje'] === null ? 'No registrado' : htmlspecialchars(number_format((int) $v['kilometraje'], 0, ',', '.') . ' km') ?></small></td>
                        <td><?= htmlspecialchars($v['cliente'].' '.$v['apellido']) ?><br><small><?= htmlspecialchars($v['documento']) ?></small></td>
                        <td class="d-flex gap-1"><a href="recepcionista_vehiculos.php?edit=<?= (int)$v['id_vehiculo'] ?>" class="btn btn-sm btn-outline-verde rounded-pill">Editar</a><a href="historial_servicios.php?vehiculo=<?= (int)$v['id_vehiculo'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill">Historial</a></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php if ($totalPaginasVehiculos > 1): ?>
                <nav aria-label="Paginación de vehículos"><ul class="pagination justify-content-center mb-0">
                  <?php for ($number = 1; $number <= $totalPaginasVehiculos; $number++): ?>
                    <li class="page-item <?= $number === $page ? 'active' : '' ?>"><a class="page-link" href="?q=<?= urlencode($search) ?>&p=<?= $number ?>"><?= $number ?></a></li>
                  <?php endfor; ?>
                </ul></nav>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <?php if ($editingVehicle): ?>
      <div class="card border-0 shadow-sm rounded-4 mt-4" id="formulario-vehiculo">
        <div class="card-body">
          <h5 class="card-title text-success">Editar vehículo</h5>
          <p class="text-muted small">Actualiza los datos del vehículo para mantener la información correcta.</p>
          <?php if ($message !== ''): ?>
            <div class="alert alert-<?= $messageType ?> alerta"><?= htmlspecialchars($message) ?></div>
          <?php endif; ?>
          <form method="POST" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="edit_id" value="<?= (int)($editingVehicle['id_vehiculo'] ?? 0) ?>">
            <input type="hidden" name="tipo_cliente" value="existente">
            <div class="col-12">
              <label class="form-label">Cliente</label>
              <select name="id_cliente" class="form-select" required>
                <option value="">Seleccione un cliente</option>
                <?php foreach ($clientes as $c): ?>
                  <option value="<?= $c['id_cliente'] ?>" <?= $selectedClient == $c['id_cliente'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido'] . ' - ' . $c['documento']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Placa</label>
              <input type="text" name="placa" class="form-control" value="<?= htmlspecialchars($editingVehicle['placa'] ?? '') ?>" placeholder="ABC-123" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Marca</label>
              <input type="text" name="marca" class="form-control" value="<?= htmlspecialchars($editingVehicle['marca'] ?? '') ?>" placeholder="Toyota" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Modelo</label>
              <input type="text" name="modelo" class="form-control" value="<?= htmlspecialchars($editingVehicle['modelo'] ?? '') ?>" placeholder="Corolla" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Color</label>
              <input type="text" name="color" class="form-control" value="<?= htmlspecialchars($editingVehicle['color'] ?? '') ?>" placeholder="Blanco" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Año</label>
              <input type="number" name="anio" class="form-control" min="1900" max="2100" value="<?= htmlspecialchars((string)($editingVehicle['anio'] ?? '')) ?>" placeholder="2022" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Kilometraje</label>
              <input type="number" name="kilometraje" class="form-control" min="0" max="4294967295" step="1" value="<?= htmlspecialchars((string)($editingVehicle['kilometraje'] ?? '')) ?>" placeholder="Ej. 45000">
            </div>
            <div class="col-12 d-flex flex-wrap gap-2">
              <button type="submit" class="btn btn-verde rounded-pill">Actualizar vehículo</button>
              <?php if ($editingVehicle): ?>
                <a href="recepcionista_vehiculos.php" class="btn btn-outline-verde rounded-pill">Cancelar</a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
