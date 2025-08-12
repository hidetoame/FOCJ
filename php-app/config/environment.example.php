<?php
/**
 * 環境設定ファイル（サンプル）
 * 
 * このファイルをコピーして environment.php として保存し、
 * 各環境に合わせて設定を変更してください。
 * 
 * cp environment.example.php environment.php
 */

// ============================================================
// 環境別の設定例
// ============================================================

// ================
// 本番環境（Linux）
// ================
// define('DOCUMENT_ROOT', '/var/www/html');
// define('WEB_ROOT', '');

// ==========================
// 開発環境（XAMPP on Windows）
// ==========================
// define('DOCUMENT_ROOT', 'C:/xampp/htdocs');
// define('WEB_ROOT', '');

// ======================
// 開発環境（MAMP on Mac）
// ======================
// define('DOCUMENT_ROOT', '/Applications/MAMP/htdocs');
// define('WEB_ROOT', '');

// ======================================
// サブディレクトリにインストールする場合
// ======================================
// define('DOCUMENT_ROOT', '/var/www/html');
// define('WEB_ROOT', '/focj_admin');  // http://example.com/focj_admin/ でアクセスする場合

// ============================================================
// 実際の設定（環境に合わせて変更してください）
// ============================================================

// ドキュメントルート（ファイルシステム上の絶対パス）
define('DOCUMENT_ROOT', '/var/www/html');

// Webルート（URLのベースパス）
define('WEB_ROOT', '');

// ============================================================
// 以下は通常変更不要
// ============================================================

// ファイルシステムパス（サーバー内部で使用）
define('APP_BASE_PATH', DOCUMENT_ROOT . '/php-app');
define('TEMPLATE_FS_PATH', DOCUMENT_ROOT . '/templates');
define('USER_IMAGES_FS_PATH', DOCUMENT_ROOT . '/user_images');

// アップロードファイルのパス
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', APP_BASE_PATH . '/uploads');
}
if (!defined('TEMP_UPLOAD_PATH')) {
    define('TEMP_UPLOAD_PATH', APP_BASE_PATH . '/uploads/temp');
}

// Webパス（ブラウザからアクセスする際のパス）
define('APP_WEB_PATH', WEB_ROOT . '/php-app');
define('TEMPLATE_WEB_PATH', WEB_ROOT . '/templates');
define('REGISTRATION_TEMPLATE_WEB_PATH', TEMPLATE_WEB_PATH . '/registration-form');
define('ADMIN_TEMPLATE_WEB_PATH', TEMPLATE_WEB_PATH . '/member-management');
define('USER_IMAGES_WEB_PATH', WEB_ROOT . '/user_images');

// ヘルパー関数
function getTemplateFilePath($templatePath) {
    return TEMPLATE_FS_PATH . '/' . ltrim($templatePath, '/');
}

function getAssetWebPath($assetPath, $type = 'admin') {
    $basePath = ($type === 'registration') ? REGISTRATION_TEMPLATE_WEB_PATH : ADMIN_TEMPLATE_WEB_PATH;
    return $basePath . '/assets/' . ltrim($assetPath, '/');
}

function getUserImageFilePath($userId, $filename) {
    return USER_IMAGES_FS_PATH . '/' . $userId . '/' . $filename;
}

function getUserImageWebPath($userId, $filename) {
    return USER_IMAGES_WEB_PATH . '/' . $userId . '/' . $filename;
}