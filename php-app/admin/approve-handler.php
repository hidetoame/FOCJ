<?php
/**
 * 承認処理ハンドラー
 */
// データベース接続（config.phpがsession_start()を呼ぶ）
require_once '../config/config.php';
require_once '../includes/mail_functions.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// POSTのみ受け付ける
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registration-list.php');
    exit;
}

$db = Database::getInstance()->getConnection();

$id = $_POST['id'] ?? 0;
$action = $_POST['action'] ?? '';

if (!$id || $action !== 'approve') {
    header('Location: registration-list.php');
    exit;
}

// 承認処理
$username = $_SESSION['admin_username'] ?? 'admin';
$sql = "UPDATE registrations SET 
        status = 'approved', 
        approved_at = NOW(),
        approved_by = :username 
        WHERE id = :id";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $id, ':username' => $username]);

// カスタムメール内容があればそれを使用
$customMailContent = $_POST['custom_mail_content'] ?? '';
if ($customMailContent) {
    // カスタムメール内容で送信
    sendCustomApprovalEmail($db, $id, $customMailContent);
} else {
    // テンプレートから送信
    sendApprovalEmail($db, $id);
}

// 完了ページへリダイレクト
header('Location: registration-approve-complete.php?id=' . $id);
exit;