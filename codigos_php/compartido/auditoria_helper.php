<?php
function registrarActividadSistema(
    mysqli $conn,
    int $idUsuario,
    string $accion,
    string $entidad,
    ?int $idRegistro,
    ?int $idServicio,
    string $descripcion
): bool {
    try {
        $stmt = $conn->prepare(
            'INSERT INTO historial_actividad_usuario
             (id_usuario,id_servicio,entidad,id_registro,accion,descripcion)
             VALUES (?,?,?,?,?,?)'
        );
        $stmt->bind_param('iisiss', $idUsuario, $idServicio, $entidad, $idRegistro, $accion, $descripcion);
        return $stmt->execute();
    } catch (mysqli_sql_exception $error) {
        error_log('No se pudo registrar actividad de usuario: ' . $error->getMessage());
        return false;
    }
}
