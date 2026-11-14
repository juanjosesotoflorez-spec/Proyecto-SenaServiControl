<?php
session_start();
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol'] ?? '') !== 'recepcionista') {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';
require_once dirname(__DIR__) . '/compartido/auditoria_helper.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = 'danger';
$cliente = [
    'nombre' => '',
    'apellido' => '',
    'documento' => '',
    'telefono' => '',
    'direccion' => '',
    'correo' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($cliente as $campo => $valor) {
        $cliente[$campo] = trim($_POST[$campo] ?? '');
    }

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message = 'La sesión venció. Recarga la página e inténtalo nuevamente.';
    } elseif (
        $cliente['nombre'] === '' ||
        $cliente['apellido'] === '' ||
        preg_match('/^(?=.*\p{L})\P{N}+$/uD', $cliente['nombre']) !== 1 ||
        preg_match('/^(?=.*\p{L})\P{N}+$/uD', $cliente['apellido']) !== 1 ||
        $cliente['documento'] === '' ||
        mb_strlen($cliente['nombre']) > 50 ||
        mb_strlen($cliente['apellido']) > 50 ||
        mb_strlen($cliente['documento']) > 20 ||
        preg_match('/^[0-9]{4,20}$/D', $cliente['documento']) !== 1 ||
        mb_strlen($cliente['telefono']) > 20 ||
        ($cliente['telefono'] !== '' && preg_match('/^[0-9]{7,20}$/D', $cliente['telefono']) !== 1) ||
        mb_strlen($cliente['direccion']) > 100 ||
        mb_strlen($cliente['correo']) > 100 ||
        ($cliente['correo'] !== '' && (
          !filter_var($cliente['correo'], FILTER_VALIDATE_EMAIL) ||
          preg_match('/\.[A-Za-z]{2,}$/D', $cliente['correo']) !== 1
        ))
    ) {
        $message = 'Verifica los campos obligatorios; nombre y apellido deben tener letras y no números.';
    } else {
        $stmt = $conn->prepare('SELECT id_cliente FROM cliente WHERE documento = ? LIMIT 1');
        $stmt->bind_param('s', $cliente['documento']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = 'Ya existe un cliente con ese número de documento.';
        } else {
            $telefono = $cliente['telefono'] !== '' ? $cliente['telefono'] : null;
            $direccion = $cliente['direccion'] !== '' ? $cliente['direccion'] : null;
            $correo = $cliente['correo'] !== '' ? $cliente['correo'] : null;
            $stmt = $conn->prepare(
                'INSERT INTO cliente (nombre, apellido, documento, telefono, direccion, correo, id_usuario)
                 VALUES (?, ?, ?, ?, ?, ?, NULL)'
            );
            $stmt->bind_param(
                'ssssss',
                $cliente['nombre'],
                $cliente['apellido'],
                $cliente['documento'],
                $telefono,
                $direccion,
                $correo
            );

            try {
                $saved = $stmt->execute();
            } catch (mysqli_sql_exception $error) {
                if ((int)$error->getCode() === 1062) {
                    $message = 'Ya existe un cliente con ese número de documento.';
                } else {
                    error_log('Error al registrar cliente desde recepción: ' . $error->getMessage());
                    $message = 'No se pudo guardar el cliente. Inténtalo nuevamente.';
                }
                $saved = false;
            }

            if ($saved) {
                $idCliente = (int)$stmt->insert_id;
                $auditOk = registrarActividadSistema(
                    $conn,
                    (int)$_SESSION['id_usuario'],
                    'cliente_creado',
                    'cliente',
                    $idCliente,
                    null,
                    'Recepción registró un cliente nuevo.'
                );
                $message = $auditOk
                    ? 'Cliente registrado correctamente.'
                    : 'El cliente se guardó, pero no pudo registrarse la auditoría.';
                $messageType = $auditOk ? 'success' : 'warning';
                $cliente = ['nombre' => '', 'apellido' => '', 'documento' => '', 'telefono' => '', 'direccion' => '', 'correo' => ''];
            } else {
                $message = 'No se pudo guardar el cliente. Inténtalo nuevamente.';
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
  <title>Registrar cliente - Recepción</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css?v=20261113-brand-green">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261113-brand-green">
  <script src="../js/form-validation.js?v=20261109-confirm" defer></script>
</head>
<body class="bg-light module-page module-client-registration">
  <div class="min-vh-100 d-flex flex-column">
    <nav class="navbar navbar-light bg-white shadow-sm border-bottom">
      <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold texto-verde" href="dashboard_recepcionista.php">Recepción SENA</a>
        <a class="btn btn-outline-verde rounded-pill" href="../../index.html">Volver al panel</a>
      </div>
    </nav>
    <main class="flex-grow-1 py-4">
      <div class="container py-3">
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <header class="mb-4">
              <p class="text-muted mb-1">Recepción · Clientes</p>
              <h1 class="section-title">Registrar cliente</h1>
              <p class="text-muted">Ingresa la información de contacto para crear el registro que luego podrás asociar a sus vehículos.</p>
            </header>
            <?php if ($message !== ''): ?>
              <div class="alert alert-<?= htmlspecialchars($messageType) ?> alerta" role="status"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <section class="card border-0 shadow-sm rounded-4">
              <div class="card-body p-4 p-lg-5">
                <form method="POST" class="row g-3">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                  <div class="col-md-6">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input class="form-control" id="nombre" name="nombre" maxlength="50" pattern="(?=.*\p{L})\P{N}+" title="Escribe un nombre con letras y sin números." value="<?= htmlspecialchars($cliente['nombre']) ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="apellido">Apellido</label>
                    <input class="form-control" id="apellido" name="apellido" maxlength="50" pattern="(?=.*\p{L})\P{N}+" title="Escribe un apellido con letras y sin números." value="<?= htmlspecialchars($cliente['apellido']) ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="documento">Documento</label>
                    <input class="form-control" id="documento" name="documento" maxlength="20" minlength="4" pattern="[0-9]{4,20}" inputmode="numeric" data-numeric-only title="Ingresa entre 4 y 20 números." value="<?= htmlspecialchars($cliente['documento']) ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="telefono">Teléfono</label>
                    <input class="form-control" id="telefono" name="telefono" maxlength="20" pattern="[0-9]{7,20}" inputmode="numeric" data-numeric-only title="Ingresa entre 7 y 20 números." value="<?= htmlspecialchars($cliente['telefono']) ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="correo">Correo</label>
                    <input type="email" class="form-control" id="correo" name="correo" maxlength="100" pattern="[^@\s]+@[^@\s]+\.[A-Za-z]{2,}" title="Ingresa un correo con al menos dos letras después del punto." value="<?= htmlspecialchars($cliente['correo']) ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="direccion">Dirección</label>
                    <input class="form-control" id="direccion" name="direccion" maxlength="100" value="<?= htmlspecialchars($cliente['direccion']) ?>">
                  </div>
                  <div class="col-12 d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-verde rounded-pill">Guardar cliente</button>
                    <a href="recepcionista_clientes.php" class="btn btn-outline-verde rounded-pill">Consultar clientes</a>
                    <a href="registro_recepcionista.php" class="btn btn-outline-secondary rounded-pill">Registrar vehículo</a>
                  </div>
                </form>
              </div>
            </section>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
