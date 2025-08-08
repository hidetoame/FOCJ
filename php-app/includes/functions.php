<?php
/**
 * 共通関数
 */

/**
 * HTMLエスケープ
 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF トークン生成
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF トークン検証
 */
function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * ファイルアップロード処理
 */
function uploadFile($file, $prefix = '') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // ファイルサイズチェック
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    // 拡張子チェック
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    // ユニークなファイル名生成
    $filename = $prefix . '_' . uniqid() . '.' . $ext;
    $filepath = TEMP_UPLOAD_PATH . '/' . $filename;
    
    // ファイル移動
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

/**
 * 一時ファイルを正式な場所に移動
 */
function moveTempFile($filename) {
    $tempPath = TEMP_UPLOAD_PATH . '/' . $filename;
    $finalPath = UPLOAD_PATH . '/' . $filename;
    
    if (file_exists($tempPath)) {
        return rename($tempPath, $finalPath);
    }
    return false;
}

/**
 * セッションからフォームデータ取得
 */
function getFormData($key = null) {
    if (!isset($_SESSION['form_data'])) {
        return null;
    }
    if ($key === null) {
        return $_SESSION['form_data'];
    }
    return isset($_SESSION['form_data'][$key]) ? $_SESSION['form_data'][$key] : null;
}

/**
 * セッションにフォームデータ保存
 */
function saveFormData($data) {
    $_SESSION['form_data'] = $data;
}

/**
 * セッションのフォームデータクリア
 */
function clearFormData() {
    unset($_SESSION['form_data']);
}

/**
 * エラーメッセージ設定
 */
function setError($message) {
    $_SESSION['error'] = $message;
}

/**
 * エラーメッセージ取得
 */
function getError() {
    if (isset($_SESSION['error'])) {
        $error = $_SESSION['error'];
        unset($_SESSION['error']);
        return $error;
    }
    return null;
}

/**
 * 成功メッセージ設定
 */
function setSuccess($message) {
    $_SESSION['success'] = $message;
}

/**
 * 成功メッセージ取得
 */
function getSuccess() {
    if (isset($_SESSION['success'])) {
        $success = $_SESSION['success'];
        unset($_SESSION['success']);
        return $success;
    }
    return null;
}