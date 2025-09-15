<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;
use App\Models\Tenant;

class TaxBalanceExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithTitle,
    WithEvents,
    WithColumnFormatting,
    ShouldAutoSize,
    WithStyles
{
    protected $startDate;
    protected $endDate;
    protected $tenantId;
    protected $selectedTenant;

    public function __construct($startDate, $endDate, $tenantId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->tenantId = $tenantId;
        $this->selectedTenant = $tenantId ? Tenant::find($tenantId) : null;
    }

    public function collection()
    {
        $query = \DB::table('tax_transactions as t')
            ->select(
                't.transaction_date',
                't.reference_number',
                't.description',
                't.amount',
                't.tax_amount',
                't.type',
                'tr.name as tax_rate_name',
                'tr.rate as tax_rate',
                't.created_at',
                't.updated_at'
            )
            ->leftJoin('tax_rates as tr', 't.tax_rate_id', '=', 'tr.id')
            ->whereBetween('t.transaction_date', [$this->startDate, $this->endDate])
            ->orderBy('t.transaction_date');
        
        // Apply tenant filter if tenant_id is provided
        if ($this->tenantId) {
            $query->where('t.tenant_id', $this->tenantId);
        }
        
        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Número de Referencia',
            'Descripción',
            'Monto',
            'Monto de IVA',
            'Tipo',
            'Tasa de IVA',
            'Porcentaje',
            'Creado',
            'Actualizado'
        ];
    }

    public function map($row): array
    {
        return [
            Carbon::parse($row->transaction_date)->format('d/m/Y'),
            $row->reference_number,
            $row->description,
            $row->amount,
            $row->tax_amount,
            $row->type == 'sale' ? 'Venta' : 'Compra',
            $row->tax_rate_name,
            $row->tax_rate . '%',
            Carbon::parse($row->created_at)->format('d/m/Y H:i'),
            Carbon::parse($row->updated_at)->format('d/m/Y H:i')
        ];
    }

    public function title(): string
    {
        return 'Balance de IVA';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getDelegate()->insertNewRowBefore(1, 4);
                
                // Add report title and date range
                $event->sheet->mergeCells('A1:J1');
                $event->sheet->setCellValue('A1', 'Reporte de Balance de IVA');
                $event->sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $event->sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
                
                $event->sheet->mergeCells('A2:J2');
                $event->sheet->setCellValue('A2', 'Período: ' . 
                    Carbon::parse($this->startDate)->format('d/m/Y') . ' al ' . 
                    Carbon::parse($this->endDate)->format('d/m/Y'));
                
                // Add tenant info if tenant is selected
                if ($this->selectedTenant) {
                    $event->sheet->mergeCells('A3:J3');
                    $event->sheet->setCellValue('A3', 'Tenant: ' . $this->selectedTenant->name);
                }
                
                // Format headers
                $event->sheet->getStyle('A5:J5')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFD9D9D9'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);
                
                // Add summary rows
                $lastRow = $event->sheet->getHighestRow() + 2;
                
                // Calculate totals
                $totalCollected = $this->collection()->where('type', 'sale')->sum('tax_amount');
                $totalPaid = $this->collection()->where('type', 'purchase')->sum('tax_amount');
                $netBalance = $totalCollected - $totalPaid;
                
                // Add summary
                $event->sheet->setCellValue("D{$lastRow}", 'Total IVA Cobrado:');
                $event->sheet->setCellValue("E{$lastRow}", $totalCollected);
                
                $event->sheet->setCellValue("D" . ($lastRow + 1), 'Total IVA Pagado:');
                $event->sheet->setCellValue("E" . ($lastRow + 1), $totalPaid);
                
                $event->sheet->setCellValue("D" . ($lastRow + 2), 'Saldo Neto:');
                $event->sheet->setCellValue("E" . ($lastRow + 2), $netBalance);
                
                // Style summary
                $summaryRange = "D{$lastRow}:E" . ($lastRow + 2);
                $event->sheet->getStyle($summaryRange)->getFont()->setBold(true);
                $event->sheet->getStyle("E{$lastRow}:E" . ($lastRow + 2))->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
                
                // Add border to summary
                $event->sheet->getStyle($summaryRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);
                
                // Highlight net balance row
                $event->sheet->getStyle("D" . ($lastRow + 2) . ":E" . ($lastRow + 2))->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => $netBalance >= 0 ? 'FFD4EDDA' : 'FFF8D7DA'],
                    ],
                ]);
                
                // Auto-size all columns
                foreach(range('A','J') as $columnID) {
                    $event->sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            },
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'E' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            5    => ['font' => ['bold' => true]],
            
            // Styling the summary rows
            'A1:J4' => ['font' => ['bold' => true]],
            
            // Add border to all cells
            'A5:J' . $sheet->getHighestRow() => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }
}
