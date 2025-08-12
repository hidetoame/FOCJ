<?php
/**
 * 一時保存画像の表示
 * セッションベースで画像を安全に配信
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(dirname(__FILE__)) . '/config/config.php';

// パラメータ取得
$file = $_GET['file'] ?? '';
// セッションIDを明示的に受け取れるように（編集時のedit_プレフィックス対応）
$sessionId = $_GET['session'] ?? session_id();

if (empty($file) || empty($sessionId)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// セキュリティチェック：ファイル名に不正な文字が含まれていないか
// 16進数とドットのみ許可
if (!preg_match('/^[a-f0-9]+\.[a-z]+$/i', $file)) {
    error_log("Invalid filename format: " . $file);
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// ファイルパス構築
$tempDir = USER_IMAGES_FS_PATH . '/temp/';
$filePath = $tempDir . $sessionId . '/' . $file;

// ファイル存在チェック
if (!file_exists($filePath)) {
    // デバッグ情報を追加
    error_log("Image not found:");
    error_log("  Session ID: " . $sessionId);
    error_log("  File: " . $file);
    error_log("  Full path: " . $filePath);
    error_log("  Directory exists: " . (is_dir(dirname($filePath)) ? 'yes' : 'no'));
    if (is_dir(dirname($filePath))) {
        error_log("  Files in directory: " . implode(', ', scandir(dirname($filePath))));
    }
    header('HTTP/1.0 404 Not Found');
    exit;
}

// MIMEタイプ取得
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($filePath);

// 画像ファイルかチェック
if (strpos($mimeType, 'image/') !== 0) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// 画像を出力
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=3600');

readfile($filePath);
?>