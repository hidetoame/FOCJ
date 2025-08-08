<?php
/**
 * 管理画面 - 案内メール編集
 */
// データベース接続（config.phpがsession_start()を呼ぶ）
require_once '../config/config.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
$db = Database::getInstance()->getConnection();

// 現在利用中のテンプレートIDを取得
$approveTemplateId = null;
$rejectTemplateId = null;

// 承認用メールの現在利用中テンプレートを取得
$sql = "SELECT template_id FROM mail_templates WHERE template_type = '承認通知' AND is_active = TRUE LIMIT 1";
$stmt = $db->query($sql);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $approveTemplateId = $result['template_id'];
}

// 非承認用メールの現在利用中テンプレートを取得
$sql = "SELECT template_id FROM mail_templates WHERE template_type = '却下通知' AND is_active = TRUE LIMIT 1";
$stmt = $db->query($sql);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $rejectTemplateId = $result['template_id'];
}

// テンプレート読み込み
$html = file_get_contents('/var/www/html/templates/member-management/B1_edit-mail-index.html');

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

// 承認用メールのリンクを調整
// 現在利用中のテンプレートがあればそのIDを使用、なければデフォルトのid=1
$approveEditLink = $approveTemplateId ? 
    'edit-mail-template.php?type=approve&id=' . $approveTemplateId :
    'mail-template-list.php?type=approve'; // テンプレートがない場合は一覧へ

$approveLinks = [
    'B2_edit-mail-template.html' => $approveEditLink,
    'B4_mail-template-list.html' => 'mail-template-list.php?type=approve',
    'B5_create-mail-template.html' => 'create-mail-template.php?type=approve'
];

// まず承認用メールのセクションを処理
$pattern = '/<h3>承認用メール<\/h3>(.*?)<h3>非承認用メール<\/h3>/s';
$html = preg_replace_callback($pattern, function($matches) use ($approveLinks) {
    $section = $matches[1];
    foreach ($approveLinks as $old => $new) {
        $section = str_replace('href="' . $old . '"', 'href="' . $new . '"', $section);
    }
    return '<h3>承認用メール</h3>' . $section . '<h3>非承認用メール</h3>';
}, $html);

// 非承認用メールのリンクを調整
// 現在利用中のテンプレートがあればそのIDを使用、なければデフォルトのid=3
$rejectEditLink = $rejectTemplateId ? 
    'edit-mail-template.php?type=reject&id=' . $rejectTemplateId :
    'mail-template-list.php?type=reject'; // テンプレートがない場合は一覧へ

$rejectLinks = [
    'B2_edit-mail-template.html' => $rejectEditLink,
    'B4_mail-template-list.html' => 'mail-template-list.php?type=reject',
    'B5_create-mail-template.html' => 'create-mail-template.php?type=reject'
];

// 非承認用メールのセクションを処理
$pattern = '/<h3>非承認用メール<\/h3>(.*?)<\/div>/s';
$html = preg_replace_callback($pattern, function($matches) use ($rejectLinks) {
    $section = $matches[1];
    foreach ($rejectLinks as $old => $new) {
        $section = str_replace('href="' . $old . '"', 'href="' . $new . '"', $section);
    }
    return '<h3>非承認用メール</h3>' . $section . '</div>';
}, $html);

echo $html;