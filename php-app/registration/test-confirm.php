<?php
/**
 * テスト用 - POSTデータ確認
 */
require_once '../config/config.php';

// POSTデータを表示
echo "<h2>受信したPOSTデータ:</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h2>受信したFILESデータ:</h2>";
echo "<pre>";
print_r($_FILES);
echo "</pre>";

echo "<h2>セッションデータ:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>