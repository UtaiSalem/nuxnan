<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: garuda, sans-serif; }
        body { color: #1f2937; font-size: 11px; }
        .doc-title { font-size: 18px; font-weight: bold; text-align: center; margin: 0 0 2px; }
        .doc-subtitle { font-size: 13px; text-align: center; margin: 0 0 4px; color: #374151; }
        .meta { font-size: 10px; color: #6b7280; text-align: center; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        thead { background-color: #eef2ff; }
        th, td { border: 0.5px solid #cbd5e1; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { font-weight: bold; font-size: 11px; }
        td { font-size: 10px; }
        .col-no { width: 40px; text-align: center; }
        .col-class { width: 70px; }
        .col-status { width: 60px; text-align: center; }
        .col-time { width: 56px; text-align: center; }
        .status-present { color: #059669; }
        .status-late { color: #d97706; }
        .status-absent { color: #dc2626; }
        .status-leave { color: #2563eb; }
        .empty { text-align: center; color: #9ca3af; padding: 20px; }
    </style>
</head>
<body>
    <div class="doc-title">{{ $academyName }}</div>
    <div class="doc-subtitle">{{ $title }} — วันที่ {{ $date }}</div>
    <div class="meta">พิมพ์เมื่อ {{ $generatedAt }} &nbsp;|&nbsp; จำนวน {{ count($rows) }} คน</div>

    <table>
        <thead>
            <tr>
                <th class="col-no">เลขที่</th>
                <th>ชื่อ-นามสกุล</th>
                <th class="col-class">ชั้นเรียน</th>
                <th class="col-status">สถานะ</th>
                <th class="col-time">เวลา</th>
                <th>หมายเหตุ</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="col-no">{{ $row['student_number'] }}</td>
                    <td>{{ $row['student_name'] }}</td>
                    <td class="col-class">{{ $row['classroom'] }}</td>
                    <td class="col-status">{{ $row['status_label'] }}</td>
                    <td class="col-time">{{ $row['checked_in_at'] }}</td>
                    <td>{{ $row['remarks'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">ยังไม่มีการเช็คชื่อในคาบนี้</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
