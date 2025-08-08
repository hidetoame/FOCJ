<?php
require_once '../config/config.php';

// セッションからデータ取得（確認画面と同じ）
$formData = getFormData();
$csrf_token = generateCsrfToken();

// テンプレート読み込み
$html = file_get_contents(TEMPLATE_PATH . '/registration-form/registration-form-confirm.html');

// フォームのaction属性を変更
$html = str_replace('action="registration-form-thanks.html"', 'action="complete.php"', $html);

// 変更前と変更後のフォームタグを表示
echo "<h2>フォームタグの確認:</h2>";

// 変更前
if (preg_match('/<form[^>]*>/', file_get_contents(TEMPLATE_PATH . '/registration-form/registration-form-confirm.html'), $matches)) {
    echo "<h3>元のフォームタグ:</h3>";
    echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
}

// 変更後
if (preg_match('/<form[^>]*>/', $html, $matches)) {
    echo "<h3>変更後のフォームタグ:</h3>";
    echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
}

// CSRFトークン追加前後
$csrfField = "\n" . '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">';
$html2 = str_replace('<form action="complete.php" id="registration-form" class="h-adr" method="post" enctype="multipart/form-data">', 
    '<form action="complete.php" id="registration-form" class="h-adr" method="post" enctype="multipart/form-data">' . $csrfField, $html);

if (preg_match('/<form[^>]*>.*?<input[^>]*csrf_token[^>]*>/s', $html2, $matches)) {
    echo "<h3>CSRFトークン追加後:</h3>";
    echo "<pre>" . htmlspecialchars(substr($matches[0], 0, 300)) . "</pre>";
}

echo "<h3>complete.phpファイルの存在確認:</h3>";
echo "complete.php exists: " . (file_exists(__DIR__ . '/complete.php') ? 'YES' : 'NO') . "<br>";
echo "Path: " . __DIR__ . '/complete.php';
?>