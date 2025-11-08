<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class GeneralTransactionController extends Controller
{
    /**
     * Get all general transactions
     */
    public function index()
    {
        $transactions = Transaction::with(['savingsAccount', 'member'])
            ->orderBy('transaction_date', 'desc')
            ->get();

        return response()->json($transactions);
    }

    /**
     * Get transaction chart data
     */
    public function chart()
    {
        // Get transaction data grouped by month for the current year
        $chartData = Transaction::select(
            DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
            DB::raw('SUM(CASE WHEN transaction_type = "Setoran" THEN amount ELSE 0 END) as total_setoran'),
            DB::raw('SUM(CASE WHEN transaction_type = "Penarikan" THEN amount ELSE 0 END) as total_penarikan'),
            DB::raw('SUM(CASE WHEN transaction_type = "Transfer" THEN amount ELSE 0 END) as total_transfer')
        )
        ->whereYear('transaction_date', date('Y'))
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        // Format data for chart
        $labels = [];
        $setoranData = [];
        $penarikanData = [];
        $transferData = [];

        foreach ($chartData as $data) {
            $labels[] = date('M Y', strtotime($data->month . '-01'));
            $setoranData[] = (float) $data->total_setoran;
            $penarikanData[] = (float) $data->total_penarikan;
            $transferData[] = (float) $data->total_transfer;
        }

        return response()->json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Setoran',
                    'data' => $setoranData,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.6)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                ],
                [
                    'label' => 'Penarikan',
                    'data' => $penarikanData,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.6)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                ],
                [
                    'label' => 'Transfer',
                    'data' => $transferData,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                ],
            ],
        ]);
    }
}
