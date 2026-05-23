<!DOCTYPE html>
<html>
<head>
    <title>Laporan Stok Rendah</title>
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
        .badge { padding: 3px 6px; border-radius: 3px; font-size: 12px; }
        .badge-active { background-color: #d4edda; color: #155724; }
        .badge-inactive { background-color: #f8d7da; color: #721c24; }
        .footer { font-size: 12px; color: #777; margin-top: 30px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Stok Rendah</h2>
    </div>

    <div class="info">
        <p><strong>Ambang Batas (Threshold):</strong> Stok &le; {{ $threshold }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Produk</th>
                <th class="text-right">Sisa Stok</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                <tr>
                    <td>{{ $product->product_id }}</td>
                    <td>{{ $product->name }}</td>
                    <td class="text-right">{{ $product->stock }}</td>
                    <td class="text-center">
                        @if($product->status === 'available')
                            <span class="badge badge-active">available</span>
                        @else
                            <span class="badge badge-inactive">unavailable</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Tidak ada produk dengan stok di bawah atau sama dengan threshold.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dibuat pada: {{ \Carbon\Carbon::now()->format('d M Y H:i:s') }}
    </div>
</body>
</html>
