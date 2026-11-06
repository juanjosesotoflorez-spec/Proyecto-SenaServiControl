<?php
session_start();
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol'] ?? '') !== 'cliente') {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../db.php';
require_once __DIR__ . '/auditoria_helper.php';

$csrfToken = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$tiposServicio = ['Mantenimiento', 'Reparacion', 'Revision general', 'Diagnostico'];
$message = '';
$messageType = 'danger';
$form = ['id_vehiculo' => '', 'tipo_servicio' => '', 'descripcion' => '', 'fecha_solicitada' => ''];

$stmt = $conn->prepare('SELECT id_cliente FROM cliente WHERE id_usuario = ? LIMIT 1');
$stmt->bind_param('i', $_SESSION['id_usuario']);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$vehiculos = [];
$solicitudes = [];

if ($cliente) {
    $idCliente = (int) $cliente['id_cliente'];
    $stmt = $conn->prepare(
        'SELECT id_vehiculo, placa, marca, modelo, kilometraje
         FROM vehiculo WHERE id_cliente = ? ORDER BY placa'
    );
    $stmt->bind_param('i', $idCliente);
    $stmt->execute();
    $vehiculos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare(
        'SELECT ss.id_solicitud, ss.tipo_servicio, ss.descripcion, ss.fecha_solicitada,
                ss.fecha_solicitud, ss.estado, ss.id_servicio, v.placa
         FROM solicitud_servicio ss
         JOIN vehiculo v ON v.id_vehiculo = ss.id_vehiculo
         WHERE v.id_cliente = ?
         ORDER BY ss.fecha_solicitud DESC, ss.id_solicitud DESC'
    );
    $stmt->bind_param('i', $idCliente);
    $stmt->execute();
    $solicitudes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $field => $value) {
        $input = $_POST[$field] ?? '';
        $form[$field] = is_string($input) ? trim($input) : '';
    }
    $submittedToken = $_POST['csrf_token'] ?? '';
    $idVehiculo = filter_var($form['id_vehiculo'], FILTER_VALIDATE_INT);
    $fecha = DateTime::createFromFormat('!Y-m-d', $form['fecha_solicitada']);
    $fechaValida = $fecha !== false && $fecha->format('Y-m-d') === $form['fecha_solicitada'];

    if (
        !is_string($submittedToken) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $message = 'La sesión venció. Recarga la página e inténtalo nuevamente.';
    } elseif (!$cliente) {
        $message = 'No se encontró el perfil asociado a tu cuenta.';
    } elseif (
        $idVehiculo === false || $idVehiculo <= 0 ||
        !in_array($form['tipo_servicio'], $tiposServicio, true) ||
        $form['descripcion'] === '' || mb_strlen($form['descripcion']) > 2000 ||
        !$fechaValida || $form['fecha_solicitada'] < date('Y-m-d') ||
        $form['fecha_solicitada'] > date('Y-m-d', strtotime('+1 year'))
    ) {
        $message = 'Completa los datos y selecciona una fecha válida dentro del próximo año.';
    } else {
        $stmt = $conn->prepare(
            'SELECT id_vehiculo FROM vehiculo WHERE id_vehiculo = ? AND id_cliente = ? LIMIT 1'
        );
        $stmt->bind_param('ii', $idVehiculo, $idCliente);
        $stmt->execute();
        $esPropio = $stmt->get_result()->num_rows === 1;

        if (!$esPropio) {
            $message = 'Selecciona uno de los vehículos asociados a tu cuenta.';
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO solicitud_servicio
                 (id_vehiculo, tipo_servicio, descripcion, fecha_solicitada, estado)
                 VALUES (?, ?, ?, ?, 'Pendiente')"
            );
            $stmt->bind_param(
                'isss',
                $idVehiculo,
                $form['tipo_servicio'],
                $form['descripcion'],
                $form['fecha_solicitada']
            );

            if ($stmt->execute()) {
                $idSolicitud = (int) $stmt->insert_id;
                $auditOk = registrarActividadSistema(
                    $conn,
                    (int) $_SESSION['id_usuario'],
                    'solicitud_servicio_creada',
                    'solicitud_servicio',
                    $idSolicitud,
                    null,
                    'El cliente solicitó una cita o servicio para su vehículo.'
                );
                $message = $auditOk
                    ? 'Solicitud enviada. Recepción revisará la fecha y la asignación del servicio.'
                    : 'La solicitud se guardó, pero no pudo registrarse en la auditoría.';
                $messageType = $auditOk ? 'success' : 'warning';
                $form = ['id_vehiculo' => '', 'tipo_servicio' => '', 'descripcion' => '', 'fecha_solicitada' => ''];

                $stmt = $conn->prepare(
                    'SELECT ss.id_solicitud, ss.tipo_servicio, ss.descripcion, ss.fecha_solicitada,
                            ss.fecha_solicitud, ss.estado, ss.id_servicio, v.placa
                     FROM solicitud_servicio ss
                     JOIN vehiculo v ON v.id_vehiculo = ss.id_vehiculo
                     WHERE v.id_cliente = ?
                     ORDER BY ss.fecha_solicitud DESC, ss.id_solicitud DESC'
                );
                $stmt->bind_param('i', $idCliente);
                $stmt->execute();
                $solicitudes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else {
                $message = 'No se pudo guardar la solicitud. Inténtalo de nuevo.';
            }
        }
    }
}

$vehiculoSeleccionado = $_GET['vehiculo'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && is_string($vehiculoSeleccionado) &&
    ctype_digit($vehiculoSeleccionado)) {
    $form['id_vehiculo'] = $vehiculoSeleccionado;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitar servicio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
</head>
<body class="admin-body module-page module-customer-service-request">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4>Cliente</h4></div>
      <nav class="sidebar-nav">
        <a href="dashboard_cliente.php">Volver al panel</a>
        <a href="cliente_vehiculos.php">Mis vehículos</a>
        <a href="cliente_solicitud_servicio.php" class="active">Solicitar servicio</a>
        <a href="cliente_servicios.php">Mis servicios</a>
        <a href="logout.php">Salir</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h1 class="section-title">Solicitar cita o servicio</h1>
      <p class="text-muted">Elige uno de tus vehículos, explica qué necesita y propone una fecha. La solicitud quedará pendiente hasta que recepción la revise.</p>
      <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?> alerta" role="status"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <?php if (!$vehiculos): ?>
        <div class="alert alert-warning alerta">Primero registra un vehículo para poder solicitar un servicio.</div>
        <a href="registro_serviteca.php" class="btn btn-verde rounded-pill">Registrar vehículo</a>
      <?php else: ?>
        <section class="dashboard-panel mb-4">
          <form method="POST" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="col-md-6">
              <label class="form-label" for="id_vehiculo">Vehículo</label>
              <select class="form-select" id="id_vehiculo" name="id_vehiculo" required>
                <option value="">Selecciona tu vehículo</option>
                <?php foreach ($vehiculos as $vehiculo): ?>
                  <option value="<?= (int) $vehiculo['id_vehiculo'] ?>" <?= (string) $vehiculo['id_vehiculo'] === $form['id_vehiculo'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($vehiculo['placa'] . ' · ' . $vehiculo['marca'] . ' ' . $vehiculo['modelo']) ?>
                    <?= $vehiculo['kilometraje'] === null ? '' : ' · ' . number_format((int) $vehiculo['kilometraje'], 0, ',', '.') . ' km' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="tipo_servicio">Servicio que necesitas</label>
              <select class="form-select" id="tipo_servicio" name="tipo_servicio" required>
                <option value="">Selecciona una opción</option>
                <?php foreach ($tiposServicio as $tipo): ?>
                  <option value="<?= htmlspecialchars($tipo) ?>" <?= $form['tipo_servicio'] === $tipo ? 'selected' : '' ?>><?= htmlspecialchars($tipo) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="fecha_solicitada">Fecha preferida</label>
              <input class="form-control" type="date" id="fecha_solicitada" name="fecha_solicitada" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+1 year')) ?>" value="<?= htmlspecialchars($form['fecha_solicitada']) ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label" for="descripcion">Describe lo que necesita el vehículo</label>
              <textarea class="form-control" id="descripcion" name="descripcion" maxlength="2000" rows="4" required><?= htmlspecialchars($form['descripcion']) ?></textarea>
            </div>
            <div class="col-12 d-flex flex-wrap gap-2">
              <button type="submit" class="btn btn-verde rounded-pill">Enviar solicitud</button>
              <a href="dashboard_cliente.php" class="btn btn-outline-verde rounded-pill">Volver al panel</a>
            </div>
          </form>
        </section>
      <?php endif; ?>

      <section class="dashboard-panel">
        <h2 class="h5 section-title">Mis solicitudes</h2>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Fecha</th><th>Vehículo</th><th>Servicio</th><th>Fecha preferida</th><th>Estado</th><th>Servicio asignado</th></tr></thead>
            <tbody>
              <?php foreach ($solicitudes as $solicitud): ?>
                <tr>
                  <td><?= htmlspecialchars($solicitud['fecha_solicitud']) ?></td>
                  <td><?= htmlspecialchars($solicitud['placa']) ?></td>
                  <td><strong><?= htmlspecialchars($solicitud['tipo_servicio']) ?></strong><br><small><?= nl2br(htmlspecialchars($solicitud['descripcion'])) ?></small></td>
                  <td><?= htmlspecialchars($solicitud['fecha_solicitada']) ?></td>
                  <td><?= htmlspecialchars($solicitud['estado']) ?></td>
                  <td>
                    <?php if ($solicitud['id_servicio'] !== null): ?>
                      <a href="historial_servicios.php?servicio=<?= (int) $solicitud['id_servicio'] ?>">Ver servicio #<?= (int) $solicitud['id_servicio'] ?></a>
                    <?php else: ?>Pendiente de asignación<?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$solicitudes): ?><tr><td colspan="6" class="text-muted">Aún no has enviado solicitudes de servicio.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
