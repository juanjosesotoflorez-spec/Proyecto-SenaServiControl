<?php
session_start();
require dirname(__DIR__, 2) . '/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correoInput = $_POST['correo'] ?? '';
    $passwordInput = $_POST['contrasena'] ?? '';
    $correo = is_string($correoInput) ? trim($correoInput) : '';
    $contrasena = is_string($passwordInput) ? $passwordInput : '';

    if ($correo !== '' && $contrasena !== '') {
        $stmt = $conn->prepare('SELECT id_usuario, correo, contrasena, rol, estado FROM usuario WHERE correo = ?');
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();
            $storedPassword = (string) $usuario['contrasena'];
            $isHashedPassword = password_get_info($storedPassword)['algo'] !== null;
            $passwordIsValid = $isHashedPassword
                ? password_verify($contrasena, $storedPassword)
                : hash_equals($storedPassword, $contrasena);

            $rolesPermitidos = ['administrador', 'recepcionista', 'tecnico', 'cliente'];
            if (
                $passwordIsValid &&
                $usuario['estado'] === 'activo' &&
                in_array($usuario['rol'], $rolesPermitidos, true)
            ) {
                if (!$isHashedPassword || password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                    $passwordHash = password_hash($contrasena, PASSWORD_DEFAULT);
                    $updatePassword = $conn->prepare('UPDATE usuario SET contrasena = ? WHERE id_usuario = ?');
                    $updatePassword->bind_param('si', $passwordHash, $usuario['id_usuario']);
                    $updatePassword->execute();
                }

                session_regenerate_id(true);
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['correo'] = $usuario['correo'];
                $_SESSION['rol'] = $usuario['rol'];

                switch ($usuario['rol']) {
                    case 'administrador':
                        header('Location: dashboard_admin.php');
                        break;
                    case 'recepcionista':
                        header('Location: dashboard_recepcionista.php');
                        break;
                    case 'tecnico':
                        header('Location: dashboard_tecnico.php');
                        break;
                    case 'cliente':
                        header('Location: dashboard_cliente.php');
                        break;
                    default:
                        $message = 'La cuenta no tiene un rol válido. Contacta al administrador.';
                        break;
                }
                if ($message === '') {
                    exit;
                }
            } else {
                $message = 'Credenciales incorrectas o cuenta inactiva.';
            }
        } else {
            $message = 'Credenciales incorrectas o cuenta inactiva.';
        }
    } else {
        $message = 'Todos los campos son obligatorios.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <script src="../js/form-validation.js?v=20261109-confirm" defer></script>
</head>
<body class="auth-page">
  <div class="auth-welcome">
    <span>Serviteca SENA</span>
    <h1>Bienvenido</h1>
    <p>Inicia sesión de manera sencilla para consultar tus servicios y el estado de tu vehículo.</p>
  </div>
  <div class="container py-5">
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-md-6 col-lg-5">
        <div class="auth-card p-4 p-md-5">
          <div class="auth-logo-card">
            <img src="../imagenes/sena_logo_transparente.png" alt="Logo SENA">
          </div>
          <h3 class="text-center texto-verde mb-3">Iniciar sesión</h3>
          <?php if ($message !== ''): ?>
            <div class="alert alert-danger alerta"><?= htmlspecialchars($message) ?></div>
          <?php endif; ?>
          <form method="POST" data-skip-confirmation="true">
            <div class="mb-3">
              <label class="form-label">Correo</label>
              <input type="email" name="correo" class="form-control" maxlength="100" pattern="[^@\s]+@[^@\s]+\.[A-Za-z]{2,}" title="Ingresa un correo con al menos dos letras después del punto." required>
            </div>
            <div class="mb-3">
              <label class="form-label">Contraseña</label>
              <input type="password" id="passwordLogin" name="contrasena" class="form-control" required>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="showPasswordLogin">
              <label class="form-check-label" for="showPasswordLogin">Mostrar contraseña</label>
            </div>
            <button type="submit" class="btn btn-verde w-100 mb-3">Entrar</button>
            <a href="../index.html" class="btn btn-outline-verde w-100">Volver al inicio</a>
          </form>
          <p class="mt-3 text-center mb-0">
            ¿No tienes cuenta? <a href="register.php">Regístrate</a>
          </p>
        </div>
      </div>
    </div>
  </div>
  <script>
    document.getElementById('showPasswordLogin')?.addEventListener('change', function () {
      const input = document.getElementById('passwordLogin');
      if (input) {
        input.type = this.checked ? 'text' : 'password';
      }
    });
  </script>
</body>
</html>
