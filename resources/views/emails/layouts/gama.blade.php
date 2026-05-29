<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title ?? 'GAMA Solutions' }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#f2f7fb;font-family:Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">
    <span style="display:none!important;visibility:hidden;opacity:0;height:0;width:0;overflow:hidden;">
        {{ $preheader ?? '' }}
    </span>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f2f7fb;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e3edf5;">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#134474;padding:28px 32px;text-align:center;">
                            <p style="margin:0;font-size:22px;font-weight:bold;color:#ffffff;letter-spacing:1px;text-transform:uppercase;">
                                GAMA SOLUTIONS
                            </p>
                            <p style="margin:8px 0 0;font-size:12px;color:#a8c4dc;letter-spacing:0.3px;">
                                Plataforma de Control de Asistencias
                            </p>
                        </td>
                    </tr>
                    {{-- Accent bar --}}
                    <tr>
                        <td style="height:4px;background-color:#f28b2c;font-size:0;line-height:0;">&nbsp;</td>
                    </tr>
                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px 32px 24px;color:#545454;font-size:15px;line-height:1.6;">
                            @yield('content')
                        </td>
                    </tr>
                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#f2f7fb;padding:20px 32px;border-top:1px solid #e3edf5;text-align:center;">
                            <p style="margin:0 0 6px;font-size:11px;color:#5f86a6;">
                                Este mensaje fue enviado desde la plataforma GAMA SOLUTIONS.
                            </p>
                            <p style="margin:0;font-size:11px;color:#94a3b8;">
                                &copy; {{ date('Y') }} GAMA Solutions. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
