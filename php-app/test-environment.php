<?php
/**
 * 環境設定のテストスクリプト
 * このファイルを実行して、パス設定が正しく機能しているか確認します
 */

require_once dirname(__FILE__) . '/config/config.php';

echo "=====================================\n";
echo "環境設定テスト\n";
echo "=====================================\n\n";

// 1. 定数の確認
echo "【設定された定数】\n";
echo "DOCUMENT_ROOT: " . DOCUMENT_ROOT . "\n";
echo "WEB_ROOT: " . WEB_ROOT . "\n";
echo "APP_BASE_PATH: " . APP_BASE_PATH . "\n";
echo "TEMPLATE_FS_PATH: " . TEMPLATE_FS_PATH . "\n";
echo "USER_IMAGES_FS_PATH: " . USER_IMAGES_FS_PATH . "\n";
echo "UPLOAD_PATH: " . UPLOAD_PATH . "\n";
echo "TEMP_UPLOAD_PATH: " . TEMP_UPLOAD_PATH . "\n\n";

echo "【Webパス】\n";
echo "APP_WEB_PATH: " . APP_WEB_PATH . "\n";
echo "TEMPLATE_WEB_PATH: " . TEMPLATE_WEB_PATH . "\n";
echo "REGISTRATION_TEMPLATE_WEB_PATH: " . REGISTRATION_TEMPLATE_WEB_PATH . "\n";
echo "ADMIN_TEMPLATE_WEB_PATH: " . ADMIN_TEMPLATE_WEB_PATH . "\n";
echo "USER_IMAGES_WEB_PATH: " . USER_IMAGES_WEB_PATH . "\n\n";

// 2. ヘルパー関数のテスト
echo "【ヘルパー関数のテスト】\n";
echo "getTemplateFilePath('member-management/A1_dashboard.html'):\n";
echo "  → " . getTemplateFilePath('member-management/A1_dashboard.html') . "\n\n";

echo "getAssetWebPath('css/style.css', 'admin'):\n";
echo "  → " . getAssetWebPath('css/style.css', 'admin') . "\n\n";

echo "getAssetWebPath('img/logo.svg', 'registration'):\n";
echo "  → " . getAssetWebPath('img/logo.svg', 'registration') . "\n\n";

echo "getUserImageFilePath(123, 'license.jpg'):\n";
echo "  → " . getUserImageFilePath(123, 'license.jpg') . "\n\n";

echo "getUserImageWebPath(123, 'license.jpg'):\n";
echo "  → " . getUserImageWebPath(123, 'license.jpg') . "\n\n";

// 3. ディレクトリの存在確認
echo "【ディレクトリの存在確認】\n";
$dirs = [
    'APP_BASE_PATH' => APP_BASE_PATH,
    'UPLOAD_PATH' => UPLOAD_PATH,
    'TEMP_UPLOAD_PATH' => TEMP_UPLOAD_PATH,
];

foreach ($dirs as $name => $path) {
    $exists = file_exists($path) ? '✓ 存在' : '✗ 存在しない';
    $writable = is_writable($path) ? '✓ 書き込み可能' : '✗ 書き込み不可';
    echo sprintf("%-20s: %s %s %s\n", $name, $path, $exists, $writable);
}

echo "\n";

// 4. テンプレートファイルの存在確認（サンプル）
echo "【テンプレートファイルのサンプル確認】\n";
$templates = [
    'member-management/A1_dashboard.html',
    'member-management/0_login.html',
    'registration-form/index.html'
];

foreach ($templates as $template) {
    $fullPath = getTemplateFilePath($template);
    $exists = file_exists($fullPath) ? '✓' : '✗';
    echo sprintf("%s %s\n", $exists, $template);
}

echo "\n=====================================\n";
echo "テスト完了\n";
echo "=====================================\n";
?>