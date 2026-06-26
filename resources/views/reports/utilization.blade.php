<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Utilisasi Ruangan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #1e40af; color: white; }
        h1 { color: #1e40af; }
    </style>
</head>
<body>
    <h1>Laporan Utilisasi Ruangan</h1>
    <p>Periode: {{ request('start_date', 'Semua') }} - {{ request('end_date', 'Semua') }}</p>
    <p>Dicetak: {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Ruangan</th>
                <th>Kapasitas</th>
                <th>Total Booking</th>
                <th>Menit Terpakai</th>
                <th>Utilisasi (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rooms as $index => $room)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $room['room_name'] }}</td>
                <td>{{ $room['capacity'] }}</td>
                <td>{{ $room['total_bookings'] }}</td>
                <td>{{ $room['booked_minutes'] }}</td>
                <td>{{ $room['utilization_percentage'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
