GAMA SOLUTIONS — Reporte de asistencias
========================================

{{ $reportTitle }}

Aula: {{ $classroomName }}
Tipo de reporte: {{ $reportTypeLabel }}
@if($periodLabel)
Período: {{ $periodLabel }}
@endif
Enviado por: {{ $senderName }}
Fecha: {{ $sentAt }}

{{ $messageBody }}

Archivo adjunto: {{ $attachmentName }}

---
Este mensaje fue enviado desde la plataforma GAMA SOLUTIONS.
© {{ date('Y') }} GAMA Solutions.
