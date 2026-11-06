<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}
require '../db.php';
require_once __DIR__ . '/auditoria_helper.php';

$csrfToken = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$message = '';
$messageType = 'danger';
$form = [
    'usuario' => '',
    'correo' => '',
    'rol' => 'cliente',
    'nombre' => '',
    'apellido' => '',
    'documento' => '',
    'telefono' => '',
    'direccion' => '',
    'especialidad' => '',
    'experiencia' => '',
    'turno' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'crear') {
        foreach ($form as $field => $value) {
            $input = $_POST[$field] ?? '';
            $form[$field] = is_string($input) ? trim($input) : '';
        }
        $usuario = $form['usuario'];
        $correo = $form['correo'];
        $passwordInput = $_POST['contrasena'] ?? '';
        $contrasena = is_string($passwordInput) ? $passwordInput : '';
        $rol = $form['rol'];
        $submittedToken = $_POST['csrf_token'] ?? '';
        $perfilesPorRol = [
            'administrador' => 'administrador',
            'recepcionista' => 'recepcionista',
            'tecnico' => 'tecnico',
            'cliente' => 'cliente'
        ];

        if (
            !is_string($submittedToken) ||
            !isset($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $submittedToken)
        ) {
            $message = 'La sesión venció. Recarga la página e inténtalo nuevamente.';
        } elseif (
            $usuario === '' || mb_strlen($usuario) < 3 || mb_strlen($usuario) > 50 ||
            $correo === '' || mb_strlen($correo) > 100 || !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
            $contrasena === '' || strlen($contrasena) < 8 ||
            !isset($perfilesPorRol[$rol]) ||
            $form['nombre'] === '' || $form['apellido'] === '' || $form['documento'] === '' ||
            mb_strlen($form['nombre']) > 50 || mb_strlen($form['apellido']) > 50 ||
            mb_strlen($form['documento']) > 20 || mb_strlen($form['telefono']) > 20 ||
            mb_strlen($form['direccion']) > 100 || mb_strlen($form['especialidad']) > 100 ||
            mb_strlen($form['experiencia']) > 100 || mb_strlen($form['turno']) > 30 ||
            ($form['telefono'] !== '' && preg_match('/^[0-9+\s().-]+$/', $form['telefono']) !== 1)
        ) {
            $message = 'Completa los datos personales y verifica los formatos y longitudes permitidos.';
        } else {
            $tablaPerfil = $perfilesPorRol[$rol];
            $idPerfil = 'id_' . $tablaPerfil;
            $conn->begin_transaction();
            try {
                $checkUser = $conn->prepare(
                    'SELECT
                        (SELECT COUNT(*) FROM usuario WHERE correo = ?) AS correo_usado,
                        (SELECT COUNT(*) FROM usuario WHERE usuario = ?) AS usuario_usado'
                );
                $checkUser->bind_param('ss', $correo, $usuario);
                $checkUser->execute();
                $duplicates = $checkUser->get_result()->fetch_assoc();

                if ((int) $duplicates['correo_usado'] > 0) {
                    $message = 'Ese correo ya está registrado.';
                } elseif ((int) $duplicates['usuario_usado'] > 0) {
                    $message = 'Ese nombre de usuario ya está en uso.';
                } else {
                    $checkProfile = $conn->prepare("SELECT `$idPerfil` FROM `$tablaPerfil` WHERE documento = ? LIMIT 1");
                    $checkProfile->bind_param('s', $form['documento']);
                    $checkProfile->execute();
                    $checkProfile->store_result();

                    if ($checkProfile->num_rows > 0) {
                        $message = 'Ese documento ya está registrado para el rol seleccionado.';
                    } else {
                        $passwordHash = password_hash($contrasena, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare(
                            'INSERT INTO usuario (usuario, correo, contrasena, rol, estado)
                             VALUES (?, ?, ?, ?, "activo")'
                        );
                        $stmt->bind_param('ssss', $usuario, $correo, $passwordHash, $rol);
                        $stmt->execute();
                        $idUsuario = (int) $conn->insert_id;

                        if ($rol === 'cliente') {
                            $profile = $conn->prepare(
                                'INSERT INTO cliente (nombre,apellido,documento,telefono,direccion,correo,id_usuario)
                                 VALUES (?,?,?,NULLIF(?,\'\'),NULLIF(?,\'\'),?,?)'
                            );
                            $profile->bind_param(
                                'ssssssi',
                                $form['nombre'],
                                $form['apellido'],
                                $form['documento'],
                                $form['telefono'],
                                $form['direccion'],
                                $correo,
                                $idUsuario
                            );
                        } elseif ($rol === 'tecnico') {
                            $profile = $conn->prepare(
                                'INSERT INTO tecnico (nombre,apellido,documento,telefono,especialidad,experiencia,id_usuario)
                                 VALUES (?,?,?,NULLIF(?,\'\'),NULLIF(?,\'\'),NULLIF(?,\'\'),?)'
                            );
                            $profile->bind_param(
                                'ssssssi',
                                $form['nombre'],
                                $form['apellido'],
                                $form['documento'],
                                $form['telefono'],
                                $form['especialidad'],
                                $form['experiencia'],
                                $idUsuario
                            );
                        } elseif ($rol === 'recepcionista') {
                            $profile = $conn->prepare(
                                'INSERT INTO recepcionista (nombre,apellido,documento,telefono,turno,id_usuario)
                                 VALUES (?,?,?,NULLIF(?,\'\'),NULLIF(?,\'\'),?)'
                            );
                            $profile->bind_param(
                                'sssssi',
                                $form['nombre'],
                                $form['apellido'],
                                $form['documento'],
                                $form['telefono'],
                                $form['turno'],
                                $idUsuario
                            );
                        } else {
                            $profile = $conn->prepare(
                                'INSERT INTO administrador (nombre,apellido,documento,telefono,id_usuario)
                                 VALUES (?,?,?,NULLIF(?,\'\'),?)'
                            );
                            $profile->bind_param(
                                'ssssi',
                                $form['nombre'],
                                $form['apellido'],
                                $form['documento'],
                                $form['telefono'],
                                $idUsuario
                            );
                        }

                        $profile->execute();
                        $idRegistro = (int) $conn->insert_id;
                        $conn->commit();
                        $auditOk = registrarActividadSistema(
                            $conn,
                            (int) $_SESSION['id_usuario'],
                            'usuario_creado',
                            $tablaPerfil,
                            $idRegistro,
                            null,
                            'Administración creó un usuario del rol ' . $rol . '.'
                        );
                        $message = $auditOk
                            ? 'Usuario creado correctamente.'
                            : 'El usuario se creó, pero no pudo registrarse en la auditoría.';
                        $messageType = $auditOk ? 'success' : 'warning';
                    }
                }
                if ($conn->in_transaction) {
                    $conn->rollback();
                }
            } catch (mysqli_sql_exception $error) {
                if ($conn->in_transaction) {
                    $conn->rollback();
                }
                error_log('Error al crear usuario y perfil: ' . $error->getMessage());
                $message = 'No se pudo crear el usuario y su perfil. Verifica que correo, usuario y documento sean únicos.';
                $messageType = 'danger';
            }
        }
    }
}

$usuarios = [];
if ($result = $conn->query('SELECT id_usuario, usuario, correo, rol, estado FROM usuario ORDER BY id_usuario DESC')) {
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Usuarios</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
</head>
<body class="admin-body module-page module-users">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4>Administración</h4></div>
      <nav class="sidebar-nav">
        <a href="dashboard_admin.php">Volver al panel</a>
        <a href="admin_usuarios.php" class="active">Usuarios</a>
        <a href="admin_vehiculos.php">Vehículos</a>
        <a href="admin_servicios.php">Servicios</a>
        <a href="admin_repuestos.php">Repuestos</a>
        <a href="logout.php">Cerrar sesión</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h2 class="section-title">Gestión de usuarios</h2>
      <?php if ($message !== ''): ?>
        <div class="alert alert-<?= $messageType ?> alerta"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>
      <div class="dashboard-panel mb-4">
        <h5>Crear usuario</h5>
        <form method="POST">
          <input type="hidden" name="accion" value="crear">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <div class="row g-2">
            <div class="col-md-3"><input class="form-control" type="text" name="usuario" maxlength="50" value="<?= htmlspecialchars($form['usuario']) ?>" placeholder="Usuario" required></div>
            <div class="col-md-3"><input class="form-control" type="email" name="correo" maxlength="100" value="<?= htmlspecialchars($form['correo']) ?>" placeholder="Correo" required></div>
            <div class="col-md-3"><input class="form-control" type="password" name="contrasena" minlength="8" placeholder="Contraseña" required></div>
            <div class="col-md-2">
              <select class="form-select" name="rol">
                <option value="administrador" <?= $form['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                <option value="recepcionista" <?= $form['rol'] === 'recepcionista' ? 'selected' : '' ?>>Recepcionista</option>
                <option value="tecnico" <?= $form['rol'] === 'tecnico' ? 'selected' : '' ?>>Técnico</option>
                <option value="cliente" <?= $form['rol'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
              </select>
            </div>
            <div class="col-md-1"><button class="btn btn-verde w-100">Crear</button></div>
          </div>
          <div class="row g-2 mt-1">
            <div class="col-md-3"><input class="form-control" type="text" name="nombre" maxlength="50" value="<?= htmlspecialchars($form['nombre']) ?>" placeholder="Nombre" required></div>
            <div class="col-md-3"><input class="form-control" type="text" name="apellido" maxlength="50" value="<?= htmlspecialchars($form['apellido']) ?>" placeholder="Apellido" required></div>
            <div class="col-md-3"><input class="form-control" type="text" name="documento" maxlength="20" value="<?= htmlspecialchars($form['documento']) ?>" placeholder="Documento" required></div>
            <div class="col-md-3"><input class="form-control" type="tel" name="telefono" maxlength="20" value="<?= htmlspecialchars($form['telefono']) ?>" placeholder="Teléfono"></div>
            <div class="col-md-4"><input class="form-control" type="text" name="direccion" maxlength="100" value="<?= htmlspecialchars($form['direccion']) ?>" placeholder="Dirección (cliente)"></div>
            <div class="col-md-4"><input class="form-control" type="text" name="especialidad" maxlength="100" value="<?= htmlspecialchars($form['especialidad']) ?>" placeholder="Especialidad (técnico)"></div>
            <div class="col-md-2"><input class="form-control" type="text" name="experiencia" maxlength="100" value="<?= htmlspecialchars($form['experiencia']) ?>" placeholder="Experiencia"></div>
            <div class="col-md-2"><input class="form-control" type="text" name="turno" maxlength="30" value="<?= htmlspecialchars($form['turno']) ?>" placeholder="Turno (recepción)"></div>
          </div>
        </form>
      </div>
      <div class="dashboard-panel">
        <h5>Usuarios registrados</h5>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>ID</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
              <tr>
                <td><?= $u['id_usuario'] ?></td>
                <td><?= htmlspecialchars($u['usuario']) ?></td>
                <td><?= htmlspecialchars($u['correo']) ?></td>
                <td><?= htmlspecialchars($u['rol']) ?></td>
                <td><?= htmlspecialchars($u['estado']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
