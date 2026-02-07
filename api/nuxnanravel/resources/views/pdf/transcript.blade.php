<!DOCTYPE html>
<html lang="th">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>ใบแสดงผลการเรียน</title>
    <style>
        @font-face {
            font-family: 'THSarabun';
            src: url('{{ storage_path("fonts/THSarabunNew.ttf") }}') format("truetype");
            font-weight: normal;
            font-style: normal;
        }
        
        * {
            font-family: 'THSarabun', sans-serif;
        }
        
        body {
            font-size: 14px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 22px;
            margin: 0 0 5px 0;
            font-weight: bold;
        }
        
        .header h2 {
            font-size: 18px;
            margin: 0 0 5px 0;
        }
        
        .header p {
            margin: 2px 0;
            font-size: 14px;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            margin-bottom: 10px;
        }
        
        .student-info {
            margin-bottom: 20px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        
        .student-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .student-info td {
            padding: 5px;
            vertical-align: top;
        }
        
        .student-info .label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .grades-table th {
            background: #2563eb;
            color: white;
            padding: 10px 8px;
            text-align: center;
            font-size: 13px;
            border: 1px solid #1d4ed8;
        }
        
        .grades-table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        
        .grades-table td.left {
            text-align: left;
        }
        
        .grades-table tr:nth-child(even) {
            background: #f5f5f5;
        }
        
        .grades-table tr:hover {
            background: #e8f4ff;
        }
        
        .summary-box {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        
        .summary-item {
            text-align: center;
            padding: 15px;
            background: #f0f9ff;
            border: 2px solid #2563eb;
            border-radius: 8px;
            min-width: 120px;
        }
        
        .summary-item .value {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }
        
        .summary-item .label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            text-align: center;
            width: 30%;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .ranking-badge {
            display: inline-block;
            background: #fbbf24;
            color: #92400e;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .grade-highlight {
            font-weight: bold;
        }
        
        .grade-a { color: #16a34a; }
        .grade-b { color: #2563eb; }
        .grade-c { color: #f59e0b; }
        .grade-d { color: #ea580c; }
        .grade-f { color: #dc2626; }
        
        .print-date {
            text-align: right;
            font-size: 11px;
            color: #888;
            margin-bottom: 10px;
        }

        @media print {
            body {
                margin: 0;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="print-date">
            วันที่พิมพ์: {{ now()->format('d/m/Y H:i') }}
        </div>
        
        <div class="header">
            @if($transcript->academy->logo)
                <img src="{{ $transcript->academy->logo }}" alt="Logo" class="logo">
            @endif
            <h1>{{ $transcript->academy->name_th ?? $transcript->academy->name }}</h1>
            <h2>ใบแสดงผลการเรียน (Transcript)</h2>
            <p>ภาคเรียนที่ {{ $transcript->semester->semester_number }} ปีการศึกษา {{ $transcript->semester->academicYear->name }}</p>
        </div>
        
        <div class="student-info">
            <table>
                <tr>
                    <td class="label">รหัสนักเรียน:</td>
                    <td>{{ $student->student_id }}</td>
                    <td class="label">ชั้น:</td>
                    <td>{{ $transcript->classroom?->name ?? ($student->class_level . '/' . $student->class_section) }}</td>
                </tr>
                <tr>
                    <td class="label">ชื่อ-นามสกุล:</td>
                    <td colspan="3">{{ $student->prefix }}{{ $student->first_name_th }} {{ $student->last_name_th }}</td>
                </tr>
                <tr>
                    <td class="label">Name:</td>
                    <td colspan="3">{{ $student->first_name_en }} {{ $student->last_name_en }}</td>
                </tr>
                <tr>
                    <td class="label">เลขประจำตัวประชาชน:</td>
                    <td>{{ $student->citizen_id ? substr($student->citizen_id, 0, 1).'-'.substr($student->citizen_id, 1, 4).'-'.substr($student->citizen_id, 5, 5).'-'.substr($student->citizen_id, 10, 2).'-'.substr($student->citizen_id, 12, 1) : '-' }}</td>
                    <td class="label">เลขที่:</td>
                    <td>{{ $transcript->student_number ?? '-' }}</td>
                </tr>
            </table>
        </div>
        
        <table class="grades-table">
            <thead>
                <tr>
                    <th style="width: 50px;">ลำดับ</th>
                    <th style="width: 80px;">รหัสวิชา</th>
                    <th class="left" style="width: 200px;">ชื่อรายวิชา</th>
                    <th style="width: 60px;">หน่วยกิต</th>
                    <th style="width: 80px;">คะแนน</th>
                    <th style="width: 50px;">เกรด</th>
                    <th style="width: 60px;">คะแนนเกรด</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transcript->items as $index => $item)
                    @php
                        $gradeClass = 'grade-c';
                        if ($item->letter_grade == 'A' || $item->grade_points == 4) {
                            $gradeClass = 'grade-a';
                        } elseif ($item->letter_grade == 'B' || $item->grade_points >= 3) {
                            $gradeClass = 'grade-b';
                        } elseif ($item->letter_grade == 'D' || $item->grade_points < 2) {
                            $gradeClass = 'grade-d';
                        } elseif ($item->letter_grade == 'F' || $item->grade_points == 0) {
                            $gradeClass = 'grade-f';
                        }
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->course?->code ?? $item->subject_code }}</td>
                        <td class="left">{{ $item->course?->name ?? $item->subject_name }}</td>
                        <td>{{ number_format($item->credits, 1) }}</td>
                        <td>{{ $item->percentage ? number_format($item->percentage, 1) . '%' : '-' }}</td>
                        <td class="grade-highlight {{ $gradeClass }}">{{ $item->letter_grade }}</td>
                        <td>{{ number_format($item->grade_points, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px;">ไม่มีข้อมูลผลการเรียน</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background: #f0f9ff; font-weight: bold;">
                    <td colspan="3" style="text-align: right;">รวม</td>
                    <td>{{ number_format($transcript->total_credits, 1) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
        
        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
            <div class="summary-item">
                <div class="value">{{ number_format($transcript->gpa, 2) }}</div>
                <div class="label">GPA (เกรดเฉลี่ย)</div>
            </div>
            
            <div class="summary-item">
                <div class="value">{{ number_format($transcript->total_credits, 1) }}</div>
                <div class="label">หน่วยกิตรวม</div>
            </div>
            
            @if($transcript->class_rank)
            <div class="summary-item">
                <div class="value">{{ $transcript->class_rank }}/{{ $transcript->class_total }}</div>
                <div class="label">อันดับในห้อง</div>
            </div>
            @endif
            
            @if($transcript->grade_rank)
            <div class="summary-item">
                <div class="value">{{ $transcript->grade_rank }}/{{ $transcript->grade_total }}</div>
                <div class="label">อันดับในชั้นปี</div>
            </div>
            @endif
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">ครูประจำชั้น</div>
                <p style="font-size: 11px; color: #666;">
                    {{ $transcript->classroom?->homeroomTeacher?->name ?? '____________________' }}
                </p>
            </div>
            
            <div class="signature-box">
                <div class="signature-line">หัวหน้าฝ่ายวิชาการ</div>
                <p style="font-size: 11px; color: #666;">
                    ____________________
                </p>
            </div>
            
            <div class="signature-box">
                <div class="signature-line">ผู้อำนวยการ</div>
                <p style="font-size: 11px; color: #666;">
                    {{ $transcript->approver?->name ?? '____________________' }}
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p>เอกสารนี้ออกโดยระบบ {{ config('app.name') }} | รหัสเอกสาร: {{ $transcript->id }}-{{ date('YmdHis') }}</p>
            <p>{{ $transcript->academy->address ?? '' }}</p>
        </div>
    </div>
</body>
</html>
