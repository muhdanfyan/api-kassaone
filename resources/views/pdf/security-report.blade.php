<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Keamanan</title>
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
            grid-template-columns: 1fr 1fr 1fr;
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
            background-color: #2196F3;
            color: white;
        }
        .text-center {
            text-align: center;
        }
        .type-patrol {
            background: #e3f2fd;
            color: #0d47a1;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .type-incident {
            background: #ffebee;
            color: #c62828;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .type-entry {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .type-exit {
            background: #fff3e0;
            color: #e65100;
            padding: 2px 5px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN KEAMANAN</h1>
        <p>Perumahan KASSAONE</p>
    </div>
    
    <div class="period">
        Periode: {{ date('d/m/Y', strtotime($period['start_date'])) }} - {{ date('d/m/Y', strtotime($period['end_date'])) }}
        ({{ $period['days'] }} hari)
    </div>
    
    <div class="summary">
        <h3>Ringkasan</h3>
        <div class="summary-grid">
            <div>Total Log: {{ $summary['total_logs'] }}</div>
            <div>Patroli: {{ $summary['patrols'] }}</div>
            <div>Insiden: {{ $summary['incidents'] }}</div>
            <div>Entry: {{ $summary['entries'] }}</div>
            <div>Exit: {{ $summary['exits'] }}</div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Tipe</th>
                <th>Lokasi</th>
                <th>Keterangan</th>
                <th>Petugas</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $index => $log)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ date('d/m/Y', strtotime($log->created_at)) }}</td>
                <td>{{ date('H:i', strtotime($log->created_at)) }}</td>
                <td class="text-center">
                    <span class="type-{{ $log->log_type }}">
                        {{ ucfirst($log->log_type) }}
                    </span>
                </td>
                <td>{{ $log->location ?? '-' }}</td>
                <td>{{ $log->description ?? '-' }}</td>
                <td>{{ $log->officer_name ?? '-' }}</td>
                <td>{{ ucfirst($log->status ?? 'completed') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 30px; text-align: right;">
        <p>Dicetak pada: {{ date('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
