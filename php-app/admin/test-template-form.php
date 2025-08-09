<?php
// 先にconfigを読み込む
require_once '../config/config.php';
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin';

// create-mail-template.phpの内容を直接実行（require_onceを避ける）
$_GET['type'] = 'approve';
$db = Database::getInstance()->getConnection();
$type = $_GET['type'] ?? 'approve';

// テンプレート読み込み
$html = file_get_contents('/var/www/html/templates/member-management/B5_create-mail-template.html');

// フォーム部分を抽出
if (preg_match('/<form[^>]*>(.*?)<\/form>/s', $html, $matches)) {
    echo "フォームが見つかりました:\n";
    echo htmlspecialchars(substr($matches[0], 0, 500)) . "...\n\n";
}

// name属性のあるフィールドを確認
if (preg_match_all('/name="([^"]*)"/', $html, $matches)) {
    echo "name属性のあるフィールド:\n";
    foreach ($matches[1] as $name) {
        echo "- $name\n";
    }
}
?>