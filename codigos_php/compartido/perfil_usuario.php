<?php
session_start();
$rol = $_SESSION['rol'] ?? '';
$perfiles = [
    'administrador' => [
        'tabla' => 'administrador',
        'id' => 'id_administrador',
        'panel' => 'dashboard_admin.php',
        'etiqueta' => 'Administrador'
    ],
    'recepcionista' => [
        'tabla' => 'recepcionista',
        'id' => 'id_recepcionista',
        'panel' => 'dashboard_recepcionista.php',
        'etiqueta' => 'Recepción'
    ],
    'tecnico' => [
        'tabla' => 'tecnico',
        'id' => 'id_tecnico',
        'panel' => 'dashboard_tecnico.php',
        'etiqueta' => 'Técnico'
    ],
    'cliente' => [
        'tabla' => 'cliente',
        'id' => 'id_cliente',
        'panel' => 'dashboard_cliente.php',
        'etiqueta' => 'Cliente'
    ]
];

if (!isset($_SESSION['id_usuario']) || !isset($perfiles[$rol])) {
    header('Location: login.php');
    exit;
}
if ($rol === 'cliente') {
    header('Location: cliente_perfil.php', true, 303);
    exit;
}

require dirname(__DIR__, 2) . '/db.php';
require_once dirname(__DIR__) . '/compartido/auditoria_helper.php';

$perfilConfig = $perfiles[$rol];
$tabla = $perfilConfig['tabla'];
$idColumna = $perfilConfig['id'];
$idUsuario = (int) $_SESSION['id_usuario'];
$csrfToken = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$message = '';
$messageType = 'danger';

$consultaPerfil = static function () use ($conn, $tabla, $idColumna, $idUsuario): ?array {
    $stmt = $conn->prepare(
        "SELECT p.`$idColumna` AS id_perfil, p.nombre, p.apellido, p.documento, p.telefono,
                u.usuario, u.correo"
        . ($tabla === 'cliente' ? ', p.direccion' : '')
        . ($tabla === 'tecnico' ? ', p.especialidad, p.experiencia' : '')
        . ($tabla === 'recepcionista' ? ', p.turno' : '')
        . " FROM `$tabla` p JOIN usuario u ON u.id_usuario = p.id_usuario
            WHERE u.id_usuario = ? LIMIT 1"
    );
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $perfil = $stmt->get_result()->fetch_assoc();
    return $perfil ?: null;
};

$perfil = $consultaPerfil();
if (!$perfil) {
    http_response_code(404);
    exit('No se encontró el perfil asociado a esta cuenta. Contacta al administrador.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $readPost = static function (string $key): string {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    };
    $nombre = $readPost('nombre');
    $apellido = $readPost('apellido');
    $documento = $readPost('documento');
    $telefono = $readPost('telefono');
    $usuario = $readPost('usuario');
    $correo = $readPost('correo');
    $direccion = $readPost('direccion');
    $especialidad = $readPost('especialidad');
    $experiencia = $readPost('experiencia');
    $turno = $readPost('turno');
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $message = 'La sesión venció. Recarga la página e inténtalo nuevamente.';
    } elseif (
        $nombre === '' || $apellido === '' || $documento === '' || $correo === '' ||
        preg_match('/^(?=.*\p{L})\P{N}+$/uD', $nombre) !== 1 ||
        preg_match('/^(?=.*\p{L})\P{N}+$/uD', $apellido) !== 1 ||
        mb_strlen($usuario) < 3 || $usuario === '' ||
        mb_strlen($nombre) > 50 || mb_strlen($apellido) > 50 || mb_strlen($documento) > 20 ||
        mb_strlen($telefono) > 20 || mb_strlen($usuario) > 50 || mb_strlen($correo) > 100 ||
        mb_strlen($direccion) > 100 || mb_strlen($especialidad) > 100 ||
        mb_strlen($experiencia) > 100 || mb_strlen($turno) > 30 ||
        !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
        preg_match('/\.[A-Za-z]{2,}$/D', $correo) !== 1 ||
        preg_match('/^[0-9]{4,20}$/D', $documento) !== 1 ||
        ($telefono !== '' && preg_match('/^[0-9]{7,20}$/D', $telefono) !== 1) ||
        ($rol === 'cliente' && mb_strlen($direccion) > 100)
    ) {
        $message = 'Revisa los campos obligatorios; nombre y apellido deben tener letras y no números.';
    } else {
        $conn->begin_transaction();
      $transactionStarted = true;
        try {
            $checkUser = $conn->prepare(
                'SELECT
                    (SELECT COUNT(*) FROM usuario WHERE usuario = ? AND id_usuario <> ?) AS usuario_usado,
                    (SELECT COUNT(*) FROM usuario WHERE correo = ? AND id_usuario <> ?) AS correo_usado'
            );
            $checkUser->bind_param('sisi', $usuario, $idUsuario, $correo, $idUsuario);
            $checkUser->execute();
            $duplicates = $checkUser->get_result()->fetch_assoc();

            $checkDocument = $conn->prepare(
                "SELECT `$idColumna` FROM `$tabla`
                 WHERE documento = ? AND `$idColumna` <> ? LIMIT 1"
            );
            $checkDocument->bind_param('si', $documento, $perfil['id_perfil']);
            $checkDocument->execute();
            $checkDocument->store_result();

            if ((int) $duplicates['usuario_usado'] > 0) {
                $message = 'Ese nombre de usuario ya está en uso.';
            } elseif ((int) $duplicates['correo_usado'] > 0) {
                $message = 'Ese correo ya está en uso por otra cuenta.';
            } elseif ($checkDocument->num_rows > 0) {
                $message = 'Ese documento ya está registrado en otro perfil.';
            } else {
                $updateUser = $conn->prepare('UPDATE usuario SET usuario = ?, correo = ? WHERE id_usuario = ?');
                $updateUser->bind_param('ssi', $usuario, $correo, $idUsuario);
                $updateUser->execute();

                if ($rol === 'cliente') {
                    $updateProfile = $conn->prepare(
                        'UPDATE cliente SET nombre=?,apellido=?,documento=?,telefono=NULLIF(?,\'\'),
                         direccion=NULLIF(?,\'\'),correo=? WHERE id_usuario=?'
                    );
                    $updateProfile->bind_param(
                        'ssssssi',
                        $nombre,
                        $apellido,
                        $documento,
                        $telefono,
                        $direccion,
                        $correo,
                        $idUsuario
                    );
                } elseif ($rol === 'tecnico') {
                    $updateProfile = $conn->prepare(
                        'UPDATE tecnico SET nombre=?,apellido=?,documento=?,telefono=NULLIF(?,\'\'),
                         especialidad=NULLIF(?,\'\'),experiencia=NULLIF(?,\'\') WHERE id_usuario=?'
                    );
                    $updateProfile->bind_param(
                        'ssssssi',
                        $nombre,
                        $apellido,
                        $documento,
                        $telefono,
                        $especialidad,
                        $experiencia,
                        $idUsuario
                    );
                } elseif ($rol === 'recepcionista') {
                    $updateProfile = $conn->prepare(
                        'UPDATE recepcionista SET nombre=?,apellido=?,documento=?,telefono=NULLIF(?,\'\'),
                         turno=NULLIF(?,\'\') WHERE id_usuario=?'
                    );
                    $updateProfile->bind_param(
                        'sssssi',
                        $nombre,
                        $apellido,
                        $documento,
                        $telefono,
                        $turno,
                        $idUsuario
                    );
                } else {
                    $updateProfile = $conn->prepare(
                        'UPDATE administrador SET nombre=?,apellido=?,documento=?,telefono=NULLIF(?,\'\')
                         WHERE id_usuario=?'
                    );
                    $updateProfile->bind_param('ssssi', $nombre, $apellido, $documento, $telefono, $idUsuario);
                }

                $updateProfile->execute();
                $conn->commit();
                $transactionStarted = false;
                $_SESSION['correo'] = $correo;
                $perfil = $consultaPerfil();

                $auditOk = registrarActividadSistema(
                    $conn,
                    $idUsuario,
                    'perfil_actualizado',
                    $tabla,
                    (int) $perfil['id_perfil'],
                    null,
                    'El usuario actualizó su información de perfil.'
                );
                $message = $auditOk
                    ? 'Tu información se actualizó correctamente.'
                    : 'Tu información se guardó, pero no pudo registrarse en la auditoría.';
                $messageType = $auditOk ? 'success' : 'warning';
            }

            if ($transactionStarted) {
                $conn->rollback();
              $transactionStarted = false;
            }
        } catch (mysqli_sql_exception $error) {
            if ($transactionStarted) {
                $conn->rollback();
              $transactionStarted = false;
            }
            error_log('Error al actualizar perfil de usuario: ' . $error->getMessage());
            $message = 'No se pudo actualizar el perfil. Verifica que correo, usuario y documento sean únicos.';
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
  <link rel="stylesheet" href="../css/style.css?v=20261113-brand-green">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261113-brand-green">
  <script src="../js/form-validation.js?v=20261109-confirm" defer></script>
</head>
<body class="admin-body module-page module-profile">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="sidebar-brand"><h4><?= htmlspecialchars($perfilConfig['etiqueta']) ?></h4></div>
      <nav class="sidebar-nav">
        <a href="../../index.html">Volver al panel</a>
        <a href="perfil_usuario.php" class="active">Mi perfil</a>
        <a href="logout.php">Cerrar sesión</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h1 class="section-title">Mi perfil</h1>
      <p class="text-muted">Consulta y actualiza los datos personales de tu cuenta.</p>
      <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?> alerta" role="status"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>
      <section class="dashboard-panel">
        <form method="POST" class="row g-3">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <div class="col-md-6">
            <label class="form-label" for="usuario">Usuario</label>
            <input class="form-control" id="usuario" name="usuario" maxlength="50" value="<?= htmlspecialchars($perfil['usuario']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="correo">Correo</label>
            <input class="form-control" id="correo" name="correo" type="email" maxlength="100" pattern="[^@\s]+@[^@\s]+\.[A-Za-z]{2,}" title="Ingresa un correo con al menos dos letras después del punto." value="<?= htmlspecialchars($perfil['correo']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="nombre">Nombre</label>
            <input class="form-control" id="nombre" name="nombre" maxlength="50" pattern="(?=.*\p{L})\P{N}+" title="Escribe un nombre con letras y sin números." value="<?= htmlspecialchars($perfil['nombre']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="apellido">Apellido</label>
            <input class="form-control" id="apellido" name="apellido" maxlength="50" pattern="(?=.*\p{L})\P{N}+" title="Escribe un apellido con letras y sin números." value="<?= htmlspecialchars($perfil['apellido']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="documento">Documento</label>
            <input class="form-control" id="documento" name="documento" maxlength="20" minlength="4" pattern="[0-9]{4,20}" inputmode="numeric" data-numeric-only title="Ingresa entre 4 y 20 números." value="<?= htmlspecialchars($perfil['documento']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="telefono">Teléfono</label>
            <input class="form-control" id="telefono" name="telefono" type="tel" maxlength="20" pattern="[0-9]{7,20}" inputmode="numeric" data-numeric-only title="Ingresa entre 7 y 20 números." value="<?= htmlspecialchars($perfil['telefono'] ?? '') ?>">
          </div>
          <?php if ($rol === 'cliente'): ?>
            <div class="col-12">
              <label class="form-label" for="direccion">Dirección</label>
              <textarea class="form-control" id="direccion" name="direccion" maxlength="100" rows="3"><?= htmlspecialchars($perfil['direccion'] ?? '') ?></textarea>
            </div>
          <?php elseif ($rol === 'tecnico'): ?>
            <div class="col-md-6">
              <label class="form-label" for="especialidad">Especialidad</label>
              <input class="form-control" id="especialidad" name="especialidad" maxlength="100" value="<?= htmlspecialchars($perfil['especialidad'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="experiencia">Experiencia</label>
              <input class="form-control" id="experiencia" name="experiencia" maxlength="100" value="<?= htmlspecialchars($perfil['experiencia'] ?? '') ?>">
            </div>
          <?php elseif ($rol === 'recepcionista'): ?>
            <div class="col-md-6">
              <label class="form-label" for="turno">Turno</label>
              <input class="form-control" id="turno" name="turno" maxlength="30" value="<?= htmlspecialchars($perfil['turno'] ?? '') ?>">
            </div>
          <?php endif; ?>
          <div class="col-12">
            <button type="submit" class="btn btn-verde rounded-pill">Guardar cambios</button>
          </div>
        </form>
      </section>
    </main>
  </div>
</body>
</html>
