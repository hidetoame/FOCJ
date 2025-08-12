<?php
/**
 * ユーザー画像表示エンドポイント
 * 管理者のみアクセス可能
 */
require_once '../config/config.php';

// 管理者ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// パラメータ取得
$userId = $_GET['user_id'] ?? '';
$file = $_GET['file'] ?? '';

if (empty($userId) || empty($file)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// セキュリティチェック：ファイル名に不正な文字が含まれていないか
if (!preg_match('/^[a-f0-9]+\.[a-z]+$/i', $file)) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// ユーザーIDは数値のみ
if (!is_numeric($userId)) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// ファイルパス構築
$filePath = getUserImageFilePath($userId, $file);

// ファイル存在チェック
if (!file_exists($filePath)) {
    // デフォルト画像を返す
    header('Content-Type: image/svg+xml');
    echo '<?xml version="1.0" encoding="UTF-8"?>
    <svg xmlns="http://www.w3.org/2000/svg" width="400" height="300">
        <rect width="400" height="300" fill="#f0f0f0"/>
        <text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#999" font-size="16">画像なし</text>
    </svg>';
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
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');

readfile($filePath);
?>