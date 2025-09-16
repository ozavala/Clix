<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
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
use App\Http\Controllers\Reports\TaxBalanceController;
use Maatwebsite\Excel\Excel as ExcelFacade;

class TaxBalanceExport implements 
    FromArray,
    WithHeadings, 
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

    public function array(): array
    {
        // Create controller instance
        $controller = new TaxBalanceController(app(ExcelFacade::class));
        
        // Use reflection to access the private method
        $reflectionMethod = new \ReflectionMethod($controller, 'generateTaxBalanceReport');
        $reflectionMethod->setAccessible(true);
        
        // Call the method with required parameters
        $report = $reflectionMethod->invoke($controller, $this->startDate, $this->endDate, $this->tenantId);
        
        $data = [];
        
        // Add summary section
        $data[] = ['Resumen de IVA'];
        $data[] = [
            'Período', 
            $report['period']['start_formatted'] . ' - ' . $report['period']['end_formatted']
        ];
        $data[] = [
            'Total IVA Cobrado', 
            number_format($report['summary']['total_tax_collected'], 2)
        ];
        $data[] = [
            'Total IVA Pagado', 
            number_format($report['summary']['total_tax_paid'], 2)
        ];
        $data[] = [
            'Saldo Neto de IVA', 
            number_format($report['summary']['net_tax_balance'], 2)
        ];
        $data[] = []; // Empty row
        
        // Add sales tax by rate
        $data[] = ['IVA por Tasa - Ventas'];
        $data[] = ['Tasa', 'Base Imponible', 'IVA'];
        foreach ($report['sales_tax_by_rate'] as $rate) {
            $data[] = [
                $rate->tax_rate_name . ' (' . $rate->tax_rate_percentage . '%)',
                number_format($rate->total_taxable_amount, 2),
                number_format($rate->total_tax_collected, 2)
            ];
        }
        $data[] = []; // Empty row
        
        // Add purchase tax by rate
        $data[] = ['IVA por Tasa - Compras'];
        $data[] = ['Tasa', 'Base Imponible', 'IVA'];
        foreach ($report['purchase_tax_by_rate'] as $rate) {
            $data[] = [
                $rate->tax_rate_name . ' (' . $rate->tax_rate_percentage . '%)',
                number_format($rate->total_taxable_amount, 2),
                number_format($rate->total_tax_paid, 2)
            ];
        }
        
        return $data;
    }

    public function headings(): array
    {
        return [
            'Concepto',
            'Valor'
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
                $sheet = $event->sheet;
                
                // Style the header
                $sheet->getStyle('A1:B1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFD9D9D9'],
                    ]
                ]);
                
                // Style section headers
                $sheet->getStyle('A2:A1000')->getFont()->setBold(true);
                
                // Style currency columns
                $sheet->getStyle('B3:B1000')->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
                    
                // Auto-size columns
                $sheet->getColumnDimension('A')->setWidth(30);
                $sheet->getColumnDimension('B')->setWidth(20);
            },
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => 'center']
            ],
            // Style section headers
            'A2:A1000' => [
                'font' => ['bold' => true]
            ],
            // Style data rows
            'A3:B1000' => [
                'font' => ['size' => 12]
            ],
            // Add borders to all cells with data
            'A1:B' . $sheet->getHighestRow() => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }
}
