<?php

namespace App\Exports;

use App\Services\IntensiMisaReportService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IntensiMisaExport extends DefaultValueBinder implements FromQuery, WithColumnFormatting, WithColumnWidths, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private array $filters) {}

    public function query()
    {
        // FromQuery reads all matching rows in chunks, independent of screen pagination.
        return app(IntensiMisaReportService::class)->query($this->filters);
    }

    public function headings(): array
    {
        return ['Waktu Pengajuan / Pencatatan (WIB)', 'Waktu Diterima Sekretariat (WIB)', 'Tanggal Misa', 'Jadwal Misa', 'Pemohon', 'Wilayah', 'Lingkungan', 'Ucapan Syukur', 'Doa Arwah', 'Permohonan Lainnya', 'Sumbangan Intensi (Rp)', 'Status'];
    }

    public function map($service): array
    {
        $r = app(IntensiMisaReportService::class)->row($service);
        $stamp = fn ($value) => $value ? Carbon::parse($value)->timezone('Asia/Jakarta')->format('d-m-Y H:i') : '-';

        return [
            $stamp($r['submitted_at']), $stamp($r['received_at']), $r['misa_date'], $r['schedule'],
            $r['applicant_name'], $r['region'] ?? '-', $r['neighborhood'] ?? '-',
            $r['ucapan_syukur'], $r['doa_arwah'], $r['permohonan_lainnya'], $r['stipendium'],
            ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$r['status']] ?? $r['status'],
        ];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        // Preserve user-entered intentions literally, including text beginning with '='.
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return ['K' => '"Rp "#,##0.00'];
    }

    public function columnWidths(): array
    {
        return ['A' => 24, 'B' => 24, 'C' => 14, 'D' => 12, 'E' => 25, 'F' => 20, 'G' => 22, 'H' => 45, 'I' => 45, 'J' => 45, 'K' => 20, 'L' => 15];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setWrapText(true)->setVertical('top');

        return [1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F3E8E8']]]];
    }
}
