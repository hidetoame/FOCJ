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

// 会員番号を発行（APIを直接実行）
$memberNumber = null;
try {
    // 現在の番号を取得
    $sql = "SELECT next_val FROM member_number_sequence WHERE id = 1";
    $stmt = $db->query($sql);
    $sequence = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentNumber = $sequence ? $sequence['next_val'] : 999;
    
    // 除外番号を取得
    $sql = "SELECT excluded_number FROM excluded_member_numbers";
    $stmt = $db->query($sql);
    $excludeNumbers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $excludeNumbers = array_map('intval', $excludeNumbers);
    
    // 現在の番号から開始して、除外番号に含まれない番号を探す
    $nextNumber = $currentNumber;
    while (in_array($nextNumber, $excludeNumbers)) {
        $nextNumber++;
    }
    
    $memberNumber = $nextNumber;
    
    // 発行した番号の次を保存
    $sql = "UPDATE member_number_sequence SET next_val = :next_val WHERE id = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':next_val' => $nextNumber + 1]);
    
    error_log("Member number issued: " . $memberNumber);
} catch (Exception $e) {
    error_log("Error issuing member number: " . $e->getMessage());
}

// 承認処理（会員番号も含めて更新）
$username = $_SESSION['admin_username'] ?? 'admin';
$sql = "UPDATE registrations SET 
        status = 'approved',
        member_number = :member_number,
        approved_at = CURRENT_TIMESTAMP,
        approved_by = :username 
        WHERE id = :id";
$stmt = $db->prepare($sql);
$stmt->execute([
    ':id' => $id,
    ':member_number' => $memberNumber,
    ':username' => $username
]);

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