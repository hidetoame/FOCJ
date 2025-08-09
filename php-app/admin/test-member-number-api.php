<?php
/**
 * 会員番号管理APIのテスト
 */
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin';

// GETリクエストのテスト
echo "<h3>GET リクエストのテスト</h3>";
$ch = curl_init('http://localhost/admin/api/member-number.php?action=get');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTPステータスコード: " . $httpCode . "<br>";
echo "レスポンス: <pre>" . htmlspecialchars($response) . "</pre>";

// POSTリクエストのテスト
echo "<h3>POST リクエストのテスト</h3>";
$data = [
    'start_number' => 1,
    'exclude_numbers' => []
];

$ch = curl_init('http://localhost/admin/api/member-number.php?action=update');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTPステータスコード: " . $httpCode . "<br>";
echo "レスポンス: <pre>" . htmlspecialchars($response) . "</pre>";

// 直接実行テスト
echo "<h3>直接実行テスト</h3>";
$_GET['action'] = 'get';
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
include 'api/member-number.php';
$output = ob_get_clean();
echo "直接実行の出力: <pre>" . htmlspecialchars($output) . "</pre>";
?>