<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Booking</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #1e40af; color: white; }
        h1 { color: #1e40af; }
        .status-pending { color: #f59e0b; }
        .status-approved { color: #22c55e; }
        .status-rejected { color: #ef4444; }
        .status-cancelled { color: #6b7280; }
        .status-completed { color: #6366f1; }
    </style>
</head>
<body>
    <h1>Laporan Pemesanan Ruangan</h1>
    <p>Periode: {{ request('start_date', 'Semua') }} - {{ request('end_date', 'Semua') }}</p>
    <p>Dicetak: {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Peminjam</th>
                <th>Kegiatan</th>
                <th>Ruangan</th>
                <th>Pemesan</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $index => $booking)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $booking->title }}</td>
                <td>{{ $booking->description ?? '-' }}</td>
                <td>{{ $booking->room->name ?? '-' }}</td>
                <td>{{ $booking->user->name ?? '-' }}</td>
                <td>{{ $booking->booking_date }}</td>
                <td>{{ $booking->start_time }} - {{ $booking->end_time }}</td>
                <td class="status-{{ $booking->status }}">{{ $booking->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
