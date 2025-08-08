<?php
/**
 * 画像表示エンドポイント
 * セキュアに画像を配信する
 */
session_start();

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

// パラメータ取得
$userId = $_GET['user_id'] ?? 0;
$type = $_GET['type'] ?? '';

// パラメータチェック
if (!$userId || !$type) {
    header('HTTP/1.0 400 Bad Request');
    exit('Invalid parameters');
}

// 許可されるタイプ
$allowedTypes = ['license', 'vehicle_inspection', 'business_card'];
if (!in_array($type, $allowedTypes)) {
    header('HTTP/1.0 400 Bad Request');
    exit('Invalid type');
}

// 画像ファイルのパスを構築
$baseDir = '/var/www/html/user_images/';
$userDir = $baseDir . $userId . '/';

// ディレクトリが存在しない場合
if (!is_dir($userDir)) {
    header('HTTP/1.0 404 Not Found');
    exit('Image not found');
}

// タイプに一致するファイルを検索
$files = glob($userDir . $type . '_*');

if (empty($files)) {
    header('HTTP/1.0 404 Not Found');
    exit('Image not found');
}

// 最新のファイルを取得
$filePath = end($files);

// ファイルが存在しない場合
if (!file_exists($filePath)) {
    header('HTTP/1.0 404 Not Found');
    exit('Image not found');
}

// MIMEタイプを取得
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($filePath);

// 画像以外のファイルは配信しない
if (strpos($mimeType, 'image/') !== 0) {
    header('HTTP/1.0 403 Forbidden');
    exit('Invalid file type');
}

// キャッシュヘッダーを設定
$lastModified = filemtime($filePath);
$etag = md5_file($filePath);

header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
header('ETag: "' . $etag . '"');
header('Cache-Control: private, max-age=3600');

// If-None-Matchヘッダーをチェック
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $etag . '"') {
    header('HTTP/1.1 304 Not Modified');
    exit;
}

// If-Modified-Sinceヘッダーをチェック
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
    if ($ifModifiedSince >= $lastModified) {
        header('HTTP/1.1 304 Not Modified');
        exit;
    }
}

// 画像を出力
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');

// 画像を出力
readfile($filePath);
?>