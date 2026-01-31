<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Iuran Bulanan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .period {
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .summary {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .status-paid {
            background: #d4edda;
            color: #155724;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .status-overdue {
            background: #f8d7da;
            color: #721c24;
            padding: 2px 5px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN IURAN BULANAN</h1>
        <p>Perumahan KASSAONE</p>
    </div>
    
    <div class="period">
        Periode: {{ $period['month_name'] }} {{ $period['year'] }}
    </div>
    
    <div class="summary">
        <h3>Ringkasan</h3>
        <div class="summary-grid">
            <div>Total Penghuni: {{ $summary['total_residents'] }}</div>
            <div>Total Tagihan: Rp {{ number_format($summary['total_billed'], 0, ',', '.') }}</div>
            <div>Total Terkumpul: Rp {{ number_format($summary['total_collected'], 0, ',', '.') }}</div>
            <div>Total Tertunggak: Rp {{ number_format($summary['total_outstanding'], 0, ',', '.') }}</div>
            <div colspan="2">Tingkat Kepatuhan: {{ number_format($summary['collection_rate'], 2) }}%</div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Unit</th>
                <th>Pemilik</th>
                <th>Jenis Iuran</th>
                <th class="text-right">Jumlah</th>
                <th class="text-center">Status</th>
                <th>Tanggal Bayar</th>
                <th>Metode</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $index => $payment)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $payment->house_number }}</td>
                <td>{{ $payment->owner_name }}</td>
                <td>{{ $payment->fee_name }}</td>
                <td class="text-right">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                <td class="text-center">
                    <span class="status-{{ $payment->status }}">
                        {{ ucfirst($payment->status) }}
                    </span>
                </td>
                <td>{{ $payment->payment_date ? date('d/m/Y', strtotime($payment->payment_date)) : '-' }}</td>
                <td>{{ ucfirst($payment->payment_method ?? '-') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 30px; text-align: right;">
        <p>Dicetak pada: {{ date('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
