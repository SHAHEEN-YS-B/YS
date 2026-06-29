<?php
// Simple status page for the bot
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>𝚂𝙷𝙰𝙷𝙴𝙴𝙽 | 𝚈𝚂 𖠌</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #fff;
        }
        .card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 24px;
            padding: 48px 40px;
            text-align: center;
            max-width: 480px;
            width: 90%;
            box-shadow: 0 32px 64px rgba(0,0,0,0.4);
        }
        .logo {
            font-size: 2.4rem;
            font-weight: 700;
            letter-spacing: 2px;
            background: linear-gradient(90deg, #a78bfa, #60a5fa, #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        .subtitle {
            color: rgba(255,255,255,0.55);
            font-size: 0.9rem;
            margin-bottom: 32px;
            letter-spacing: 1px;
        }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(52, 211, 153, 0.15);
            border: 1px solid rgba(52, 211, 153, 0.3);
            color: #34d399;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            margin-bottom: 32px;
        }
        .dot {
            width: 8px;
            height: 8px;
            background: #34d399;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        .info {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.7);
            line-height: 1.8;
            text-align: right;
        }
        .info span { color: #a78bfa; font-weight: 600; }
        .btn {
            display: inline-block;
            background: linear-gradient(90deg, #7c3aed, #4f46e5);
            color: #fff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 20px rgba(124, 58, 237, 0.4);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(124, 58, 237, 0.6);
        }
        .gif {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 20px;
            border: 3px solid rgba(167, 139, 250, 0.5);
        }
    </style>
</head>
<body>
    <div class="card">
        <img class="gif" src="https://i.postimg.cc/Mp6J1k1Q/Picsart-26-06-29-10-52-12-611-ezgif-com-video-to-gif-converter.gif" alt="Bot Avatar">
        <div class="logo">𝚂𝙷𝙰𝙷𝙴𝙴𝙽 | 𝚈𝚂 𖠌</div>
        <div class="subtitle">Telegram Bot • بوت تيليجرام</div>
        <div class="status">
            <div class="dot"></div>
            البوت يعمل الآن
        </div>
        <div class="info">
            <div>👤 اسم المستخدم: <span>@YShaheen</span></div>
            <div>🌐 اللغة الافتراضية: <span>العربية</span></div>
            <div>⚙️ PHP: <span><?= PHP_VERSION ?></span></div>
            <div>🕐 الوقت: <span><?= date('Y-m-d H:i:s') ?></span></div>
        </div>
        <a class="btn" href="https://t.me/YShaheen" target="_blank">💬 فتح البوت في تيليجرام</a>
    </div>
</body>
</html>
