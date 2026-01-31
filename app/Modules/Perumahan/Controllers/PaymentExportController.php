<?php

namespace App\Modules\Perumahan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Perumahan\Models\EstateFeePayment;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class PaymentExportController extends Controller
{
    /**
     * Export payments report to PDF, Excel, or CSV
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function export(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'format' => 'in:pdf,excel,csv',
            'status' => 'in:paid,pending,overdue,cancelled',
            'period_month' => 'integer|min:1|max:12',
            'period_year' => 'integer|min:2020|max:2099',
            'date_from' => 'date',
            'date_to' => 'date',
            'search' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Build query with filters
            $query = EstateFeePayment::with(['resident', 'fee'])
                ->orderBy('period_year', 'desc')
                ->orderBy('period_month', 'desc')
                ->orderBy('house_number', 'asc');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('period_month')) {
                $query->where('period_month', $request->period_month);
            }

            if ($request->has('period_year')) {
                $query->where('period_year', $request->period_year);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('house_number', 'like', "%{$search}%")
                      ->orWhereHas('resident', function ($q) use ($search) {
                          $q->where('owner_name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->has('date_from')) {
                $query->whereDate('payment_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('payment_date', '<=', $request->date_to);
            }

            $payments = $query->get();

            if ($payments->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data untuk diekspor'
                ], 404);
            }

            // Calculate statistics
            $stats = [
                'total_unit' => $payments->count(),
                'lunas' => $payments->where('status', 'paid')->count(),
                'tertunggak' => $payments->whereIn('status', ['overdue', 'pending'])->count(),
                'total_terkumpul' => $payments->where('status', 'paid')
                    ->sum(function ($p) {
                        return $p->amount + $p->penalty_amount;
                    }),
            ];

            $format = $request->get('format', 'pdf');

            switch ($format) {
                case 'pdf':
                    return $this->exportPDF($payments, $stats, $request);
                case 'excel':
                    return $this->exportExcel($payments, $stats);
                case 'csv':
                    return $this->exportCSV($payments, $stats);
                default:
                    return $this->exportPDF($payments, $stats, $request);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengekspor laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export to PDF format
     * 
     * @param \Illuminate\Support\Collection $payments
     * @param array $stats
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    private function exportPDF($payments, $stats, $request)
    {
        $data = [
            'payments' => $payments,
            'stats' => $stats,
            'generated_at' => Carbon::now()->locale('id')->isoFormat('dddd, D MMMM YYYY HH:mm'),
            'filter_status' => $request->get('status'),
            'period_month' => $request->get('period_month'),
            'period_year' => $request->get('period_year'),
            'search' => $request->get('search'),
        ];

        $pdf = Pdf::loadView('exports.payments', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        $filename = 'Laporan_Iuran_' . date('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export to Excel format
     * 
     * @param \Illuminate\Support\Collection $payments
     * @param array $stats
     * @return \Illuminate\Http\JsonResponse
     */
    private function exportExcel($payments, $stats)
    {
        // TODO: Implementation for Excel export using Laravel Excel
        // composer require maatwebsite/excel
        return response()->json([
            'success' => false,
            'message' => 'Format Excel belum diimplementasikan. Silakan gunakan format PDF.'
        ], 501);
    }

    /**
     * Export to CSV format
     * 
     * @param \Illuminate\Support\Collection $payments
     * @param array $stats
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    private function exportCSV($payments, $stats)
    {
        $filename = 'Laporan_Iuran_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($payments, $stats) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Add summary information
            fputcsv($file, ['LAPORAN IURAN PERUMAHAN']);
            fputcsv($file, ['Dicetak:', Carbon::now()->locale('id')->isoFormat('dddd, D MMMM YYYY HH:mm')]);
            fputcsv($file, []);
            
            fputcsv($file, ['RINGKASAN']);
            fputcsv($file, ['Total Unit', $stats['total_unit']]);
            fputcsv($file, ['Sudah Lunas', $stats['lunas']]);
            fputcsv($file, ['Tertunggak', $stats['tertunggak']]);
            fputcsv($file, ['Total Terkumpul', 'Rp ' . number_format($stats['total_terkumpul'], 0, ',', '.')]);
            fputcsv($file, []);

            // Add header row
            fputcsv($file, [
                'No',
                'Blok/Unit',
                'Nama Penghuni',
                'Jenis Iuran',
                'Periode',
                'Nominal (Rp)',
                'Denda (Rp)',
                'Total (Rp)',
                'Status',
                'Tanggal Bayar',
                'Metode Pembayaran',
                'Nomor Kwitansi',
                'Keterangan'
            ]);

            // Add data rows
            $no = 1;
            foreach ($payments as $payment) {
                $periode = Carbon::create()->month($payment->period_month)->locale('id')->translatedFormat('F') . ' ' . $payment->period_year;
                $status = $this->getStatusLabel($payment->status);
                $paymentDate = $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d/m/Y') : '-';
                $paymentMethod = $payment->payment_method ? strtoupper($payment->payment_method) : '-';
                $total = $payment->amount + $payment->penalty_amount;

                fputcsv($file, [
                    $no++,
                    $payment->house_number,
                    $payment->resident->owner_name ?? '-',
                    $payment->fee->fee_name ?? '-',
                    $periode,
                    number_format($payment->amount, 0, ',', '.'),
                    number_format($payment->penalty_amount, 0, ',', '.'),
                    number_format($total, 0, ',', '.'),
                    $status,
                    $paymentDate,
                    $paymentMethod,
                    $payment->receipt_number ?? '-',
                    $payment->notes ?? '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get status label in Indonesian
     * 
     * @param string $status
     * @return string
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'paid' => 'Lunas',
            'pending' => 'Menunggu',
            'overdue' => 'Tertunggak',
            'cancelled' => 'Dibatalkan',
        ];

        return $labels[$status] ?? $status;
    }
}
