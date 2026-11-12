<?php
session_start();

$rol = $_SESSION['rol'] ?? '';
if (!isset($_SESSION['id_usuario']) || !in_array($rol, ['administrador', 'recepcionista', 'cliente'], true)) {
    header('Location: login.php');
    exit;
}

require dirname(__DIR__, 2) . '/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensaje = '';
$tipoMensaje = 'danger';
$idCliente = null;

if ($rol === 'cliente') {
    $stmt = $conn->prepare('SELECT id_cliente FROM cliente WHERE id_usuario = ?');
    $stmt->bind_param('i', $_SESSION['id_usuario']);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
    $idCliente = $cliente ? (int) $cliente['id_cliente'] : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $mensaje = 'La sesión del formulario venció. Recarga la página e inténtalo nuevamente.';
    } elseif (
        isset($_POST['action']) &&
        $_POST['action'] === 'confirm' &&
        $rol === 'cliente' &&
        $idCliente !== null
    ) {
        $idNotificacion = (int) ($_POST['id_notificacion'] ?? 0);
        $stmt = $conn->prepare(
            'SELECT n.id_notificacion
             FROM notificacion n
             WHERE n.id_notificacion = ? AND n.id_cliente = ?'
        );
        $stmt->bind_param('ii', $idNotificacion, $idCliente);
        $stmt->execute();
        $notificacion = $stmt->get_result()->fetch_assoc();

        if (!$notificacion) {
            $mensaje = 'La notificación no existe o no pertenece a tu cuenta.';
        } else {
            $stmt = $conn->prepare(
                'SELECT id_confirmacion FROM confirmacion_notificacion WHERE id_notificacion = ?'
            );
            $stmt->bind_param('i', $idNotificacion);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $mensaje = 'Esta notificación ya fue confirmada.';
                $tipoMensaje = 'info';
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO confirmacion_notificacion (id_notificacion) VALUES (?)'
                );
                $stmt->bind_param('i', $idNotificacion);
                if ($stmt->execute()) {
                    $mensaje = 'Recepción de la información confirmada.';
                    $tipoMensaje = 'success';
                } else {
                    $mensaje = 'No se pudo registrar la confirmación.';
                }
            }
        }
    } elseif (
        isset($_POST['action']) &&
        $_POST['action'] === 'create' &&
        in_array($rol, ['administrador', 'recepcionista'], true)
    ) {
        $idServicio = (int) ($_POST['id_servicio'] ?? 0);
        $contenido = trim($_POST['mensaje'] ?? '');
        $categoria = $_POST['categoria'] ?? 'seguimiento';
        $stmt = $conn->prepare(
            'SELECT s.estado, c.id_cliente
             FROM servicio s
             JOIN vehiculo v ON v.id_vehiculo = s.id_vehiculo
             JOIN cliente c ON c.id_cliente = v.id_cliente
             WHERE s.id_servicio = ?'
        );
        $stmt->bind_param('i', $idServicio);
        $stmt->execute();
        $servicio = $stmt->get_result()->fetch_assoc();

        if (
            !$servicio ||
            $idServicio <= 0 ||
            $contenido === '' ||
            $servicio['estado'] === null ||
            trim((string) $servicio['estado']) === '' ||
            !in_array($categoria, ['seguimiento', 'entrega'], true)
        ) {
            $mensaje = 'Selecciona un servicio válido e ingresa el mensaje.';
        } elseif ($categoria === 'entrega' && $servicio['estado'] !== 'Finalizado') {
            $mensaje = 'La notificación de entrega solo se permite cuando el servicio está finalizado.';
        } else {
            $contenido = ($categoria === 'entrega' ? 'Entrega del vehículo: ' : 'Actualización del servicio: ') . $contenido;
            $estadoNotificacion = 'Interna';
            $idClienteServicio = (int) $servicio['id_cliente'];
            $estadoServicio = (string) $servicio['estado'];
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO seguimiento (fecha, fecha_hora, estado, comentario, id_servicio, id_tecnico, aprendiz)
                     VALUES (CURDATE(), NOW(), ?, ?, ?, NULL, '')"
                );
                $stmt->bind_param('ssi', $estadoServicio, $contenido, $idServicio);
                $stmt->execute();

                $stmt = $conn->prepare(
                    'INSERT INTO notificacion (mensaje, fecha_envio, estado, id_cliente, id_servicio)
                     VALUES (?, NOW(), ?, ?, ?)'
                );
                $stmt->bind_param('ssii', $contenido, $estadoNotificacion, $idClienteServicio, $idServicio);
                $stmt->execute();
                $conn->commit();
                $mensaje = 'Notificación interna registrada. No se envió por correo, SMS ni WhatsApp.';
                $tipoMensaje = 'success';
            } catch (mysqli_sql_exception $error) {
                $conn->rollback();
                error_log('Error al guardar notificación interna: ' . $error->getMessage());
                $mensaje = 'No se pudo guardar la notificación.';
            }
        }
    } else {
        $mensaje = 'No tienes permiso para realizar esta acción.';
    }
}

$notificaciones = [];
if ($rol === 'cliente' && $idCliente !== null) {
    $stmt = $conn->prepare(
        'SELECT n.id_notificacion, n.mensaje, n.fecha_envio, n.estado, n.id_servicio,
                s.tipo_servicio, s.estado AS estado_servicio,
                cn.fecha_confirmacion
         FROM notificacion n
         LEFT JOIN servicio s ON s.id_servicio = n.id_servicio
         LEFT JOIN confirmacion_notificacion cn ON cn.id_notificacion = n.id_notificacion
         WHERE n.id_cliente = ?
         ORDER BY n.fecha_envio DESC, n.id_notificacion DESC'
    );
    $stmt->bind_param('i', $idCliente);
} elseif (in_array($rol, ['administrador', 'recepcionista'], true)) {
    $stmt = $conn->prepare(
        'SELECT n.id_notificacion, n.mensaje, n.fecha_envio, n.estado, n.id_servicio,
                c.nombre AS cliente, c.apellido AS cliente_apellido,
                s.tipo_servicio, s.estado AS estado_servicio,
                cn.fecha_confirmacion
         FROM notificacion n
         JOIN cliente c ON c.id_cliente = n.id_cliente
         LEFT JOIN servicio s ON s.id_servicio = n.id_servicio
         LEFT JOIN confirmacion_notificacion cn ON cn.id_notificacion = n.id_notificacion
         ORDER BY n.fecha_envio DESC, n.id_notificacion DESC'
    );
} else {
    $stmt = null;
}

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notificaciones[] = $row;
    }
}

$serviciosDisponibles = [];
if (in_array($rol, ['administrador', 'recepcionista'], true)) {
    $result = $conn->query(
        'SELECT s.id_servicio, s.tipo_servicio, s.estado, v.placa, c.nombre, c.apellido
         FROM servicio s
         JOIN vehiculo v ON v.id_vehiculo = s.id_vehiculo
         JOIN cliente c ON c.id_cliente = v.id_cliente
         ORDER BY s.id_servicio DESC'
    );
    while ($row = $result->fetch_assoc()) {
        $serviciosDisponibles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notificaciones</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
  <script src="../js/form-validation.js?v=20261109-confirm" defer></script>
</head>
<body class="admin-body module-page module-notifications">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4><?= $rol === 'cliente' ? 'Cliente' : ($rol === 'administrador' ? 'Administración' : 'Recepción') ?></h4></div>
      <nav class="sidebar-nav">
        <?php if ($rol === 'cliente'): ?>
          <a href="../../index.html">Volver al panel</a>
          <a href="cliente_vehiculos.php">Mis vehículos</a>
          <a href="cliente_servicios.php">Mis servicios</a>
        <?php elseif ($rol === 'administrador'): ?>
          <a href="../../index.html">Volver al panel</a>
          <a href="admin_servicios.php">Servicios</a>
          <a href="notificaciones.php" class="active">Notificaciones</a>
        <?php else: ?>
          <a href="../../index.html">Volver al panel</a>
          <a href="recepcionista_servicios.php">Servicios</a>
          <a href="registro_servicios.php">Registrar servicio</a>
          <a href="notificaciones.php" class="active">Notificaciones</a>
        <?php endif; ?>
        <a href="logout.php">Salir</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h2 class="section-title"><?= $rol === 'cliente' ? 'Mis notificaciones' : 'Notificaciones de servicios' ?></h2>
      <p class="texto-gris">Avisos internos del sistema. No representan el envío de correo, SMS ni WhatsApp.</p>

      <?php if ($mensaje !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($tipoMensaje) ?> alerta"><?= htmlspecialchars($mensaje) ?></div>
      <?php endif; ?>

      <?php if (in_array($rol, ['administrador', 'recepcionista'], true)): ?>
        <div class="dashboard-panel mb-4">
          <h5 class="section-title">Registrar notificación interna</h5>
          <form method="POST" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="create">
            <div class="col-md-8">
              <label class="form-label" for="id_servicio">Servicio y destinatario</label>
              <select class="form-select" id="id_servicio" name="id_servicio" required>
                <option value="">Seleccione un servicio</option>
                <?php foreach ($serviciosDisponibles as $servicio): ?>
                  <option value="<?= (int) $servicio['id_servicio'] ?>">
                    #<?= (int) $servicio['id_servicio'] ?> · <?= htmlspecialchars($servicio['placa'] . ' · ' . $servicio['tipo_servicio'] . ' · ' . $servicio['nombre'] . ' ' . $servicio['apellido'] . ' · ' . ($servicio['estado'] ?? 'Sin estado')) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="categoria">Tipo de aviso</label>
              <select class="form-select" id="categoria" name="categoria" required>
                <option value="seguimiento">Seguimiento / novedad</option>
                <option value="entrega">Entrega del vehículo (solo finalizado)</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label" for="mensaje">Mensaje</label>
              <textarea class="form-control" id="mensaje" name="mensaje" rows="3" maxlength="4000" required></textarea>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-verde rounded-pill">Guardar notificación</button>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <div class="dashboard-panel">
        <?php if ($notificaciones): ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <?php if ($rol !== 'cliente'): ?><th>Cliente</th><?php endif; ?>
                  <th>Servicio</th><th>Mensaje</th><th>Fecha</th><th>Estado</th><th>Confirmación</th>
                  <?php if ($rol === 'cliente'): ?><th>Acción</th><?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($notificaciones as $notificacion): ?>
                  <tr>
                    <?php if ($rol !== 'cliente'): ?>
                      <td><?= htmlspecialchars(($notificacion['cliente'] ?? 'Cliente') . ' ' . ($notificacion['cliente_apellido'] ?? '')) ?></td>
                    <?php endif; ?>
                    <td>
                      <?= $notificacion['id_servicio'] === null ? 'Histórica sin servicio asociado' : '#' . (int) $notificacion['id_servicio'] . ' · ' . htmlspecialchars($notificacion['tipo_servicio'] ?? '') ?>
                    </td>
                    <td><?= nl2br(htmlspecialchars($notificacion['mensaje'])) ?></td>
                    <td><?= htmlspecialchars($notificacion['fecha_envio'] ?? 'Sin fecha') ?></td>
                    <td><?= htmlspecialchars($notificacion['estado'] ?? 'Sin estado') ?></td>
                    <td><?= htmlspecialchars($notificacion['fecha_confirmacion'] ?? 'Sin confirmar') ?></td>
                    <?php if ($rol === 'cliente'): ?>
                      <td>
                        <?php if ($notificacion['fecha_confirmacion'] === null): ?>
                          <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="confirm">
                            <input type="hidden" name="id_notificacion" value="<?= (int) $notificacion['id_notificacion'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-verde rounded-pill">Confirmar recepción</button>
                          </form>
                        <?php else: ?>
                          <span class="text-success">Confirmada</span>
                        <?php endif; ?>
                      </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="mb-0 text-muted">No hay notificaciones registradas.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>
