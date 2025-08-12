<?php
/**
 * 管理画面 - 退会処理完了
 */
// データベース接続（config.phpがsession_start()を呼ぶ）
require_once dirname(dirname(__FILE__)) . '/config/config.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// IDチェック
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: members-list.php');
    exit;
}

// 退会済み会員データを取得
$sql = "SELECT * FROM registrations WHERE id = :id AND is_withdrawn = TRUE";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    header('Location: members-list.php');
    exit;
}

// テンプレート読み込み（C7_remove-member-complete.htmlを使用）
$html = file_get_contents(getTemplateFilePath('member-management/C7_remove-member-complete.html'));

// アセットパスを調整
$html = str_replace('href="assets/', 'href="' . ADMIN_TEMPLATE_WEB_PATH . '/assets/', $html);
$html = str_replace('src="assets/', 'src="' . ADMIN_TEMPLATE_WEB_PATH . '/assets/', $html);

// ユーザー名を表示
$username = $_SESSION['admin_username'] ?? 'admin';
$html = str_replace('username01', h($username), $html);

// ログアウトリンクを調整
$html = str_replace('action="0_login.html"', 'action="logout.php"', $html);

// メニューリンクを調整
$html = str_replace('href="A2_registration-list.html"', 'href="registration-list.php"', $html);
$html = str_replace('href="B1_edit-mail-index.html"', 'href="edit-mail.php"', $html);
$html = str_replace('href="C1_members-list.html"', 'href="members-list.php"', $html);

// 会員番号を表示
$memberNumber = $member['member_number'] ?? 'FOCJ-' . str_pad($id, 5, '0', STR_PAD_LEFT);
$html = str_replace('退会した会員の番号：2000', '退会した会員の番号：' . h($memberNumber), $html);

echo $html;