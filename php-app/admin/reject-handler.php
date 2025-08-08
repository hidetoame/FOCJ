<?php
/**
 * 却下処理ハンドラー
 */
// データベース接続（config.phpがsession_start()を呼ぶ）
require_once '../config/config.php';
require_once '../includes/mail_functions.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// セッションから情報を取得
$id = $_SESSION['rejection_id'] ?? 0;
$reason = $_SESSION['rejection_reason'] ?? '';

if (!$id || !$reason) {
    header('Location: registration-list.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// 却下処理
$username = $_SESSION['admin_username'] ?? 'admin';
$sql = "UPDATE registrations SET 
        status = 'rejected', 
        rejection_reason = :reason,
        rejected_at = NOW(),
        rejected_by = :username
        WHERE id = :id";
$stmt = $db->prepare($sql);
$stmt->execute([
    ':id' => $id, 
    ':reason' => $reason,
    ':username' => $username
]);

// 却下メール送信
sendRejectionEmail($db, $id, $reason);

// セッションクリア
unset($_SESSION['rejection_id']);
unset($_SESSION['rejection_reason']);

// 完了ページへリダイレクト
header('Location: registration-reject-complete.php?id=' . $id);
exit;