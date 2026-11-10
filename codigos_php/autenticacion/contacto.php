<?php
declare(strict_types=1);

function volverContacto(string $estado): never
{
    header('Location: ../index.html?contacto=' . rawurlencode($estado) . '#contacto', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Método no permitido.');
}

if (trim((string)($_POST['sitio_web'] ?? '')) !== '') {
    volverContacto('error');
}

$nombre = trim((string)($_POST['nombre'] ?? ''));
$correo = trim((string)($_POST['correo'] ?? ''));
$telefono = trim((string)($_POST['telefono'] ?? ''));
$mensaje = trim((string)($_POST['mensaje'] ?? ''));

if (
    $nombre === '' ||
    mb_strlen($nombre) > 100 ||
    preg_match('/^(?=.*\p{L})\P{N}+$/uD', $nombre) !== 1 ||
    $correo === '' ||
    mb_strlen($correo) > 254 ||
    filter_var($correo, FILTER_VALIDATE_EMAIL) === false ||
    preg_match('/\.[A-Za-z]{2,}$/D', $correo) !== 1 ||
    mb_strlen($telefono) > 30 ||
    ($telefono !== '' && preg_match('/^[0-9]{7,30}$/D', $telefono) !== 1) ||
    $mensaje === '' ||
    mb_strlen($mensaje) > 5000
) {
    volverContacto('datos');
}

$destinatario = 'senaserviteca@gmail.com';
$asunto = 'Mensaje de contacto - Serviteca SENA';
$cuerpo = implode("\r\n", [
    'Nuevo mensaje recibido desde el sitio web de Serviteca SENA.',
    '',
    'Nombre: ' . $nombre,
    'Correo de respuesta: ' . $correo,
    'Teléfono: ' . ($telefono !== '' ? $telefono : 'No proporcionado'),
    '',
    'Mensaje:',
    $mensaje
]);
$cabeceras = [
    'From' => 'Serviteca SENA <senaserviteca@gmail.com>',
    'Reply-To' => $correo,
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8',
    'X-Mailer' => 'PHP/' . PHP_VERSION
];

try {
    $enviado = mail($destinatario, $asunto, $cuerpo, $cabeceras);
} catch (Throwable $error) {
    error_log('No se pudo enviar el formulario de contacto: ' . $error->getMessage());
    volverContacto('error');
}

if (!$enviado) {
    error_log('La función mail() rechazó el mensaje del formulario de contacto.');
    volverContacto('error');
}

volverContacto('enviado');
