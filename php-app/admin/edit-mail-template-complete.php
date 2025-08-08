<?php
/**
 * 管理画面 - メールテンプレート編集完了
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$type = $_GET['type'] ?? 'approve';

// テンプレート読み込み
$html = file_get_contents('/var/www/html/templates/member-management/B3_edit-mail-template-complete.html');

// アセットパスを調整
$html = str_replace('href="assets/', 'href="/templates/member-management/assets/', $html);
$html = str_replace('src="assets/', 'src="/templates/member-management/assets/', $html);

// ユーザー名を表示
$username = $_SESSION['admin_username'] ?? 'admin';
$html = str_replace('username01', htmlspecialchars($username), $html);

// ログアウトリンクを調整
$html = str_replace('action="0_login.html"', 'action="logout.php"', $html);

// メニューリンクを調整
$html = str_replace('href="A2_registration-list.html"', 'href="registration-list.php"', $html);
$html = str_replace('href="B1_edit-mail-index.html"', 'href="edit-mail.php"', $html);
$html = str_replace('href="C1_members-list.html"', 'href="members-list.php"', $html);

// テンプレート名を表示（実際は保存時の名前を使用）
$templateName = $_SESSION['last_template_name'] ?? '標準テンプレート';
$html = str_replace('（テンプレート名）', htmlspecialchars($templateName), $html);

// 3秒後にリダイレクト
$html = str_replace('</head>', '<meta http-equiv="refresh" content="3;url=mail-template-list.php?type=' . $type . '"></head>', $html);

echo $html;