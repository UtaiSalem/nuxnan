<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: garuda, sans-serif; font-size: 11px; }
        h2 { font-size: 15px; margin: 0 0 4px; }
        .meta { color: #555; font-size: 10px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
        th { background: #eee; }
        .empty { color: #999; padding: 12px; text-align: center; }
    </style>
</head>
<body>
    <h2>{{ $report->name }}</h2>
    <div class="meta">สร้างเมื่อ {{ optional($report->generated_at)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</div>

    @if (empty($rows))
        <div class="empty">ไม่มีข้อมูล</div>
    @else
        <table>
            <thead>
                <tr>@foreach ($headings as $h)<th>{{ $h }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>@foreach ($headings as $h)<td>{{ is_scalar($row[$h] ?? '') ? ($row[$h] ?? '') : json_encode($row[$h] ?? '', JSON_UNESCAPED_UNICODE) }}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
