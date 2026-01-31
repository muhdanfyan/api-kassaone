<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Layanan</title>
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
        .category-summary {
            margin-bottom: 20px;
        }
        .category-summary table {
            width: 50%;
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
            background-color: #FF9800;
            color: white;
        }
        .text-center {
            text-align: center;
        }
        .priority-high {
            background: #ffebee;
            color: #c62828;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .priority-medium {
            background: #fff3e0;
            color: #e65100;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .priority-low {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .status-resolved, .status-closed {
            background: #d4edda;
            color: #155724;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .status-in_progress {
            background: #cce5ff;
            color: #004085;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .status-pending, .status-submitted, .status-acknowledged {
            background: #fff3cd;
            color: #856404;
            padding: 2px 5px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN LAYANAN</h1>
        <p>Perumahan KASSAONE</p>
    </div>
    
    <div class="period">
        Periode: {{ date('d/m/Y', strtotime($period['start_date'])) }} - {{ date('d/m/Y', strtotime($period['end_date'])) }}
        ({{ $period['days'] }} hari)
    </div>
    
    <div class="summary">
        <h3>Ringkasan</h3>
        <div class="summary-grid">
            <div>Total Request: {{ $summary['total_requests'] }}</div>
            <div>Resolved: {{ $summary['resolved'] }}</div>
            <div>In Progress: {{ $summary['in_progress'] }}</div>
            <div>Pending: {{ $summary['pending'] }}</div>
            <div>Avg Resolution: {{ $summary['avg_resolution_days'] }} hari</div>
        </div>
    </div>
    
    <div class="category-summary">
        <h3>Per Kategori</h3>
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Resolved</th>
                </tr>
            </thead>
            <tbody>
                @foreach($by_category as $cat)
                <tr>
                    <td>{{ ucfirst($cat->category) }}</td>
                    <td class="text-center">{{ $cat->count }}</td>
                    <td class="text-center">{{ $cat->resolved }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <h3>Detail Layanan</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tiket</th>
                <th>Tanggal</th>
                <th>Unit</th>
                <th>Pelapor</th>
                <th>Kategori</th>
                <th>Judul</th>
                <th>Prioritas</th>
                <th>Status</th>
                <th>Petugas</th>
                <th>Waktu (hari)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $index => $service)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $service->ticket_number }}</td>
                <td>{{ date('d/m/Y', strtotime($service->created_at)) }}</td>
                <td>{{ $service->house_number }}</td>
                <td>{{ $service->reporter_name }}</td>
                <td>{{ ucfirst($service->category) }}</td>
                <td>{{ $service->title }}</td>
                <td class="text-center">
                    <span class="priority-{{ $service->priority }}">
                        {{ ucfirst($service->priority) }}
                    </span>
                </td>
                <td class="text-center">
                    <span class="status-{{ $service->status }}">
                        {{ ucfirst(str_replace('_', ' ', $service->status)) }}
                    </span>
                </td>
                <td>{{ $service->assigned_to ?? '-' }}</td>
                <td class="text-center">{{ $service->resolution_time_days ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 30px; text-align: right;">
        <p>Dicetak pada: {{ date('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
