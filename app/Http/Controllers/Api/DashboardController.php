<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\Transaction;
use App\Models\Meeting;
use App\Models\ShuDistribution;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats()
    {
        $totalAnggota = Member::count();

        $anggotaBaruBulanIni = Member::whereMonth('created_at', Carbon::now()->month)
                                    ->whereYear('created_at', Carbon::now()->year)
                                    ->count();

        $totalSimpanan = SavingsAccount::sum('balance');

        // Assuming 'pembiayaan' (financing) is a type of transaction or a separate model
        // For now, let's assume it's a transaction type for simplicity
        $totalPembiayaan = Transaction::where('transaction_type', 'pembiayaan')->sum('amount');

        $shuTahunBerjalan = ShuDistribution::where('fiscal_year', Carbon::now()->year)->sum('total_shu_amount');

        $transaksiBulanIni = Transaction::whereMonth('transaction_date', Carbon::now()->month)
                                        ->whereYear('transaction_date', Carbon::now()->year)
                                        ->count();

        $rapatTerjadwal = Meeting::where('meeting_date', '>=', Carbon::now())->count();

        return response()->json([
            'totalAnggota' => $totalAnggota,
            'anggotaBaruBulanIni' => $anggotaBaruBulanIni,
            'totalSimpanan' => $totalSimpanan,
            'totalPembiayaan' => $totalPembiayaan,
            'shuTahunBerjalan' => $shuTahunBerjalan,
            'transaksiBulanIni' => $transaksiBulanIni,
            'rapatTerjadwal' => $rapatTerjadwal,
        ]);
    }
}
