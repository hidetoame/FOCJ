<?php
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin';
$_GET['id'] = 2;

chdir('/var/www/html/admin');
ob_start();
include 'registration-detail.php';
$html = ob_get_clean();

// ボタン部分を探す
if (preg_match('/<div class="registration-detail-control.*?<\/div>\s*<\/div>/s', $html, $matches)) {
    echo "ボタン部分のHTML:\n";
    echo htmlspecialchars($matches[0]);
} else {
    echo "ボタン部分が見つかりません\n";
}

echo "\n\n承認ボタンの検索:\n";
if (strpos($html, 'registration-approve.php') !== false) {
    echo "✓ registration-approve.php へのリンクが存在します\n";
} else if (strpos($html, 'A4_registration-approve.html') !== false) {
    echo "✗ 元のA4_registration-approve.htmlのままです\n";
} else {
    echo "✗ 承認ボタンが見つかりません\n";
}