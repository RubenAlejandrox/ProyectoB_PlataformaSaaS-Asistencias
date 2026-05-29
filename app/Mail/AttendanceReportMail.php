<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class AttendanceReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $messageBody,
        public string $attachmentName,
        public string $attachmentData,
        public string $classroomName,
        public string $reportTypeLabel,
        public ?string $periodLabel,
        public string $senderName,
        public string $senderEmail,
        public string $reportTitle,
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address', 'noreply@gamasolutions.com');
        $fromName    = config('mail.from.name', 'GAMA Solutions');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            replyTo: [
                new Address($this->senderEmail, $this->senderName),
            ],
            subject: $this->subjectLine,
            tags: ['reporte-asistencias'],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Entity-Ref-ID' => 'gama-report-' . md5($this->attachmentName . now()->timestamp),
            ],
        );
    }

    public function content(): Content
    {
        $preheader = "Reporte {$this->reportTypeLabel} — {$this->classroomName}. Archivo XLSX adjunto.";

        return new Content(
            view: 'emails.attendance-report',
            text: 'emails.attendance-report-text',
            with: [
                'messageBody'     => $this->messageBody,
                'classroomName'   => $this->classroomName,
                'reportTypeLabel' => $this->reportTypeLabel,
                'periodLabel'     => $this->periodLabel,
                'senderName'      => $this->senderName,
                'sentAt'          => now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'attachmentName'  => $this->attachmentName,
                'reportTitle'     => $this->reportTitle,
                'preheader'       => $preheader,
                'title'           => $this->subjectLine,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->attachmentData,
                $this->attachmentName
            )->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
