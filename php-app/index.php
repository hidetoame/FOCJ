<?php
/**
 * FOCJ Admin Application Entry Point
 */
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferrari Owners' Club Japan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f5f5f5;
        }
        .container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 2rem;
        }
        .logo {
            width: 150px;
            height: 150px;
            margin: 0 auto 2rem;
        }
        .links {
            margin-top: 2rem;
        }
        .links a {
            display: inline-block;
            padding: 1rem 2rem;
            margin: 0 1rem;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .links a:hover {
            background: #c82333;
        }
        .info {
            margin-top: 3rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .info h3 {
            color: #666;
            margin-bottom: 1rem;
        }
        .info ul {
            text-align: left;
            max-width: 500px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="<?php echo REGISTRATION_TEMPLATE_WEB_PATH; ?>/assets/img/logo_focj.svg" width="150" height="150" alt="Ferrari Owners' Club Japan">
        </div>
        <h1>Ferrari Owners' Club Japan</h1>
        <p>フェラーリオーナーズクラブジャパンへようこそ</p>
        
        <div class="links">
            <a href="/registration/">入会申込フォーム</a>
            <a href="/admin/">管理画面</a>
        </div>
        
        <div class="info">
            <h3>📋 システム情報</h3>
            <ul>
                <li>PHP Version: <?php echo phpversion(); ?></li>
                <li>PostgreSQL: 接続設定済み</li>
                <li>Docker環境で稼働中</li>
            </ul>
        </div>
    </div>
</body>
</html>