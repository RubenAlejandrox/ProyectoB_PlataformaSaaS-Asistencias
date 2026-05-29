<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClassroomStudentsExport implements FromArray, WithHeadings
{
    public function __construct(
        private array $rows
    ) {}

    public function headings(): array
    {
        return [
            'Alumno',
            'Correo',
            '% Asistencia',
            'Semáforo',
            'Asistencias',
            'Justificados aprobados',
            'Total sesiones del ciclo',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }
}
