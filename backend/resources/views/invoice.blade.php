<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $payment->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>SistemKlinik</h2>
        <p>Invoice Pembayaran</p>
    </div>

    <p><strong>Nomor Invoice:</strong> {{ $payment->invoice_number }}</p>
    <p><strong>Tanggal:</strong> {{ $payment->created_at->format('d/m/Y H:i') }}</p>
    <p><strong>Pasien:</strong> {{ $visit->patient->user->name }}</p>
    <p><strong>Dokter:</strong> {{ $visit->doctor->user->name }}</p>
    <p><strong>Poli:</strong> {{ $visit->polyclinic->name }}</p>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Biaya Konsultasi</td>
                <td class="right">1</td>
                <td class="right">Rp {{ number_format($bill['consultation_fee'], 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($bill['consultation_fee'], 0, ',', '.') }}</td>
            </tr>
            @foreach($bill['medicines'] as $m)
            <tr>
                <td>{{ $m['medicine'] }}</td>
                <td class="right">{{ $m['quantity'] }}</td>
                <td class="right">Rp {{ number_format($m['price'], 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($m['subtotal'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total">
                <td colspan="3" class="right">Total</td>
                <td class="right">Rp {{ number_format($bill['total'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top: 20px;">Status: {{ $payment->status }}</p>
</body>
</html>