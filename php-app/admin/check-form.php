<?php
session_start();

// HTMLテンプレートを読み込み
$html = file_get_contents('/var/www/html/templates/member-management/B5_create-mail-template.html');

echo "=== オリジナルのHTMLフォーム ===\n";
if (preg_match_all('/<form[^>]*>/', $html, $matches)) {
    foreach ($matches[0] as $i => $form) {
        echo "Form " . ($i + 1) . ": " . $form . "\n";
    }
}

echo "\n=== input/textareaフィールド ===\n";
if (preg_match('/<input type="text"[^>]*>/', $html, $matches)) {
    echo "Input: " . $matches[0] . "\n";
}
if (preg_match('/<textarea[^>]*>/', $html, $matches)) {
    echo "Textarea: " . $matches[0] . "\n";
}

// create-mail-template.phpと同じ置換処理
$type = 'approve';

// フォームのアクションを調整
$html = str_replace('action="B6_create-mail-template-complete.html"', 'action="create-mail-template.php?type=' . $type . '"', $html);

// input要素にname属性を追加
$html = str_replace('<input type="text" class="input-text" value="">', 
    '<input type="text" name="template_name" class="input-text" value="" required>', $html);

// textareaにname属性を追加
$html = str_replace('<textarea class="input-textarea --large"></textarea>', 
    '<textarea name="template_content" class="input-textarea --large" required></textarea>', $html);

echo "\n=== 置換後のフォーム ===\n";
if (preg_match_all('/<form[^>]*>/', $html, $matches)) {
    foreach ($matches[0] as $i => $form) {
        echo "Form " . ($i + 1) . ": " . $form . "\n";
    }
}

echo "\n=== 置換後のフィールド ===\n";
if (preg_match('/<input[^>]*name="template_name"[^>]*>/', $html, $matches)) {
    echo "Input: " . $matches[0] . "\n";
}
if (preg_match('/<textarea[^>]*name="template_content"[^>]*>/', $html, $matches)) {
    echo "Textarea: " . $matches[0] . "\n";
}
?>