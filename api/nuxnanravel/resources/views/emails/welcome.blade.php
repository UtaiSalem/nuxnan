<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยินดีต้อนรับสู่ {{ $appName }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #15171a;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #ffffff;
        }
        .wrap { padding: 24px 12px; }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #1d2333;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }
        .header {
            background: linear-gradient(90deg, #615dfa 0%, #23d2e2 100%);
            padding: 32px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 26px;
            letter-spacing: 2px;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .content { padding: 32px 24px; text-align: center; }
        .avatar {
            width: 88px;
            height: 88px;
            line-height: 88px;
            background-color: #21283b;
            border-radius: 50%;
            margin: -76px auto 20px;
            border: 6px solid #1d2333;
            font-size: 36px;
            color: #615dfa;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            background-color: #f0a640;
            color: #1d2333;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 18px;
        }
        .welcome-text { font-size: 22px; font-weight: 700; margin: 0 0 6px; color: #ffffff; }
        .sub-text { font-size: 15px; color: #8f91ac; line-height: 1.7; margin: 0 0 18px; }
        .panel {
            background-color: #21283b;
            border-radius: 10px;
            padding: 18px 20px;
            text-align: left;
            margin: 24px 0 8px;
        }
        .panel h3 { margin: 0 0 10px; font-size: 15px; color: #ffffff; }
        .panel ol { margin: 0; padding-left: 20px; color: #8f91ac; font-size: 14px; line-height: 1.9; }
        .footer {
            background-color: #15171a;
            padding: 18px;
            text-align: center;
            font-size: 12px;
            color: #5c5e6e;
            line-height: 1.7;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="container">
            <div class="header">
                <h1>{{ $appName }}</h1>
            </div>
            <div class="content">
                <div class="avatar">{{ mb_strtoupper(mb_substr($displayName, 0, 1)) }}</div>

                <div class="badge">รอผู้ดูแลอนุมัติ</div>

                <h2 class="welcome-text">ยินดีต้อนรับ, {{ $displayName }}</h2>

                <p class="sub-text">
                    เราได้รับการสมัครของคุณเรียบร้อยแล้ว บัญชีนี้กำลังรอผู้ดูแลระบบตรวจสอบและอนุมัติ
                    <strong style="color:#ffffff;">คุณยังไม่ต้องทำอะไรเพิ่ม</strong>
                </p>

                <div class="panel">
                    <h3>ขั้นตอนต่อไป</h3>
                    <ol>
                        <li>ผู้ดูแลระบบตรวจสอบบัญชีของคุณ</li>
                        <li>เมื่ออนุมัติแล้ว คุณจะเข้าสู่ระบบด้วยอีเมลและรหัสผ่านที่สมัครไว้ได้ทันที</li>
                        <li>ระหว่างนี้หากเข้าสู่ระบบ จะพบข้อความแจ้งว่ายังรอการอนุมัติ ซึ่งเป็นเรื่องปกติ</li>
                    </ol>
                </div>

                <p class="sub-text" style="margin-top: 20px; font-size: 13px;">
                    หากคุณไม่ได้เป็นผู้สมัครบัญชีนี้ ไม่ต้องดำเนินการใด ๆ บัญชีจะไม่ถูกอนุมัติ
                </p>
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} {{ $appName }}<br>
                อีเมลฉบับนี้ส่งอัตโนมัติ กรุณาอย่าตอบกลับ
            </div>
        </div>
    </div>
</body>
</html>
