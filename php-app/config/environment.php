<?php
/**
 * 環境設定ファイル
 * 
 * 開発環境と本番環境でパスを切り替えるための設定
 * このファイルを環境に合わせて編集してください
 */

// ============================================================
// 基本設定（環境に応じて変更してください）
// ============================================================

// ドキュメントルート（ファイルシステム上の絶対パス）
// 本番環境例: '/var/www/html'
// 開発環境例（Windows）: 'C:/xampp/htdocs'
// 開発環境例（Mac/Linux）: '/Applications/MAMP/htdocs'
define('DOCUMENT_ROOT', '/var/www/html');

// Webルート（URLのベースパス）
// ルートディレクトリの場合: ''
// サブディレクトリの場合: '/myapp' （スラッシュで始まり、スラッシュで終わらない）
define('WEB_ROOT', '');

// ============================================================
// ファイルシステムパス（サーバー内部で使用）
// ============================================================

// アプリケーションのベースパス
define('APP_BASE_PATH', DOCUMENT_ROOT . '/php-app');

// テンプレートファイルのパス（HTMLファイル等）
define('TEMPLATE_FS_PATH', DOCUMENT_ROOT . '/templates');

// ユーザー画像の保存パス
define('USER_IMAGES_FS_PATH', DOCUMENT_ROOT . '/user_images');

// アップロードファイルのパス（既存のUPLOAD_PATHと統合）
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', APP_BASE_PATH . '/uploads');
}
if (!defined('TEMP_UPLOAD_PATH')) {
    define('TEMP_UPLOAD_PATH', APP_BASE_PATH . '/uploads/temp');
}

// ============================================================
// Webパス（ブラウザからアクセスする際のパス）
// ============================================================

// PHPアプリケーションのWebパス
define('APP_WEB_PATH', WEB_ROOT . '/php-app');

// テンプレートのWebパス（CSS、JS、画像等のアセット用）
define('TEMPLATE_WEB_PATH', WEB_ROOT . '/templates');

// 登録フォーム用テンプレートのWebパス
define('REGISTRATION_TEMPLATE_WEB_PATH', TEMPLATE_WEB_PATH . '/registration-form');

// 管理画面用テンプレートのWebパス
define('ADMIN_TEMPLATE_WEB_PATH', TEMPLATE_WEB_PATH . '/member-management');

// ユーザー画像のWebパス
define('USER_IMAGES_WEB_PATH', WEB_ROOT . '/user_images');

// ============================================================
// ヘルパー関数
// ============================================================

/**
 * テンプレートファイルのフルパスを取得
 * @param string $templatePath テンプレートの相対パス
 * @return string フルパス
 */
function getTemplateFilePath($templatePath) {
    return TEMPLATE_FS_PATH . '/' . ltrim($templatePath, '/');
}

/**
 * アセットのWebパスを取得
 * @param string $assetPath アセットの相対パス
 * @param string $type 'registration' または 'admin'
 * @return string WebのURL
 */
function getAssetWebPath($assetPath, $type = 'admin') {
    $basePath = ($type === 'registration') ? REGISTRATION_TEMPLATE_WEB_PATH : ADMIN_TEMPLATE_WEB_PATH;
    return $basePath . '/assets/' . ltrim($assetPath, '/');
}

/**
 * ユーザー画像のフルパスを取得
 * @param int $userId ユーザーID
 * @param string $filename ファイル名
 * @return string フルパス
 */
function getUserImageFilePath($userId, $filename) {
    return USER_IMAGES_FS_PATH . '/' . $userId . '/' . $filename;
}

/**
 * ユーザー画像のWebパスを取得
 * @param int $userId ユーザーID
 * @param string $filename ファイル名
 * @return string WebのURL
 */
function getUserImageWebPath($userId, $filename) {
    return USER_IMAGES_WEB_PATH . '/' . $userId . '/' . $filename;
}