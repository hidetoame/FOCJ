<?php
// complete.phpへの直接POST テスト
session_start();

// テスト用のデータをセッションに設定
$_SESSION['form_data'] = [
    'familyname' => 'テスト',
    'firstname' => '太郎',
    'mail-address' => 'test@example.com',
    'postal-code' => '100-0001',
    'prefecture' => '東京都',
    'city-address' => '千代田区',
    'mobile-number' => '090-1234-5678',
];
$_SESSION['csrf_token'] = 'test_token';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete.php Test</title>
</head>
<body>
    <h2>Complete.phpへの直接POSTテスト</h2>
    <form action="complete.php" method="POST">
        <input type="hidden" name="csrf_token" value="test_token">
        <input type="submit" value="complete.phpへPOST">
    </form>
    
    <h3>セッション内容:</h3>
    <pre><?php print_r($_SESSION); ?></pre>
</body>
</html>