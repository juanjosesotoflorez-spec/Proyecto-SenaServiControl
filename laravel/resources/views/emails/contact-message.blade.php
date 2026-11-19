<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva solicitud de contacto</title>
</head>
<body style="margin:0;padding:0;background-color:#f2f5f2;font-family:Arial,Helvetica,sans-serif;color:#203329;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        Nuevo mensaje de {{ $contact['nombre'] }} desde el sitio web de Serviteca SENA.
    </div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f2f5f2;">
        <tr>
            <td align="center" style="padding:32px 14px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background-color:#ffffff;border:1px solid #e1e9e1;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:26px 32px;background-color:#218500;color:#ffffff;">
                            <p style="margin:0 0 5px;font-size:12px;font-weight:bold;letter-spacing:1.6px;text-transform:uppercase;color:#d9f4ce;">Atención automotriz · Medellín</p>
                            <p style="margin:0;font-size:23px;font-weight:bold;line-height:1.3;">Serviteca SENA</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:34px 32px 12px;">
                            <p style="margin:0 0 10px;font-size:12px;font-weight:bold;letter-spacing:1.4px;text-transform:uppercase;color:#278400;">Nuevo contacto desde la página web</p>
                            <h1 style="margin:0 0 14px;font-size:27px;line-height:1.25;color:#193426;">Tienes un nuevo mensaje</h1>
                            <p style="margin:0;font-size:15px;line-height:1.7;color:#526258;">Hola, equipo de la Serviteca. Un usuario completó el formulario de contacto y espera su respuesta.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px 8px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f6f9f5;border:1px solid #e6eee4;border-radius:12px;">
                                <tr>
                                    <td style="padding:17px 20px 8px;font-size:11px;font-weight:bold;letter-spacing:1.2px;text-transform:uppercase;color:#748176;">Datos del usuario</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 20px;font-size:14px;line-height:1.6;color:#203329;">
                                        <strong style="display:inline-block;width:96px;color:#526258;">Nombre</strong>
                                        <span>{{ $contact['nombre'] }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 20px;font-size:14px;line-height:1.6;color:#203329;">
                                        <strong style="display:inline-block;width:96px;color:#526258;">Correo</strong>
                                        <a href="mailto:{{ $contact['correo'] }}" style="color:#247d08;text-decoration:underline;">{{ $contact['correo'] }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 20px 17px;font-size:14px;line-height:1.6;color:#203329;">
                                        <strong style="display:inline-block;width:96px;color:#526258;">Teléfono</strong>
                                        @if (!empty($contact['telefono']))
                                            <a href="tel:{{ $contact['telefono'] }}" style="color:#247d08;text-decoration:none;">{{ $contact['telefono'] }}</a>
                                        @else
                                            <span style="color:#748176;">No proporcionado</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px 6px;">
                            <h2 style="margin:0 0 12px;font-size:17px;color:#193426;">Mensaje recibido</h2>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#fbfcfb;border-left:4px solid #39a900;">
                                <tr>
                                    <td style="padding:17px 19px;font-size:15px;line-height:1.75;color:#35473b;">{!! nl2br(e($contact['mensaje'])) !!}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="padding:24px 32px 32px;">
                            <a href="mailto:{{ $contact['correo'] }}?subject={{ rawurlencode('Respuesta a tu mensaje - Serviteca SENA') }}" style="display:inline-block;padding:13px 20px;border-radius:8px;background-color:#39a900;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;">Responder al usuario</a>
                            <p style="margin:16px 0 0;font-size:13px;line-height:1.6;color:#69776c;">Puedes responder directamente a este correo: la dirección del usuario ya está configurada como destino de respuesta.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px;border-top:1px solid #e9eee9;background-color:#fafcf9;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#748176;">Este mensaje fue enviado desde el formulario de contacto de Serviteca SENA.<br>Atención clara y confiable para cada vehículo.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
