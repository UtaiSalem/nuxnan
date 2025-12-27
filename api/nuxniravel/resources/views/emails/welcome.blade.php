<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Vikinger</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #15171a;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #ffffff;
        }
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
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .content {
            padding: 40px 30px;
            text-align: center;
        }
        .avatar {
            width: 100px;
            height: 100px;
            background-color: #21283b;
            border-radius: 50%;
            margin: -90px auto 20px;
            border: 6px solid #1d2333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #615dfa;
            font-weight: bold;
        }
        .welcome-text {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #ffffff;
        }
        .sub-text {
            font-size: 16px;
            color: #8f91ac;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            background-color: #615dfa;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            box-shadow: 0 4px 12px rgba(97, 93, 250, 0.4);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            background-color: #504ccb;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(97, 93, 250, 0.5);
        }
        .footer {
            background-color: #15171a;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #5c5e6e;
        }
        .level-badge {
            display: inline-block;
            background-color: #23d2e2;
            color: #fff;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div style="padding: 40px 0;">
        <div class="container">
            <div class="header">
                <h1>Vikinger</h1>
            </div>
            <div class="content">
                <div class="avatar">
                    {{ strtoupper(substr($user->username, 0, 1)) }}
                </div>
                <div class="level-badge">New Recruit / สมาชิกใหม่</div>
                <h2 class="welcome-text">Welcome, {{ $user->profile->display_name ?? $user->username }}!</h2>
                <h3 class="welcome-text" style="font-size: 20px; margin-top: 0;">ยินดีต้อนรับ, {{ $user->profile->display_name ?? $user->username }}!</h3>
                
                <p class="sub-text">
                    You've successfully joined the ranks. To unlock your full potential and start earning XP, verify your email address below.
                </p>
                <p class="sub-text" style="margin-top: -20px;">
                    คุณได้เข้าร่วมกองทัพเรียบร้อยแล้ว เพื่อปลดล็อกศักยภาพสูงสุดและเริ่มสะสม XP โปรดยืนยันอีเมลของคุณด้านล่าง
                </p>

                <a href="{{ $verificationUrl }}" class="btn">Verify Account / ยืนยันบัญชี</a>
                
                <p class="sub-text" style="margin-top: 30px; font-size: 14px;">
                    Or copy this link / หรือคัดลอกลิงก์นี้: <br>
                    <a href="{{ $verificationUrl }}" style="color: #23d2e2; word-break: break-all;">{{ $verificationUrl }}</a>
                </p>
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} Vikinger. All rights reserved.<br>
                Quest complete: Registration / ภารกิจเสร็จสิ้น: การลงทะเบียน
            </div>
        </div>
    </div>
</body>
</html>
