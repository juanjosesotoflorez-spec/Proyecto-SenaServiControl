<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'cliente') {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';
require_once dirname(__DIR__) . '/compartido/auditoria_helper.php';

$csrfToken = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$basePath = strpos($_SERVER['REQUEST_URI'] ?? '', '/codigos_php/') !== false ? '' : 'codigos_php/';
$homePath = $basePath === '' ? '../index.html' : 'index.html';
$logoutPath = 'logout.php';

$message = '';
$messageType = 'danger';

$stmt = $conn->prepare('SELECT c.id_cliente, c.nombre, c.apellido, c.documento, c.telefono, c.direccion, c.correo, u.usuario FROM cliente c JOIN usuario u ON c.id_usuario = u.id_usuario WHERE c.id_usuario = ?');
$stmt->bind_param('i', $_SESSION['id_usuario']);
$stmt->execute();
$result = $stmt->get_result();
$cliente = $result->num_rows === 1 ? $result->fetch_assoc() : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'editar_perfil') {
    $readPost = static function (string $key): string {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    };
    $nombre = $readPost('nombre');
    $apellido = $readPost('apellido');
    $documento = $readPost('documento');
    $telefono = $readPost('telefono');
    $direccion = $readPost('direccion');
    $correo = $readPost('correo');
    $usuario = $readPost('usuario');
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $message = 'La sesión venció. Recarga la página e inténtalo nuevamente.';
    } elseif (
        $nombre === '' || $apellido === '' || $documento === '' || $correo === '' || $usuario === '' ||
        preg_match('/^(?=.*\p{L})\P{N}+$/uD', $nombre) !== 1 ||
        preg_match('/^(?=.*\p{L})\P{N}+$/uD', $apellido) !== 1 ||
        mb_strlen($nombre) > 50 || mb_strlen($apellido) > 50 || mb_strlen($documento) > 20 ||
        mb_strlen($telefono) > 20 || mb_strlen($direccion) > 100 ||
        mb_strlen($correo) > 100 || mb_strlen($usuario) > 50 ||
        !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
        preg_match('/\.[A-Za-z]{2,}$/D', $correo) !== 1 ||
        preg_match('/^[0-9]{4,20}$/D', $documento) !== 1 ||
        ($telefono !== '' && preg_match('/^[0-9]{7,20}$/D', $telefono) !== 1)
    ) {
        $message = 'Verifica los campos obligatorios; nombre y apellido deben tener letras y no números.';
    } elseif (!$cliente) {
        $message = 'No se encontró el perfil asociado a esta cuenta.';
    } else {
        $idUsuario = (int) $_SESSION['id_usuario'];
      $transactionStarted = false;
        try {
        $conn->begin_transaction();
        $transactionStarted = true;
            $check = $conn->prepare(
                'SELECT
                    (SELECT COUNT(*) FROM usuario WHERE correo=? AND id_usuario<>?) AS correo_usado,
                    (SELECT COUNT(*) FROM usuario WHERE usuario=? AND id_usuario<>?) AS usuario_usado,
                    (SELECT COUNT(*) FROM cliente WHERE documento=? AND id_cliente<>?) AS documento_usado'
            );
            $check->bind_param(
                'sisisi',
                $correo,
                $idUsuario,
                $usuario,
                $idUsuario,
                $documento,
                $cliente['id_cliente']
            );
            $check->execute();
            $duplicates = $check->get_result()->fetch_assoc();

            if ((int) $duplicates['correo_usado'] > 0) {
                $message = 'Ese correo ya está en uso por otro usuario.';
            } elseif ((int) $duplicates['usuario_usado'] > 0) {
                $message = 'Ese nombre de usuario ya está en uso.';
            } elseif ((int) $duplicates['documento_usado'] > 0) {
                $message = 'Ese documento ya está registrado en otro perfil.';
            } else {
                $stmtUsuario = $conn->prepare('UPDATE usuario SET usuario=?,correo=? WHERE id_usuario=?');
                $stmtUsuario->bind_param('ssi', $usuario, $correo, $idUsuario);
                $stmtUsuario->execute();

                $stmtCliente = $conn->prepare(
                    'UPDATE cliente SET nombre=?,apellido=?,documento=?,telefono=NULLIF(?,\'\'),
                     direccion=NULLIF(?,\'\'),correo=? WHERE id_usuario=?'
                );
                $stmtCliente->bind_param(
                    'ssssssi',
                    $nombre,
                    $apellido,
                    $documento,
                    $telefono,
                    $direccion,
                    $correo,
                    $idUsuario
                );
                $stmtCliente->execute();
                $conn->commit();
                $transactionStarted = false;
                $_SESSION['correo'] = $correo;

                $auditOk = registrarActividadSistema(
                    $conn,
                    $idUsuario,
                    'perfil_cliente_actualizado',
                    'cliente',
                    (int) $cliente['id_cliente'],
                    null,
                    'El cliente actualizó su información de perfil.'
                );
                $message = $auditOk
                    ? 'Tu información se actualizó correctamente.'
                    : 'Tu información se guardó, pero no pudo registrarse en la auditoría.';
                $messageType = $auditOk ? 'success' : 'warning';
                $stmtRefresh = $conn->prepare(
                    'SELECT c.id_cliente,c.nombre,c.apellido,c.documento,c.telefono,c.direccion,c.correo,u.usuario
                     FROM cliente c JOIN usuario u ON c.id_usuario=u.id_usuario WHERE c.id_usuario=?'
                );
                $stmtRefresh->bind_param('i', $idUsuario);
                $stmtRefresh->execute();
                $cliente = $stmtRefresh->get_result()->fetch_assoc() ?: null;
            }
            if ($transactionStarted) {
                $conn->rollback();
              $transactionStarted = false;
            }
        } catch (mysqli_sql_exception $error) {
            if ($transactionStarted) {
                $conn->rollback();
            }
            error_log('Error al actualizar perfil de cliente: ' . $error->getMessage());
            $message = 'No se pudo actualizar la información. Verifica que correo, usuario y documento sean únicos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi perfil</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
  <script src="../js/form-validation.js?v=20261109-confirm" defer></script>
</head>
<body class="bg-light module-page module-profile">
  <div class="min-vh-100 d-flex flex-column">
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm border-bottom">
      <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="<?= $homePath ?>">Serviteca SENA</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuCliente" aria-controls="menuCliente" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuCliente">
          <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-2">
            <li class="nav-item"><a class="nav-link" href="../../index.html">Volver al panel</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= $basePath ?>cliente_vehiculos.php">Mis vehículos</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= $basePath ?>cliente_servicios.php">Mis servicios</a></li>
            <li class="nav-item"><a class="nav-link active" href="<?= $basePath ?>cliente_perfil.php">Mi perfil</a></li>
            <li class="nav-item"><a class="btn btn-light text-success ms-lg-2 rounded-pill" href="<?= $logoutPath ?>">Salir</a></li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="flex-grow-1 py-4">
      <div class="container py-3">
        <?php if ($message !== ''): ?>
          <div class="alert alert-<?= $messageType ?> alerta mb-4"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="row g-4">
          <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4">
              <div class="d-flex flex-column align-items-center text-center">
                <div class="profile-avatar rounded-circle d-flex align-items-center justify-content-center mb-3">
                  <?= htmlspecialchars(substr($cliente['nombre'] ?? 'C', 0, 1)) ?>
                </div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($cliente['nombre'] ?? 'Cliente') ?> <?= htmlspecialchars($cliente['apellido'] ?? '') ?></h4>
                <p class="text-muted mb-3">Información personal del cliente</p>
                <a href="../../index.html" class="btn btn-outline-verde rounded-pill">Volver al panel</a>
              </div>
            </div>
          </div>

          <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4">
              <h5 class="text-success mb-3">Editar información</h5>
              <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="editar_perfil">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="col-md-6">
                  <label class="form-label">Usuario</label>
                  <input type="text" name="usuario" class="form-control" value="<?= htmlspecialchars($cliente['usuario'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Nombre</label>
                  <input type="text" name="nombre" class="form-control" maxlength="50" pattern="(?=.*\p{L})\P{N}+" title="Escribe un nombre con letras y sin números." value="<?= htmlspecialchars($cliente['nombre'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Apellido</label>
                  <input type="text" name="apellido" class="form-control" maxlength="50" pattern="(?=.*\p{L})\P{N}+" title="Escribe un apellido con letras y sin números." value="<?= htmlspecialchars($cliente['apellido'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Documento</label>
                  <input type="text" name="documento" class="form-control" maxlength="20" minlength="4" pattern="[0-9]{4,20}" inputmode="numeric" data-numeric-only title="Ingresa entre 4 y 20 números." value="<?= htmlspecialchars($cliente['documento'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Teléfono</label>
                  <input type="tel" name="telefono" class="form-control" maxlength="20" pattern="[0-9]{7,20}" inputmode="numeric" data-numeric-only title="Ingresa entre 7 y 20 números." value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Correo</label>
                  <input type="email" name="correo" class="form-control" maxlength="100" pattern="[^@\s]+@[^@\s]+\.[A-Za-z]{2,}" title="Ingresa un correo con al menos dos letras después del punto." value="<?= htmlspecialchars($cliente['correo'] ?? '') ?>" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Dirección</label>
                  <textarea name="direccion" class="form-control" rows="3"><?= htmlspecialchars($cliente['direccion'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-verde rounded-pill">Guardar cambios</button>
                </div>
              </form>
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
