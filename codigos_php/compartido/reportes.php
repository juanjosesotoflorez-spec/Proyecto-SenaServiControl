<?php
session_start();
$rol = $_SESSION['rol'] ?? '';
$rolesPermitidos = ['administrador', 'recepcionista', 'tecnico', 'cliente'];
if (!isset($_SESSION['id_usuario']) || !in_array($rol, $rolesPermitidos, true)) {
    header('Location: login.php');
    exit;
}
require dirname(__DIR__, 2) . '/db.php';

function rf100FechaValida(string $fecha): bool
{
    $objeto = DateTime::createFromFormat('!Y-m-d', $fecha);
    return $objeto !== false && $objeto->format('Y-m-d') === $fecha;
}

function rf100Consultar(mysqli $conn, string $sql, string $tipos = '', array $parametros = []): array
{
    $stmt = $conn->prepare($sql);
    if ($tipos !== '') {
        $argumentos = [$tipos];
        foreach ($parametros as $indice => $valor) {
            $parametros[$indice] = $valor;
            $argumentos[] = &$parametros[$indice];
        }
        call_user_func_array([$stmt, 'bind_param'], $argumentos);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$clienteId = 0;
$tecnicoId = 0;
if ($rol === 'cliente') {
    $perfil = rf100Consultar($conn, 'SELECT id_cliente FROM cliente WHERE id_usuario=?', 'i', [(int)$_SESSION['id_usuario']]);
    $clienteId = (int)($perfil[0]['id_cliente'] ?? 0);
} elseif ($rol === 'tecnico') {
    $perfil = rf100Consultar($conn, 'SELECT id_tecnico FROM tecnico WHERE id_usuario=?', 'i', [(int)$_SESSION['id_usuario']]);
    $tecnicoId = (int)($perfil[0]['id_tecnico'] ?? 0);
}

$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');
$periodo = $_GET['periodo'] ?? '';
$periodosPermitidos = ['diario', 'semanal', 'mensual'];
if (in_array($periodo, $periodosPermitidos, true) && $desde === '' && $hasta === '') {
    $hoy = new DateTimeImmutable('today');
    if ($periodo === 'diario') {
        $desde = $hoy->format('Y-m-d');
        $hasta = $desde;
    } elseif ($periodo === 'semanal') {
        $desde = $hoy->modify('monday this week')->format('Y-m-d');
        $hasta = $hoy->modify('sunday this week')->format('Y-m-d');
    } else {
        $desde = $hoy->modify('first day of this month')->format('Y-m-d');
        $hasta = $hoy->modify('last day of this month')->format('Y-m-d');
    }
}
$errorFiltro = '';
if (($desde !== '' && !rf100FechaValida($desde)) || ($hasta !== '' && !rf100FechaValida($hasta))) {
    $errorFiltro = 'Selecciona fechas válidas para el período.';
} elseif ($desde !== '' && $hasta !== '' && $desde > $hasta) {
    $errorFiltro = 'La fecha inicial no puede ser posterior a la fecha final.';
}

$where = [];
$tipos = '';
$parametros = [];
if ($rol === 'tecnico') {
    $where[] = 's.id_tecnico=?';
    $tipos .= 'i';
    $parametros[] = $tecnicoId;
} elseif ($rol === 'cliente') {
    $where[] = 'v.id_cliente=?';
    $tipos .= 'i';
    $parametros[] = $clienteId;
}
if ($desde !== '' && $errorFiltro === '') {
    $where[] = 's.fecha_ingreso>=?';
    $tipos .= 's';
    $parametros[] = $desde;
}
if ($hasta !== '' && $errorFiltro === '') {
    $where[] = 's.fecha_ingreso<=?';
    $tipos .= 's';
    $parametros[] = $hasta;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
if ($errorFiltro !== '') {
    $whereSql .= ($whereSql === '' ? ' WHERE ' : ' AND ') . '1=0';
}
$completedWhereSql = ($whereSql === '' ? ' WHERE ' : $whereSql . ' AND ')
    . 's.estado IN ("Finalizado","Entregado")';
$activeWhereSql = ($whereSql === '' ? ' WHERE ' : $whereSql . ' AND ')
    . '(s.estado NOT IN ("Finalizado","Entregado") OR s.estado IS NULL)';
$baseFrom = ' FROM servicio s
              JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
              JOIN cliente c ON c.id_cliente=v.id_cliente
              LEFT JOIN tecnico t ON t.id_tecnico=s.id_tecnico
              LEFT JOIN recepcionista r ON r.id_recepcionista=s.id_recepcionista';

$metricas = [
    'servicios' => 0,
    'finalizados' => 0,
    'activos' => 0,
    'costo_finalizados' => 0.0,
    'vehiculos' => 0,
    'clientes' => 0
];
$estados = [];
$tendencia = [];
$topVehiculos = [];
$frecuenciaClientes = [];
$monitoreo = [];
$retrasados = [];
$actividades = [];
$vehiculosCliente = [];
$serviciosCliente = [];
$clientesAtendidos = 0;

if ($errorFiltro === '') {
    $rows = rf100Consultar(
        $conn,
        'SELECT COUNT(*) AS servicios,
                COALESCE(SUM(s.estado IN ("Finalizado","Entregado")),0) AS finalizados,
                COALESCE(SUM(s.estado NOT IN ("Finalizado","Entregado") OR s.estado IS NULL),0) AS activos,
                COALESCE(SUM(CASE WHEN s.estado IN ("Finalizado","Entregado") THEN s.costo ELSE 0 END),0) AS costo_finalizados,
                COUNT(DISTINCT CASE WHEN s.estado IN ("Finalizado","Entregado") THEN v.id_vehiculo END) AS vehiculos,
                COUNT(DISTINCT CASE WHEN s.estado IN ("Finalizado","Entregado") THEN c.id_cliente END) AS clientes'
            . $baseFrom . $whereSql,
        $tipos,
        $parametros
    );
    if ($rows) {
        $metricas = $rows[0];
    }
    $estados = rf100Consultar(
        $conn,
        'SELECT COALESCE(s.estado,"Sin estado") AS estado, COUNT(*) AS cantidad'
            . $baseFrom . $whereSql . ' GROUP BY s.estado ORDER BY cantidad DESC,s.estado',
        $tipos,
        $parametros
    );

    if ($rol === 'administrador') {
        $tendencia = rf100Consultar(
            $conn,
            'SELECT DATE_FORMAT(s.fecha_ingreso,"%Y-%m") AS periodo, COUNT(*) AS servicios,
                    SUM(s.estado IN ("Finalizado","Entregado")) AS completados,
                    COALESCE(SUM(CASE WHEN s.estado IN ("Finalizado","Entregado") THEN s.costo ELSE 0 END),0) AS costo
             ' . $baseFrom . $whereSql . ' GROUP BY DATE_FORMAT(s.fecha_ingreso,"%Y-%m") ORDER BY periodo DESC LIMIT 12',
            $tipos,
            $parametros
        );
    }

    if (in_array($rol, ['administrador', 'tecnico'], true)) {
        $topVehiculos = rf100Consultar(
            $conn,
            'SELECT COALESCE(v.marca,"Sin marca") AS marca, COALESCE(v.modelo,"Sin modelo") AS modelo,
                    COALESCE(v.anio,"Sin año") AS anio, COUNT(*) AS servicios
             ' . $baseFrom . $completedWhereSql
                . ' GROUP BY v.marca,v.modelo,v.anio ORDER BY servicios DESC,marca,modelo LIMIT 10',
            $tipos,
            $parametros
        );
    }

    if ($rol === 'administrador') {
        $extraWhere = $whereSql;
        $frecuenciaClientes = rf100Consultar(
            $conn,
            'SELECT c.id_cliente,c.nombre,c.apellido,COUNT(*) AS servicios,
                    COUNT(DISTINCT v.id_vehiculo) AS vehiculos
             ' . $baseFrom . $extraWhere
                . ($extraWhere === '' ? ' WHERE ' : ' AND ') . 's.estado IN ("Finalizado","Entregado")
                    GROUP BY c.id_cliente,c.nombre,c.apellido ORDER BY servicios DESC,c.nombre,c.apellido LIMIT 10',
            $tipos,
            $parametros
        );
    } elseif ($rol === 'recepcionista') {
        $frecuenciaClientes = rf100Consultar(
            $conn,
            'SELECT c.id_cliente,c.nombre,c.apellido,COUNT(*) AS servicios,
                    COUNT(DISTINCT v.id_vehiculo) AS vehiculos
             ' . $baseFrom . $whereSql
                . ($whereSql === '' ? ' WHERE ' : ' AND ') . 's.estado IN ("Finalizado","Entregado")
                    GROUP BY c.id_cliente,c.nombre,c.apellido ORDER BY servicios DESC,c.nombre,c.apellido LIMIT 10',
            $tipos,
            $parametros
        );
    }

    if ($rol === 'tecnico') {
        $rows = rf100Consultar(
            $conn,
            'SELECT COUNT(DISTINCT c.id_cliente) AS total' . $baseFrom . $whereSql
               . ($whereSql === '' ? ' WHERE ' : ' AND ') . 's.estado IN ("Finalizado","Entregado")',
            $tipos,
            $parametros
        );
        $clientesAtendidos = (int)($rows[0]['total'] ?? 0);
    }

    if (in_array($rol, ['administrador', 'recepcionista', 'tecnico', 'cliente'], true)) {
        $monitoreo = rf100Consultar(
            $conn,
            'SELECT s.id_servicio,s.tipo_servicio,s.estado,s.fecha_ingreso,s.fecha_entrega_estimada,
                    v.placa,c.nombre AS cliente,t.nombre AS tecnico,t.apellido AS apellido_tecnico,
                    r.nombre AS recepcionista,r.apellido AS apellido_recepcionista,
                    COALESCE(seg.fecha_hora,TIMESTAMP(seg.fecha)) AS ultima_fecha,
                    seg.estado AS ultimo_estado,seg.comentario AS ultima_actualizacion
             ' . $baseFrom . '
             LEFT JOIN seguimiento seg ON seg.id_seguimiento=(
                 SELECT sc.id_seguimiento FROM seguimiento sc
                 WHERE sc.id_servicio=s.id_servicio
                 ORDER BY COALESCE(sc.fecha_hora,TIMESTAMP(sc.fecha)) DESC,sc.id_seguimiento DESC LIMIT 1
             )'
                . $activeWhereSql . ' ORDER BY s.fecha_ingreso,s.id_servicio DESC LIMIT 100',
            $tipos,
            $parametros
        );
    }

    if (in_array($rol, ['administrador', 'recepcionista'], true)) {
        $retrasados = rf100Consultar(
            $conn,
            'SELECT s.id_servicio,s.tipo_servicio,s.estado,s.fecha_entrega_estimada,
                    m.fecha_programacion,m.estado_trabajo,v.placa,t.nombre AS tecnico,t.apellido AS apellido_tecnico,
                    CASE
                      WHEN s.fecha_entrega_estimada<CURDATE() THEN s.fecha_entrega_estimada
                      ELSE m.fecha_programacion
                    END AS fecha_retraso
             ' . $baseFrom . '
             LEFT JOIN mantenimiento m ON m.id_servicio=s.id_servicio'
                . $activeWhereSql . ' AND (s.fecha_entrega_estimada<CURDATE()
                     OR (m.fecha_programacion<CURDATE() AND (m.estado_trabajo IS NULL OR m.estado_trabajo<>"Completado")))
                   ORDER BY fecha_retraso,s.id_servicio LIMIT 100',
            $tipos,
            $parametros
        );
    }

    if ($rol === 'administrador') {
        $actividades = rf100Consultar(
            $conn,
            'SELECT h.fecha_hora,h.accion,h.entidad,h.id_registro,h.id_servicio,h.descripcion,u.usuario,u.rol
             FROM historial_actividad_usuario h JOIN usuario u ON u.id_usuario=h.id_usuario
             ORDER BY h.fecha_hora DESC,h.id_actividad DESC LIMIT 20'
        );
        $rows = rf100Consultar($conn, 'SELECT COUNT(*) AS total FROM cliente');
        $metricas['clientes_registrados'] = (int)($rows[0]['total'] ?? 0);
    } elseif ($rol === 'cliente' && $clienteId > 0) {
        $vehiculosCliente = rf100Consultar(
            $conn,
            'SELECT id_vehiculo,placa,marca,modelo,color,anio FROM vehiculo WHERE id_cliente=? ORDER BY id_vehiculo DESC',
            'i',
            [$clienteId]
        );
        $serviciosCliente = rf100Consultar(
            $conn,
            'SELECT s.id_servicio,s.tipo_servicio,s.estado,s.fecha_ingreso,s.fecha_entrega_estimada,
                    v.placa,COALESCE(seg.fecha_hora,TIMESTAMP(seg.fecha)) AS ultima_fecha,
                    seg.estado AS ultimo_estado,seg.comentario AS ultima_actualizacion
             FROM servicio s JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
             LEFT JOIN seguimiento seg ON seg.id_seguimiento=(
                 SELECT sc.id_seguimiento FROM seguimiento sc WHERE sc.id_servicio=s.id_servicio
                 ORDER BY COALESCE(sc.fecha_hora,TIMESTAMP(sc.fecha)) DESC,sc.id_seguimiento DESC LIMIT 1
             )
             WHERE v.id_cliente=? ORDER BY s.fecha_ingreso DESC,s.id_servicio DESC',
            'i',
            [$clienteId]
        );
    }
}

$titulo = 'Reportes e indicadores';
$moneda = static function ($valor): string {
    return '$' . number_format((float)$valor, 2, ',', '.');
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($titulo) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/module-pages.css?v=20261022">
</head>
<body class="admin-body module-page module-reports">
<div class="admin-layout">
  <aside class="admin-sidebar"><div class="sidebar-brand"><h4>Reportes</h4></div><nav class="sidebar-nav">
    <?php if ($rol === 'administrador'): ?><a href="dashboard_admin.php">Volver al panel</a><a href="admin_servicios.php">Servicios</a>
    <?php elseif ($rol === 'recepcionista'): ?><a href="dashboard_recepcionista.php">Volver al panel</a><a href="recepcionista_servicios.php">Servicios</a>
    <?php elseif ($rol === 'tecnico'): ?><a href="dashboard_tecnico.php">Volver al panel</a><a href="tecnico_servicios.php">Servicios asignados</a>
    <?php else: ?><a href="dashboard_cliente.php">Volver al panel</a><a href="cliente_servicios.php">Mis servicios</a><a href="cliente_vehiculos.php">Mis vehículos</a><?php endif; ?>
    <a href="reportes.php" class="active">Reportes e indicadores</a>
    <?php if ($rol === 'administrador' || $rol === 'recepcionista'): ?><a href="historial_actividades.php">Historial de actividades</a><a href="incidencias.php">Incidencias</a>
    <?php elseif ($rol === 'tecnico'): ?><a href="incidencias.php">Reportar incidencia</a><?php endif; ?>
    <a href="logout.php">Salir</a>
  </nav></aside>
  <main class="admin-content">
    <h2 class="section-title"><?= htmlspecialchars($titulo) ?></h2>
    <?php if ($errorFiltro !== ''): ?><div class="alert alert-warning"><?= htmlspecialchars($errorFiltro) ?></div><?php endif; ?>
    <div class="dashboard-panel mb-4">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-12 d-flex flex-wrap gap-2">
          <a class="btn btn-sm <?= $periodo === 'diario' ? 'btn-verde' : 'btn-outline-verde' ?>" href="reportes.php?periodo=diario">Hoy</a>
          <a class="btn btn-sm <?= $periodo === 'semanal' ? 'btn-verde' : 'btn-outline-verde' ?>" href="reportes.php?periodo=semanal">Esta semana</a>
          <a class="btn btn-sm <?= $periodo === 'mensual' ? 'btn-verde' : 'btn-outline-verde' ?>" href="reportes.php?periodo=mensual">Este mes</a>
        </div>
        <div class="col-md-4"><label class="form-label">Fecha inicial</label><input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>"></div>
        <div class="col-md-4"><label class="form-label">Fecha final</label><input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>"></div>
        <div class="col-md-4 d-flex gap-2"><button class="btn btn-verde rounded-pill">Filtrar</button><a href="reportes.php" class="btn btn-outline-verde rounded-pill">Limpiar</a></div>
      </form>
      <small class="text-muted">El período filtra por fecha de ingreso del servicio. Las actividades y las incidencias conservan su fecha propia.</small>
    </div>

    <?php if ($errorFiltro === ''): ?>
    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-xl-3"><div class="dashboard-panel h-100"><small class="text-muted">Servicios en período</small><h3><?= (int)$metricas['servicios'] ?></h3></div></div>
      <div class="col-sm-6 col-xl-3"><div class="dashboard-panel h-100"><small class="text-muted">Finalizados o entregados</small><h3><?= (int)$metricas['finalizados'] ?></h3></div></div>
      <div class="col-sm-6 col-xl-3"><div class="dashboard-panel h-100"><small class="text-muted">Activos o sin estado</small><h3><?= (int)$metricas['activos'] ?></h3></div></div>
      <div class="col-sm-6 col-xl-3"><div class="dashboard-panel h-100"><small class="text-muted"><?= $rol === 'administrador' ? 'Clientes registrados' : ($rol === 'tecnico' ? 'Clientes atendidos' : 'Vehículos finalizados') ?></small><h3><?= $rol === 'administrador' ? (int)($metricas['clientes_registrados'] ?? 0) : ($rol === 'tecnico' ? $clientesAtendidos : (int)$metricas['vehiculos']) ?></h3></div></div>
    </div>
    <div class="dashboard-panel mb-4"><h5 class="section-title">Costos de servicios completados</h5><p class="fs-4 mb-0"><?= $moneda($metricas['costo_finalizados']) ?></p><small class="text-muted">Suma de costos de servicios con trabajo finalizado, incluido el estado Entregado.</small></div>

    <div class="dashboard-panel mb-4"><h5 class="section-title">Servicios por estado</h5>
      <?php if ($estados): ?><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Estado</th><th>Cantidad</th></tr></thead><tbody><?php foreach ($estados as $fila): ?><tr><td><?= htmlspecialchars($fila['estado']) ?></td><td><?= (int)$fila['cantidad'] ?></td></tr><?php endforeach; ?></tbody></table></div>
      <?php else: ?><p class="text-muted mb-0">No hay información disponible para el período seleccionado.</p><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($rol === 'administrador' && $errorFiltro === ''): ?>
    <div class="dashboard-panel mb-4"><h5 class="section-title">Tendencia mensual</h5><?php if ($tendencia): ?><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Mes</th><th>Servicios</th><th>Completados</th><th>Costos completados</th></tr></thead><tbody><?php foreach ($tendencia as $fila): ?><tr><td><?= htmlspecialchars($fila['periodo']) ?></td><td><?= (int)$fila['servicios'] ?></td><td><?= (int)$fila['completados'] ?></td><td><?= $moneda($fila['costo']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="text-muted mb-0">No hay información disponible para el período seleccionado.</p><?php endif; ?></div>
    <div class="dashboard-panel mb-4"><h5 class="section-title">Clientes con más servicios completados</h5><?php if ($frecuenciaClientes): ?><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Cliente</th><th>Vehículos</th><th>Servicios</th></tr></thead><tbody><?php foreach ($frecuenciaClientes as $fila): ?><tr><td><?= htmlspecialchars($fila['nombre'].' '.$fila['apellido']) ?></td><td><?= (int)$fila['vehiculos'] ?></td><td><?= (int)$fila['servicios'] ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="text-muted mb-0">No hay información disponible para el período seleccionado.</p><?php endif; ?></div>
    <?php endif; ?>

    <?php if (in_array($rol, ['administrador','recepcionista'], true) && $errorFiltro === ''): ?>
    <div class="dashboard-panel mb-4"><h5 class="section-title">Servicios activos y última actualización</h5><?php if ($monitoreo): ?><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Servicio</th><th>Vehículo</th><th>Estado</th><th>Técnico</th><th>Recepcionista</th><th>Último avance</th></tr></thead><tbody><?php foreach ($monitoreo as $fila): ?><tr><td>#<?= (int)$fila['id_servicio'] ?> · <?= htmlspecialchars($fila['tipo_servicio']) ?></td><td><?= htmlspecialchars($fila['placa']) ?></td><td><?= htmlspecialchars($fila['estado'] ?? 'Sin estado') ?></td><td><?= htmlspecialchars(trim(($fila['tecnico'] ?? '').' '.($fila['apellido_tecnico'] ?? '')) ?: 'Sin asignar') ?></td><td><?= htmlspecialchars(trim(($fila['recepcionista'] ?? '').' '.($fila['apellido_recepcionista'] ?? '')) ?: 'No disponible') ?></td><td><?= htmlspecialchars($fila['ultima_fecha'] ?? 'Sin actualizaciones') ?><br><small><?= htmlspecialchars($fila['ultima_actualizacion'] ?? '') ?></small></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="text-muted mb-0">No hay servicios activos para mostrar.</p><?php endif; ?></div>
    <div class="dashboard-panel mb-4"><h5 class="section-title">Servicios retrasados</h5><?php if ($retrasados): ?><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Servicio</th><th>Vehículo</th><th>Estado</th><th>Fecha vencida</th><th>Técnico</th></tr></thead><tbody><?php foreach ($retrasados as $fila): ?><tr><td>#<?= (int)$fila['id_servicio'] ?> · <?= htmlspecialchars($fila['tipo_servicio']) ?></td><td><?= htmlspecialchars($fila['placa']) ?></td><td><?= htmlspecialchars($fila['estado'] ?? 'Sin estado') ?></td><td><?= htmlspecialchars($fila['fecha_retraso']) ?></td><td><?= htmlspecialchars(trim(($fila['tecnico'] ?? '').' '.($fila['apellido_tecnico'] ?? '')) ?: 'Sin asignar') ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="text-muted mb-0">No hay servicios retrasados con las fechas registradas.</p><?php endif; ?></div>
    <?php endif; ?>

    <?php if (in_array($rol, ['administrador','tecnico'], true) && $errorFiltro === ''): ?>
    <div class="dashboard-panel mb-4"><h5 class="section-title">Marcas, modelos y años más atendidos</h5><?php if ($topVehiculos): ?><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Marca</th><th>Modelo</th><th>Año</th><th>Servicios completados</th></tr></thead><tbody><?php foreach ($topVehiculos as $fila): ?><tr><td><?= htmlspecialchars($fila['marca']) ?></td><td><?= htmlspecialchars($fila['modelo']) ?></td><td><?= htmlspecialchars($fila['anio']) ?></td><td><?= (int)$fila['servicios'] ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="text-muted mb-0">No hay información disponible para el período seleccionado.</p><?php endif; ?></div>
    <?php endif; ?>

    <?php if ($rol === 'cliente' && $errorFiltro === ''): ?>
    <div class="dashboard-panel mb-4"><h5 class="section-title">Mis vehículos</h5><?php if ($vehiculosCliente): ?><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Placa</th><th>Marca</th><th>Modelo</th><th>Color</th><th>Año</th></tr></thead><tbody><?php foreach ($vehiculosCliente as $fila): ?><tr><td><?= htmlspecialchars($fila['placa']) ?></td><td><?= htmlspecialchars($fila['marca'] ?? '') ?></td><td><?= htmlspecialchars($fila['modelo'] ?? '') ?></td><td><?= htmlspecialchars($fila['color'] ?? '') ?></td><td><?= htmlspecialchars($fila['anio'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="text-muted mb-0">No tienes vehículos registrados.</p><?php endif; ?></div>
    <div class="dashboard-panel mb-4"><h5 class="section-title">Mis servicios e historial</h5><?php if ($serviciosCliente): ?><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Servicio</th><th>Vehículo</th><th>Estado actual</th><th>Ingreso</th><th>Última actividad</th></tr></thead><tbody><?php foreach ($serviciosCliente as $fila): ?><tr><td>#<?= (int)$fila['id_servicio'] ?> · <?= htmlspecialchars($fila['tipo_servicio']) ?></td><td><?= htmlspecialchars($fila['placa']) ?></td><td><?= htmlspecialchars($fila['ultimo_estado'] ?? $fila['estado'] ?? 'Sin estado') ?></td><td><?= htmlspecialchars($fila['fecha_ingreso']) ?></td><td><?= htmlspecialchars($fila['ultima_fecha'] ?? 'Sin actualización') ?><br><small><?= htmlspecialchars($fila['ultima_actualizacion'] ?? '') ?></small></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="text-muted mb-0">No hay información disponible para el período seleccionado.</p><?php endif; ?></div>
    <?php endif; ?>

    <?php if ($rol === 'recepcionista' && $frecuenciaClientes && $errorFiltro === ''): ?>
    <div class="dashboard-panel mb-4"><h5 class="section-title">Clientes con mayor frecuencia de atención</h5><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Cliente</th><th>Vehículos atendidos</th><th>Servicios completados</th></tr></thead><tbody><?php foreach ($frecuenciaClientes as $fila): ?><tr><td><?= htmlspecialchars($fila['nombre'].' '.$fila['apellido']) ?></td><td><?= (int)$fila['vehiculos'] ?></td><td><?= (int)$fila['servicios'] ?></td></tr><?php endforeach; ?></tbody></table></div></div>
    <?php elseif ($rol === 'recepcionista' && $errorFiltro === ''): ?><div class="dashboard-panel mb-4"><h5 class="section-title">Frecuencia de atención</h5><p class="text-muted mb-0">No hay información disponible para el período seleccionado.</p></div><?php endif; ?>

    <?php if ($rol === 'tecnico' && $errorFiltro === ''): ?>
    <div class="dashboard-panel mb-4"><h5 class="section-title">Actividades recientes de mis servicios</h5>
      <?php
      $actividadesTecnicas = rf100Consultar(
          $conn,
          'SELECT sc.id_servicio,sc.fecha,sc.fecha_hora,sc.estado,sc.comentario,v.placa
           FROM seguimiento sc JOIN servicio s ON s.id_servicio=sc.id_servicio
           JOIN vehiculo v ON v.id_vehiculo=s.id_vehiculo
           WHERE s.id_tecnico=? ORDER BY COALESCE(sc.fecha_hora,TIMESTAMP(sc.fecha)) DESC,sc.id_seguimiento DESC LIMIT 30',
          'i',
          [$tecnicoId]
      );
      ?>
      <?php if ($actividadesTecnicas): ?><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Fecha</th><th>Servicio</th><th>Estado</th><th>Actividad / comentario</th></tr></thead><tbody><?php foreach ($actividadesTecnicas as $fila): ?><tr><td><?= htmlspecialchars($fila['fecha_hora'] ?? $fila['fecha']) ?></td><td>#<?= (int)$fila['id_servicio'] ?> · <?= htmlspecialchars($fila['placa']) ?></td><td><?= htmlspecialchars($fila['estado'] ?? 'Sin estado') ?></td><td><?= nl2br(htmlspecialchars($fila['comentario'] ?? '')) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="text-muted mb-0">No hay actividades registradas para tus asignaciones.</p><?php endif; ?>
    </div>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
