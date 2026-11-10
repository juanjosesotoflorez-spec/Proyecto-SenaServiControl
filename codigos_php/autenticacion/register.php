<?php
session_start();
require dirname(__DIR__, 2) . '/db.php';
require_once dirname(__DIR__) . '/compartido/auditoria_helper.php';

$csrfToken = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$message = '';
$messageType = 'danger';
$form = [
    'usuario' => '',
    'nombre' => '',
    'apellido' => '',
    'documento' => '',
    'telefono' => '',
    'direccion' => '',
    'correo' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $field => $value) {
        $input = $_POST[$field] ?? '';
        $form[$field] = is_string($input) ? trim($input) : '';
    }
    $usuario = $form['usuario'];
    $nombre = $form['nombre'];
    $apellido = $form['apellido'];
    $documento = $form['documento'];
    $telefono = $form['telefono'];
    $direccion = $form['direccion'];
    $correo = $form['correo'];
    $passwordInput = $_POST['contrasena'] ?? '';
    $confirmInput = $_POST['confirmar'] ?? '';
    $contrasena = is_string($passwordInput) ? $passwordInput : '';
    $confirmar = is_string($confirmInput) ? $confirmInput : '';
    $rol = 'cliente';
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $message = 'La sesión venció. Recarga la página e inténtalo nuevamente.';
    } elseif (
        $usuario === '' || $nombre === '' || $apellido === '' || $documento === '' ||
        $correo === '' || $contrasena === '' || $confirmar === ''
    ) {
        $message = 'Completa los campos obligatorios.';
    } elseif (
      preg_match('/^(?=.*\p{L})\P{N}+$/uD', $nombre) !== 1 ||
      preg_match('/^(?=.*\p{L})\P{N}+$/uD', $apellido) !== 1
    ) {
      $message = 'El nombre y el apellido deben contener letras y no números.';
    } elseif (mb_strlen($usuario) < 3 || mb_strlen($usuario) > 50) {
        $message = 'El nombre de usuario debe tener al menos 3 caracteres.';
    } elseif (
        mb_strlen($nombre) > 50 || mb_strlen($apellido) > 50 || mb_strlen($documento) > 20 ||
        mb_strlen($telefono) > 20 || mb_strlen($direccion) > 100
    ) {
        $message = 'Revisa la longitud de los datos personales ingresados.';
    } elseif (preg_match('/^[0-9]{4,20}$/D', $documento) !== 1) {
      $message = 'El documento debe contener entre 4 y 20 números.';
    } elseif ($telefono !== '' && preg_match('/^[0-9]{7,20}$/D', $telefono) !== 1) {
      $message = 'El teléfono debe contener entre 7 y 20 números.';
    } elseif (mb_strlen($correo) > 100 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $message = 'Ingrese un correo electrónico válido.';
    } elseif (preg_match('/\.[A-Za-z]{2,}$/D', $correo) !== 1) {
      $message = 'El correo debe terminar con al menos dos letras después del punto.';
    } elseif ($contrasena !== $confirmar) {
        $message = 'Las contraseñas no coinciden.';
    } elseif (strlen($contrasena) < 8) {
        $message = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        $conn->begin_transaction();
        try {
            $check = $conn->prepare(
                'SELECT
                    (SELECT COUNT(*) FROM usuario WHERE correo = ?) AS correo_usado,
                    (SELECT COUNT(*) FROM usuario WHERE usuario = ?) AS usuario_usado,
                    (SELECT COUNT(*) FROM cliente WHERE documento = ?) AS documento_usado'
            );
            $check->bind_param('sss', $correo, $usuario, $documento);
            $check->execute();
            $duplicates = $check->get_result()->fetch_assoc();

            if ((int) $duplicates['correo_usado'] > 0) {
                $message = 'Ese correo ya está registrado.';
            } elseif ((int) $duplicates['usuario_usado'] > 0) {
                $message = 'Ese nombre de usuario ya está en uso.';
            } elseif ((int) $duplicates['documento_usado'] > 0) {
                $message = 'Ese documento ya está registrado.';
            } else {
                $passwordHash = password_hash($contrasena, PASSWORD_DEFAULT);
                $insertUser = $conn->prepare(
                    'INSERT INTO usuario (usuario, correo, contrasena, rol, estado) VALUES (?, ?, ?, ?, "activo")'
                );
                $insertUser->bind_param('ssss', $usuario, $correo, $passwordHash, $rol);
                $insertUser->execute();
                $idUsuario = (int) $conn->insert_id;

                $insertClient = $conn->prepare(
                    'INSERT INTO cliente (nombre, apellido, documento, telefono, direccion, correo, id_usuario)
                     VALUES (?, ?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'), ?, ?)'
                );
                $insertClient->bind_param(
                    'ssssssi',
                    $nombre,
                    $apellido,
                    $documento,
                    $telefono,
                    $direccion,
                    $correo,
                    $idUsuario
                );
                $insertClient->execute();
                $idCliente = (int) $conn->insert_id;
                $conn->commit();

                registrarActividadSistema(
                    $conn,
                    $idUsuario,
                    'cuenta_cliente_creada',
                    'cliente',
                    $idCliente,
                    null,
                    'El cliente completó el registro de su cuenta.'
                );
                session_regenerate_id(true);
                $_SESSION['id_usuario'] = $idUsuario;
                $_SESSION['correo'] = $correo;
                $_SESSION['rol'] = $rol;
                header('Location: dashboard_cliente.php');
                exit;
            }
            $conn->rollback();
        } catch (mysqli_sql_exception $error) {
            $conn->rollback();
            error_log('Error al registrar una cuenta de cliente: ' . $error->getMessage());
            $message = 'No se pudo completar el registro. Verifica que el correo, usuario y documento no estén registrados.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css?v=20261022-register-fit">
  <script src="../js/form-validation.js?v=20261109-confirm" defer></script>
</head>
<body class="auth-page auth-register-page">
  <div class="auth-welcome">
    <span>Serviteca SENA</span>
    <h1>Bienvenido</h1>
    <p>Regístrate de manera sencilla para gestionar tus servicios y hacer seguimiento a tu vehículo.</p>
  </div>
  <div class="container py-5">
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-md-8 col-lg-6">
        <div class="auth-card p-4 p-md-5">
          <div class="auth-logo-card">
            <img src="../imagenes/sena_logo_transparente.png" alt="Logo SENA">
          </div>
          <h3 class="text-center texto-verde mb-3">Crear cuenta</h3>
          <?php if ($message !== ''): ?>
            <div class="alert alert-<?= $messageType ?> alerta"><?= htmlspecialchars($message) ?></div>
          <?php endif; ?>
          <form method="POST" class="row g-2">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="col-12 col-md-6">
              <label class="form-label">Nombre de usuario</label>
              <input type="text" name="usuario" class="form-control" minlength="3" maxlength="50" value="<?= htmlspecialchars($form['usuario']) ?>" autocomplete="username" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Documento</label>
              <input type="text" name="documento" class="form-control" maxlength="20" minlength="4" pattern="[0-9]{4,20}" inputmode="numeric" data-numeric-only title="Ingresa entre 4 y 20 números." value="<?= htmlspecialchars($form['documento']) ?>" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Nombre</label>
              <input type="text" name="nombre" class="form-control" maxlength="50" pattern="(?=.*\p{L})\P{N}+" title="Escribe un nombre con letras y sin números." value="<?= htmlspecialchars($form['nombre']) ?>" autocomplete="given-name" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Apellido</label>
              <input type="text" name="apellido" class="form-control" maxlength="50" pattern="(?=.*\p{L})\P{N}+" title="Escribe un apellido con letras y sin números." value="<?= htmlspecialchars($form['apellido']) ?>" autocomplete="family-name" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Teléfono</label>
              <input type="tel" name="telefono" class="form-control" maxlength="20" pattern="[0-9]{7,20}" inputmode="numeric" data-numeric-only title="Ingresa entre 7 y 20 números." value="<?= htmlspecialchars($form['telefono']) ?>" autocomplete="tel">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Dirección</label>
              <input type="text" name="direccion" class="form-control" maxlength="100" value="<?= htmlspecialchars($form['direccion']) ?>" autocomplete="street-address">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Correo</label>
              <input type="email" name="correo" class="form-control" maxlength="100" pattern="[^@\s]+@[^@\s]+\.[A-Za-z]{2,}" title="Ingresa un correo con al menos dos letras después del punto." value="<?= htmlspecialchars($form['correo']) ?>" autocomplete="email" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Contraseña</label>
              <input type="password" id="password1" name="contrasena" class="form-control" minlength="8" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Confirmar contraseña</label>
              <input type="password" id="password2" name="confirmar" class="form-control" minlength="8" data-match="#password1" data-validation-message="Las contraseñas deben coincidir." required>
            </div>
            <div class="col-12 form-check">
              <input class="form-check-input" type="checkbox" id="showPasswords">
              <label class="form-check-label" for="showPasswords">Mostrar contraseñas</label>
            </div>
            <div class="col-12 col-sm-6">
              <button type="submit" class="btn btn-verde w-100">Registrarse</button>
            </div>
            <div class="col-12 col-sm-6">
              <a href="../index.html" class="btn btn-outline-verde w-100">Volver al inicio</a>
            </div>
          </form>
          <p class="mt-3 text-center mb-0">
            ¿Ya tienes cuenta? <a href="../laravel/public/login">Inicia sesión</a>
          </p>
        </div>
      </div>
    </div>
  </div>
  <script>
    document.getElementById('showPasswords')?.addEventListener('change', function () {
      const type = this.checked ? 'text' : 'password';
      document.getElementById('password1').type = type;
      document.getElementById('password2').type = type;
    });
  </script>
</body>
</html>
