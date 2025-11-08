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
        // Total anggota yang sudah verified
        $totalAnggota = Member::where('verification_status', Member::VERIFICATION_VERIFIED)->count();

        // Anggota baru bulan ini (yang created_at di bulan ini)
        $anggotaBaruBulanIni = Member::whereMonth('created_at', Carbon::now()->month)
                                    ->whereYear('created_at', Carbon::now()->year)
                                    ->count();

        // Total simpanan dari semua savings_accounts
        $totalSimpanan = SavingsAccount::sum('balance');

        // Assuming 'pembiayaan' (financing) is a type of transaction or a separate model
        // For now, let's assume it's a transaction type for simplicity
        $totalPembiayaan = Transaction::where('transaction_type', 'pembiayaan')->sum('amount');

        // SHU tahun berjalan
        $shuTahunBerjalan = ShuDistribution::where('fiscal_year', Carbon::now()->year)->sum('total_shu_amount');

        // Transaksi bulan ini - SEMUA transaksi (deposit & withdrawal dari pokok, wajib, sukarela)
        // Hitung dari tabel transactions berdasarkan transaction_date bulan ini
        $transaksiBulanIni = Transaction::whereMonth('transaction_date', Carbon::now()->month)
                                        ->whereYear('transaction_date', Carbon::now()->year)
                                        ->whereIn('transaction_type', ['deposit', 'withdrawal'])
                                        ->count();

        // Rapat yang terjadwal (meeting_date >= hari ini)
        $rapatTerjadwal = Meeting::where('meeting_date', '>=', Carbon::now())->count();

        return response()->json([
            'totalAnggota' => $totalAnggota,
            'anggotaBaruBulanIni' => $anggotaBaruBulanIni,
            'totalSimpanan' => $totalSimpanan,
            'totalPembiayaan' => $totalPembiayaan,
            'shuTahunBerjalan' => $shuTahunBerjalan ?? 0,
            'transaksiBulanIni' => $transaksiBulanIni,
            'rapatTerjadwal' => $rapatTerjadwal,
        ]);
    }
}
