<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: garuda, sans-serif; }
        body { color: #1f2937; font-size: 11px; }
        .doc-title { font-size: 18px; font-weight: bold; text-align: center; margin: 0 0 2px; }
        .doc-subtitle { font-size: 13px; text-align: center; margin: 0 0 8px; color: #374151; }
        .meta { font-size: 10px; color: #6b7280; text-align: center; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        thead { background-color: #eef2ff; }
        th, td { border: 0.5px solid #cbd5e1; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { font-weight: bold; font-size: 11px; }
        td { font-size: 10px; }
        .col-no { width: 26px; text-align: center; }
        .col-date { width: 62px; }
        .col-class { width: 60px; }
        .col-status { width: 62px; text-align: center; }
        .status-completed { color: #059669; }
        .status-pending { color: #d97706; }
        .status-cancelled { color: #dc2626; }
        .empty { text-align: center; color: #9ca3af; padding: 20px; }
    </style>
</head>
<body>
    <div class="doc-title">{{ $academyName }}</div>
    <div class="doc-subtitle">รายงานการเยี่ยมบ้านนักเรียน</div>
    <div class="meta">
        พิมพ์เมื่อ {{ $generatedAt }} &nbsp;|&nbsp; จำนวน {{ count($rows) }} รายการ
        @if ($filterSummary)
            &nbsp;|&nbsp; ตัวกรอง: {{ $filterSummary }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-no">#</th>
                <th class="col-date">วันที่เยี่ยม</th>
                <th>ชื่อ-นามสกุลนักเรียน</th>
                <th class="col-class">ชั้นเรียน</th>
                <th>ครูผู้เยี่ยม</th>
                <th class="col-status">สถานะ</th>
                <th>หัวข้อการเยี่ยม</th>
                <th>สรุปผลการเยี่ยม</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $row)
                <tr>
                    <td class="col-no">{{ $i + 1 }}</td>
                    <td class="col-date">{{ $row['visit_date'] }}</td>
                    <td>{{ $row['student_name'] }}</td>
                    <td class="col-class">{{ $row['classroom'] }}</td>
                    <td>{{ $row['visitor_name'] }}</td>
                    <td class="col-status status-{{ $row['status_key'] }}">{{ $row['status_label'] }}</td>
                    <td>{{ $row['notes'] }}</td>
                    <td>{{ $row['observations'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">ไม่พบข้อมูลการเยี่ยมบ้าน</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
