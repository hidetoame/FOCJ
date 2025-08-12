<?php
/**
 * アプリケーション設定
 */

// セッション設定
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// エラー表示設定（開発環境）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 環境設定の読み込み
require_once dirname(dirname(__FILE__)) . '/config/environment.php';

// パス設定
define('BASE_PATH', dirname(dirname(__FILE__)));
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', BASE_PATH . '/uploads');
}
if (!defined('TEMP_UPLOAD_PATH')) {
    define('TEMP_UPLOAD_PATH', BASE_PATH . '/uploads/temp');
}
define('TEMPLATE_PATH', BASE_PATH . '/templates');

// ファイルアップロード設定
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// データベース設定読み込み
require_once BASE_PATH . '/config/database.php';

// 共通関数読み込み
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/template.php';

// アップロードディレクトリの作成
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}
if (!file_exists(TEMP_UPLOAD_PATH)) {
    mkdir(TEMP_UPLOAD_PATH, 0777, true);
}