<?php
session_start();

// テスト用のデータをセッションに設定
$_SESSION['form_data'] = [
    'familyname' => 'テスト',
    'firstname' => '太郎',
    'familyname-kana' => 'テスト',
    'firstname-kana' => 'タロウ',
    'name-alphabet' => 'TEST TARO',
    'mail-address' => 'test@example.com',
    'postal-code' => '100-0001',
    'prefecture' => '東京都',
    'city-address' => '千代田区',
    'mobile-number' => '090-1234-5678',
    'phone-number' => '03-1234-5678',
    'birth-year' => '1990',
    'birth-month' => '1',
    'birth-day' => '1',
    'occupation' => 'テスト職業',
    'car-model' => 'テストモデル',
    'car-year' => '2020',
];
$_SESSION['csrf_token'] = 'test_token';

// 直接complete.phpの処理を実行（POSTメソッドをシミュレート）
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['csrf_token'] = 'test_token';

echo "<h2>Complete.php Debug Test</h2>";
echo "<pre>";
echo "Session data set successfully.\n";
echo "Simulating POST to complete.php...\n";
echo "</pre>";

// complete.phpのコードを直接インクルード
ob_start();
include 'complete.php';
$output = ob_get_clean();

echo "<h3>Output from complete.php:</h3>";
echo $output;
?>