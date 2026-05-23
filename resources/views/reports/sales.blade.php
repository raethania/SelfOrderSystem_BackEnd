<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 20px; }
        .info { margin-bottom: 15px; }
        .info p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-box { background: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin-bottom: 20px; }
        .footer { font-size: 12px; color: #777; margin-top: 30px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Penjualan</h2>
    </div>

    <div class="info">
        <p><strong>Periode:</strong> {{ $startDate }} sampai {{ $endDate }}</p>
        <p><strong>Pengelompokan:</strong> {{ ucfirst($groupBy) }}</p>
    </div>

    <div class="summary-box">
        <strong>Ringkasan:</strong><br>
        Total Pendapatan: Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}<br>
        Total Pesanan: {{ number_format($summary['total_orders'], 0, ',', '.') }}<br>
        Rata-rata Nilai Pesanan: Rp {{ number_format($summary['avg_order_value'], 0, ',', '.') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th class="text-right">Pesanan</th>
                <th class="text-right">Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($breakdown as $item)
                <tr>
                    <td>{{ $item->date }}</td>
                    <td class="text-right">{{ number_format($item->orders, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->revenue, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">Tidak ada data penjualan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dibuat pada: {{ \Carbon\Carbon::now()->format('d M Y H:i:s') }}
    </div>
</body>
</html>
