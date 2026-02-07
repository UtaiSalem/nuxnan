<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - {{ $certificate->certificate_number }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Sarabun', 'TH Sarabun New', sans-serif;
            background: #fff;
            color: #1a1a1a;
        }
        
        .certificate-container {
            width: 297mm;
            height: 210mm;
            padding: 15mm;
            position: relative;
            background: linear-gradient(135deg, #fefefe 0%, #f8f9fa 100%);
        }
        
        .certificate-inner {
            width: 100%;
            height: 100%;
            border: 3px solid #c9a227;
            padding: 20mm;
            position: relative;
            background: #fff;
        }
        
        .certificate-inner::before {
            content: '';
            position: absolute;
            top: 5mm;
            left: 5mm;
            right: 5mm;
            bottom: 5mm;
            border: 1px solid #c9a227;
            pointer-events: none;
        }
        
        /* Corner decorations */
        .corner {
            position: absolute;
            width: 30mm;
            height: 30mm;
            border: 2px solid #c9a227;
        }
        
        .corner-tl { top: 10mm; left: 10mm; border-right: none; border-bottom: none; }
        .corner-tr { top: 10mm; right: 10mm; border-left: none; border-bottom: none; }
        .corner-bl { bottom: 10mm; left: 10mm; border-right: none; border-top: none; }
        .corner-br { bottom: 10mm; right: 10mm; border-left: none; border-top: none; }
        
        .header {
            text-align: center;
            margin-bottom: 10mm;
        }
        
        .logo {
            width: 25mm;
            height: 25mm;
            margin: 0 auto 5mm;
        }
        
        .logo img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .organization-name {
            font-size: 14pt;
            color: #666;
            margin-bottom: 3mm;
        }
        
        .certificate-title {
            font-size: 32pt;
            font-weight: bold;
            color: #c9a227;
            margin-bottom: 2mm;
            text-transform: uppercase;
            letter-spacing: 3mm;
        }
        
        .certificate-subtitle {
            font-size: 16pt;
            color: #444;
        }
        
        .body {
            text-align: center;
            margin: 15mm 0;
        }
        
        .presented-to {
            font-size: 14pt;
            color: #666;
            margin-bottom: 5mm;
        }
        
        .student-name {
            font-size: 28pt;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 5mm;
            border-bottom: 2px solid #c9a227;
            display: inline-block;
            padding: 0 20mm 3mm;
        }
        
        .achievement-text {
            font-size: 14pt;
            color: #444;
            line-height: 1.8;
            max-width: 180mm;
            margin: 0 auto;
        }
        
        .course-title {
            font-size: 18pt;
            font-weight: bold;
            color: #1a1a1a;
            margin: 5mm 0;
        }
        
        .grade-section {
            margin: 10mm 0;
        }
        
        .grade-label {
            font-size: 12pt;
            color: #666;
        }
        
        .grade-value {
            font-size: 24pt;
            font-weight: bold;
            color: #c9a227;
        }
        
        .footer {
            position: absolute;
            bottom: 25mm;
            left: 25mm;
            right: 25mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .signature-section {
            text-align: center;
            width: 60mm;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            margin-bottom: 2mm;
            height: 10mm;
        }
        
        .signature-name {
            font-size: 12pt;
            font-weight: bold;
        }
        
        .signature-title {
            font-size: 10pt;
            color: #666;
        }
        
        .date-section {
            text-align: center;
        }
        
        .date-label {
            font-size: 10pt;
            color: #666;
        }
        
        .date-value {
            font-size: 12pt;
            font-weight: bold;
        }
        
        .qr-section {
            text-align: center;
            width: 30mm;
        }
        
        .qr-code {
            width: 25mm;
            height: 25mm;
            border: 1px solid #ddd;
            margin-bottom: 2mm;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8pt;
            color: #999;
        }
        
        .certificate-number {
            font-size: 8pt;
            color: #999;
        }
        
        .verification-text {
            font-size: 7pt;
            color: #999;
        }
        
        /* Type-specific styles */
        .type-excellence .certificate-title {
            color: #d4af37;
        }
        
        .type-excellence .certificate-inner {
            border-color: #d4af37;
        }
        
        .type-achievement .certificate-title {
            color: #c0c0c0;
        }
        
        .type-achievement .certificate-inner {
            border-color: #a0a0a0;
        }
        
        .type-participation .certificate-title {
            color: #cd7f32;
        }
        
        .type-participation .certificate-inner {
            border-color: #cd7f32;
        }
    </style>
</head>
<body>
    <div class="certificate-container type-{{ $certificate->type }}">
        <div class="certificate-inner">
            <!-- Corner decorations -->
            <div class="corner corner-tl"></div>
            <div class="corner corner-tr"></div>
            <div class="corner corner-bl"></div>
            <div class="corner corner-br"></div>
            
            <!-- Header -->
            <div class="header">
                <div class="logo">
                    <img src="{{ public_path('images/logo.png') }}" alt="Logo" onerror="this.style.display='none'">
                </div>
                <div class="organization-name">Nuxnan Academy</div>
                <div class="certificate-title">
                    @switch($certificate->type)
                        @case('completion')
                            Certificate of Completion
                            @break
                        @case('excellence')
                            Certificate of Excellence
                            @break
                        @case('achievement')
                            Certificate of Achievement
                            @break
                        @case('participation')
                            Certificate of Participation
                            @break
                    @endswitch
                </div>
                <div class="certificate-subtitle">
                    @switch($certificate->type)
                        @case('completion')
                            ประกาศนียบัตรจบวิชา
                            @break
                        @case('excellence')
                            ประกาศนียบัตรความเป็นเลิศ
                            @break
                        @case('achievement')
                            ประกาศนียบัตรเกียรติคุณ
                            @break
                        @case('participation')
                            ประกาศนียบัตรการเข้าร่วม
                            @break
                    @endswitch
                </div>
            </div>
            
            <!-- Body -->
            <div class="body">
                <div class="presented-to">This is to certify that / ขอมอบให้แก่</div>
                <div class="student-name">{{ $certificate->student_name }}</div>
                
                <div class="achievement-text">
                    @switch($certificate->type)
                        @case('completion')
                            has successfully completed the course
                            <br>ได้ศึกษาจนจบหลักสูตร
                            @break
                        @case('excellence')
                            has demonstrated outstanding excellence in
                            <br>ได้แสดงผลงานอันยอดเยี่ยมใน
                            @break
                        @case('achievement')
                            has achieved remarkable performance in
                            <br>ได้มีผลการเรียนดีเด่นใน
                            @break
                        @case('participation')
                            has actively participated in
                            <br>ได้เข้าร่วมศึกษาใน
                            @break
                    @endswitch
                </div>
                
                <div class="course-title">"{{ $certificate->course_title }}"</div>
                
                @if($certificate->grade)
                <div class="grade-section">
                    <span class="grade-label">with Grade / เกรด</span>
                    <span class="grade-value">{{ $certificate->grade }}</span>
                    @if($certificate->score)
                    <span class="grade-label">({{ number_format($certificate->score, 2) }} คะแนน)</span>
                    @endif
                </div>
                @endif
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <div class="signature-section">
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $certificate->instructor_name ?? 'Instructor' }}</div>
                    <div class="signature-title">ผู้สอน / Instructor</div>
                </div>
                
                <div class="date-section">
                    <div class="date-label">Date of Completion / วันที่จบ</div>
                    <div class="date-value">{{ $certificate->completion_date->format('d F Y') }}</div>
                    <br>
                    <div class="date-label">Date of Issue / วันที่ออก</div>
                    <div class="date-value">{{ $certificate->issue_date->format('d F Y') }}</div>
                </div>
                
                <div class="qr-section">
                    <div class="qr-code">
                        @if($qr_code)
                            <img src="data:image/png;base64,{{ $qr_code }}" alt="QR Code" style="width:100%;height:100%;" onerror="this.parentElement.innerHTML='QR'">
                        @else
                            QR
                        @endif
                    </div>
                    <div class="certificate-number">{{ $certificate->certificate_number }}</div>
                    <div class="verification-text">Scan to verify / สแกนเพื่อตรวจสอบ</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
