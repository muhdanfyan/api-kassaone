<?php

namespace App\Modules\Perumahan\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfReportService
{
    /**
     * Generate Fee Report PDF
     */
    public function generateFeePdf($data)
    {
        $pdf = Pdf::loadView('pdf.fee-report', $data);
        
        $filename = sprintf(
            'Laporan_Iuran_%s_%d.pdf',
            $data['period']['month_name'],
            $data['period']['year']
        );
        
        return $pdf->download($filename);
    }
    
    /**
     * Generate Security Report PDF
     */
    public function generateSecurityPdf($data)
    {
        $pdf = Pdf::loadView('pdf.security-report', $data);
        
        $filename = sprintf(
            'Laporan_Keamanan_%s_to_%s.pdf',
            $data['period']['start_date'],
            $data['period']['end_date']
        );
        
        return $pdf->download($filename);
    }
    
    /**
     * Generate Service Report PDF
     */
    public function generateServicePdf($data)
    {
        $pdf = Pdf::loadView('pdf.service-report', $data);
        
        $filename = sprintf(
            'Laporan_Layanan_%s_to_%s.pdf',
            $data['period']['start_date'],
            $data['period']['end_date']
        );
        
        return $pdf->download($filename);
    }
}
