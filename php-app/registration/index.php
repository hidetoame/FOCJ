<?php
/**
 * 登録フォーム - 同意画面
 */
require_once '../config/config.php';

// テンプレート読み込み
$html = file_get_contents(TEMPLATE_PATH . '/registration-form/index.html');

// action属性を変更
$html = str_replace('href="registration-form.html"', 'href="form.php"', $html);

// アセットパスを調整
$html = str_replace('href="assets/', 'href="' . REGISTRATION_TEMPLATE_WEB_PATH . '/assets/', $html);
$html = str_replace('src="assets/', 'src="' . REGISTRATION_TEMPLATE_WEB_PATH . '/assets/', $html);

echo $html;