@extends('emails.layouts.gama')

@section('content')
    <p style="margin:0 0 8px;font-size:13px;color:#134474;font-weight:bold;text-transform:uppercase;letter-spacing:0.5px;">
        Reporte de asistencias
    </p>

    <h1 style="margin:0 0 20px;font-size:20px;font-weight:bold;color:#134474;line-height:1.3;">
        {{ $reportTitle }}
    </h1>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:24px;background-color:#f2f7fb;border-radius:6px;border:1px solid #e3edf5;">
        <tr>
            <td style="padding:16px 18px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="padding:4px 0;font-size:13px;color:#5f86a6;width:120px;vertical-align:top;">Aula</td>
                        <td style="padding:4px 0;font-size:14px;color:#134474;font-weight:bold;">{{ $classroomName }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;font-size:13px;color:#5f86a6;">Tipo de reporte</td>
                        <td style="padding:4px 0;font-size:14px;color:#545454;">{{ $reportTypeLabel }}</td>
                    </tr>
                    @if($periodLabel)
                    <tr>
                        <td style="padding:4px 0;font-size:13px;color:#5f86a6;">Período</td>
                        <td style="padding:4px 0;font-size:14px;color:#545454;">{{ $periodLabel }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding:4px 0;font-size:13px;color:#5f86a6;">Enviado por</td>
                        <td style="padding:4px 0;font-size:14px;color:#545454;">{{ $senderName }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;font-size:13px;color:#5f86a6;">Fecha</td>
                        <td style="padding:4px 0;font-size:14px;color:#545454;">{{ $sentAt }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px;font-size:15px;color:#545454;line-height:1.6;">
        {{ $messageBody }}
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:8px;">
        <tr>
            <td style="padding:14px 16px;background-color:#eaf3fb;border-left:4px solid #134474;border-radius:0 6px 6px 0;">
                <p style="margin:0;font-size:14px;color:#134474;">
                    <strong>Archivo adjunto:</strong> {{ $attachmentName }}
                </p>
                <p style="margin:8px 0 0;font-size:13px;color:#5f86a6;">
                    Abre el archivo XLSX con Microsoft Excel, Google Sheets o LibreOffice Calc.
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:20px 0 0;font-size:13px;color:#94a3b8;line-height:1.5;">
        Si no esperabas este correo, puedes ignorarlo. Para dudas, responde a quien lo envió.
    </p>
@endsection
