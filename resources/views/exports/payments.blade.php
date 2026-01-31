<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Iuran Perumahan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            padding: 15px;
            color: #1f2937;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 10px;
        }
        
        .header h1 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 8px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        .header .subtitle {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        
        .header .filter-info {
            font-size: 9px;
            color: #4b5563;
            background: #fef3c7;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 5px;
        }
        
        .summary {
            background: #fef3c7;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #f59e0b;
        }
        
        .summary h3 {
            font-size: 12px;
            margin-bottom: 10px;
            color: #92400e;
            font-weight: bold;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
        }
        
        .summary-row {
            display: table-row;
        }
        
        .summary-item {
            display: table-cell;
            background: white;
            padding: 8px 10px;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
            width: 25%;
            vertical-align: top;
        }
        
        .summary-item + .summary-item {
            padding-left: 15px;
        }
        
        .summary-item .label {
            display: block;
            font-size: 8px;
            color: #6b7280;
            margin-bottom: 4px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .summary-item .value {
            display: block;
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
        }
        
        .summary-item.highlight .value {
            color: #f59e0b;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8px;
        }
        
        thead {
            background: #f59e0b;
            color: white;
        }
        
        th {
            padding: 8px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 8px;
            border: 1px solid #d97706;
        }
        
        td {
            padding: 6px 4px;
            border: 1px solid #e5e7eb;
            font-size: 7.5px;
        }
        
        tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        tbody tr:hover {
            background: #fef3c7;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .badge-secondary {
            background: #f3f4f6;
            color: #4b5563;
            border: 1px solid #d1d5db;
        }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 7px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .no-wrap {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN IURAN PERUMAHAN</h1>
        <div class="subtitle">Dicetak pada: {{ $generated_at }}</div>
        
        @if($filter_status || $period_month || $period_year || $search)
            <div class="filter-info">
                <strong>Filter Aktif:</strong>
                @if($filter_status)
                    Status: <strong>{{ ucfirst($filter_status) }}</strong>
                @endif
                @if($period_month && $period_year)
                    | Periode: <strong>{{ \Carbon\Carbon::create()->month($period_month)->locale('id')->translatedFormat('F') }} {{ $period_year }}</strong>
                @elseif($period_year)
                    | Tahun: <strong>{{ $period_year }}</strong>
                @endif
                @if($search)
                    | Pencarian: <strong>{{ $search }}</strong>
                @endif
            </div>
        @endif
    </div>
    
    <div class="summary">
        <h3>📊 RINGKASAN LAPORAN</h3>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-item">
                    <span class="label">Total Unit</span>
                    <span class="value">{{ $stats['total_unit'] }}</span>
                </div>
                <div class="summary-item">
                    <span class="label">Sudah Lunas</span>
                    <span class="value" style="color: #16a34a;">{{ $stats['lunas'] }}</span>
                </div>
                <div class="summary-item">
                    <span class="label">Tertunggak</span>
                    <span class="value" style="color: #dc2626;">{{ $stats['tertunggak'] }}</span>
                </div>
                <div class="summary-item highlight">
                    <span class="label">Total Terkumpul</span>
                    <span class="value">Rp {{ number_format($stats['total_terkumpul'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 7%;">Blok/Unit</th>
                <th style="width: 15%;">Nama Penghuni</th>
                <th style="width: 12%;">Jenis Iuran</th>
                <th style="width: 10%;">Periode</th>
                <th class="text-right" style="width: 10%;">Nominal</th>
                <th class="text-right" style="width: 8%;">Denda</th>
                <th class="text-right" style="width: 10%;">Total</th>
                <th class="text-center" style="width: 8%;">Status</th>
                <th class="text-center" style="width: 9%;">Tgl Bayar</th>
                <th class="text-center" style="width: 8%;">Metode</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $index => $payment)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $payment->house_number }}</strong></td>
                <td>{{ $payment->resident->owner_name ?? '-' }}</td>
                <td>{{ $payment->fee->fee_name ?? '-' }}</td>
                <td class="no-wrap">
                    {{ \Carbon\Carbon::create()->month($payment->period_month)->locale('id')->translatedFormat('M') }}
                    {{ $payment->period_year }}
                </td>
                <td class="text-right">
                    <strong>{{ number_format($payment->amount, 0, ',', '.') }}</strong>
                </td>
                <td class="text-right">
                    @if($payment->penalty_amount > 0)
                        <span style="color: #dc2626;">{{ number_format($payment->penalty_amount, 0, ',', '.') }}</span>
                    @else
                        -
                    @endif
                </td>
                <td class="text-right">
                    <strong>{{ number_format($payment->amount + $payment->penalty_amount, 0, ',', '.') }}</strong>
                </td>
                <td class="text-center">
                    @php
                        $badgeClass = 'badge-warning';
                        $statusLabel = 'Menunggu';
                        
                        if ($payment->status === 'paid') {
                            $badgeClass = 'badge-success';
                            $statusLabel = 'Lunas';
                        } elseif ($payment->status === 'overdue') {
                            $badgeClass = 'badge-danger';
                            $statusLabel = 'Tunggak';
                        } elseif ($payment->status === 'cancelled') {
                            $badgeClass = 'badge-secondary';
                            $statusLabel = 'Batal';
                        }
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                </td>
                <td class="text-center no-wrap">
                    {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : '-' }}
                </td>
                <td class="text-center">
                    @if($payment->payment_method)
                        <span style="font-weight: 600;">{{ strtoupper($payment->payment_method) }}</span>
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    @if($payments->count() === 0)
        <div style="text-align: center; padding: 30px; color: #6b7280;">
            <p>Tidak ada data untuk ditampilkan</p>
        </div>
    @endif
    
    <div class="footer">
        <p><strong>KASSAONE - Sistem Manajemen Perumahan</strong></p>
        <p>Dokumen ini digenerate secara otomatis oleh sistem pada {{ $generated_at }}</p>
        <p style="margin-top: 5px; font-style: italic;">Halaman ini adalah dokumen resmi dan sah sebagai laporan iuran perumahan</p>
    </div>
</body>
</html>
