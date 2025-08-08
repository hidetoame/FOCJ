<?php
/**
 * 画像アップロード処理クラス
 */
class ImageUpload {
    // アップロードディレクトリのベースパス
    private const BASE_DIR = '/var/www/html/user_images/';
    
    // 許可する画像形式
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    
    // 最大ファイルサイズ（5MB）
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;
    
    /**
     * 画像をアップロードする
     * 
     * @param array $file $_FILES配列の要素
     * @param int $userId ユーザーID
     * @param string $type 画像タイプ（license, vehicle_inspection, business_card）
     * @return array 結果配列 ['success' => bool, 'path' => string or 'error' => string]
     */
    public static function upload($file, $userId, $type) {
        // エラーチェック
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => self::getUploadErrorMessage($file['error'])];
        }
        
        // ファイルサイズチェック
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return ['success' => false, 'error' => 'ファイルサイズが大きすぎます（最大5MB）'];
        }
        
        // MIMEタイプチェック
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!isset(self::ALLOWED_TYPES[$mimeType])) {
            return ['success' => false, 'error' => '許可されていないファイル形式です'];
        }
        
        // ユーザーディレクトリを作成
        $userDir = self::BASE_DIR . $userId . '/';
        if (!is_dir($userDir)) {
            if (!mkdir($userDir, 0755, true)) {
                return ['success' => false, 'error' => 'ディレクトリの作成に失敗しました'];
            }
        }
        
        // ファイル名を生成（タイプ_タイムスタンプ.拡張子）
        $extension = self::ALLOWED_TYPES[$mimeType];
        $fileName = $type . '_' . time() . '.' . $extension;
        $filePath = $userDir . $fileName;
        
        // 古い同タイプのファイルを削除
        self::deleteOldFiles($userDir, $type);
        
        // ファイルを移動
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'error' => 'ファイルの保存に失敗しました'];
        }
        
        // 画像をリサイズ（必要に応じて）
        self::resizeImage($filePath, $mimeType);
        
        // 相対パスを返す
        $relativePath = '/user_images/' . $userId . '/' . $fileName;
        
        return ['success' => true, 'path' => $relativePath];
    }
    
    /**
     * 画像を取得する
     * 
     * @param int $userId ユーザーID
     * @param string $type 画像タイプ
     * @return string|null ファイルパス or null
     */
    public static function getImage($userId, $type) {
        $userDir = self::BASE_DIR . $userId . '/';
        
        if (!is_dir($userDir)) {
            return null;
        }
        
        // タイプに一致するファイルを検索
        $files = glob($userDir . $type . '_*');
        
        if (empty($files)) {
            return null;
        }
        
        // 最新のファイルを返す
        $latestFile = end($files);
        return '/user_images/' . $userId . '/' . basename($latestFile);
    }
    
    /**
     * 画像を削除する
     * 
     * @param int $userId ユーザーID
     * @param string $type 画像タイプ
     * @return bool 成功/失敗
     */
    public static function deleteImage($userId, $type) {
        $userDir = self::BASE_DIR . $userId . '/';
        
        if (!is_dir($userDir)) {
            return true;
        }
        
        // タイプに一致するファイルを削除
        $files = glob($userDir . $type . '_*');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * 古い同タイプのファイルを削除
     * 
     * @param string $userDir ユーザーディレクトリ
     * @param string $type 画像タイプ
     */
    private static function deleteOldFiles($userDir, $type) {
        $files = glob($userDir . $type . '_*');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    /**
     * 画像をリサイズする
     * 
     * @param string $filePath ファイルパス
     * @param string $mimeType MIMEタイプ
     */
    private static function resizeImage($filePath, $mimeType) {
        // 画像情報を取得
        list($width, $height) = getimagesize($filePath);
        
        // 最大幅・高さ
        $maxWidth = 1920;
        $maxHeight = 1080;
        
        // リサイズが必要かチェック
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return;
        }
        
        // アスペクト比を保持してリサイズ
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        // 元画像を読み込み
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($filePath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($filePath);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($filePath);
                break;
            default:
                return;
        }
        
        // 新しい画像を作成
        $destination = imagecreatetruecolor($newWidth, $newHeight);
        
        // PNG/GIFの透過処理
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // リサイズ
        imagecopyresampled(
            $destination, $source,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $width, $height
        );
        
        // 保存
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($destination, $filePath, 85);
                break;
            case 'image/png':
                imagepng($destination, $filePath, 9);
                break;
            case 'image/gif':
                imagegif($destination, $filePath);
                break;
            case 'image/webp':
                imagewebp($destination, $filePath, 85);
                break;
        }
        
        // メモリ解放
        imagedestroy($source);
        imagedestroy($destination);
    }
    
    /**
     * アップロードエラーメッセージを取得
     * 
     * @param int $errorCode エラーコード
     * @return string エラーメッセージ
     */
    private static function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'ファイルサイズがサーバーの上限を超えています';
            case UPLOAD_ERR_FORM_SIZE:
                return 'ファイルサイズがフォームの上限を超えています';
            case UPLOAD_ERR_PARTIAL:
                return 'ファイルが部分的にしかアップロードされませんでした';
            case UPLOAD_ERR_NO_FILE:
                return 'ファイルがアップロードされませんでした';
            case UPLOAD_ERR_NO_TMP_DIR:
                return '一時フォルダが見つかりません';
            case UPLOAD_ERR_CANT_WRITE:
                return 'ディスクへの書き込みに失敗しました';
            case UPLOAD_ERR_EXTENSION:
                return '拡張機能によってアップロードが停止されました';
            default:
                return '不明なエラーが発生しました';
        }
    }
}
?>