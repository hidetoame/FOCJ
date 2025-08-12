<?php
/**
 * 管理画面 - メールテンプレート編集
 */
require_once dirname(dirname(__FILE__)) . '/config/config.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// パラメータ取得
$type = $_GET['type'] ?? 'approve';
$id = $_GET['id'] ?? 0;

// POST処理（保存）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateName = $_POST['template_name'] ?? '';
    $templateContent = $_POST['template_content'] ?? '';
    
    if ($id > 0) {
        // 更新処理
        $sql = "UPDATE mail_templates SET template_name = :name, body = :content, updated_at = CURRENT_TIMESTAMP WHERE template_id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':name' => $templateName,
            ':content' => $templateContent,
            ':id' => $id
        ]);
        $_SESSION['last_template_name'] = $templateName;
    }
    
    header('Location: edit-mail-template-complete.php?type=' . $type);
    exit;
}

// データベースからテンプレートを取得
$templateData = null;
if ($id > 0) {
    $sql = "SELECT * FROM mail_templates WHERE template_id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $id]);
    $templateData = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$templateData) {
    // デフォルトデータ
    $templateData = [
        'template_name' => '',
        'body' => '',
        'updated_at' => date('Y-m-d H:i:s')
    ];
}

// テンプレート読み込み
$html = file_get_contents(getTemplateFilePath('member-management/B2_edit-mail-template.html'));

// アセットパスを調整
$html = str_replace('href="assets/', 'href="' . ADMIN_TEMPLATE_WEB_PATH . '/assets/', $html);
$html = str_replace('src="assets/', 'src="' . ADMIN_TEMPLATE_WEB_PATH . '/assets/', $html);

// ユーザー名を表示
$username = $_SESSION['admin_username'] ?? 'admin';
$html = str_replace('username01', htmlspecialchars($username), $html);

// ログアウトリンクを調整
$html = str_replace('action="0_login.html"', 'action="logout.php"', $html);

// メニューリンクを調整
$html = str_replace('href="A2_registration-list.html"', 'href="registration-list.php"', $html);
$html = str_replace('href="B1_edit-mail-index.html"', 'href="edit-mail.php"', $html);
$html = str_replace('href="C1_members-list.html"', 'href="members-list.php"', $html);

// フォームのアクションを調整
$html = str_replace('action="B3_edit-mail-template-complete.html"', 'action="edit-mail-template.php?type=' . $type . '&id=' . $id . '"', $html);

// 戻るリンクを調整
$html = str_replace('href="B4_mail-template-list.html"', 'href="mail-template-list.php?type=' . $type . '"', $html);

// input要素にname属性を追加
$html = str_replace('<input type="text" class="input-text" value="（テンプレート名）">', 
    '<input type="text" name="template_name" class="input-text" value="' . htmlspecialchars($templateData['template_name']) . '">', $html);

// textareaを置換
$textareaContent = htmlspecialchars($templateData['body']);
$html = preg_replace('/<textarea class="input-textarea --large">.*?<\/textarea>/s', 
    '<textarea name="template_content" class="input-textarea --large">' . $textareaContent . '</textarea>', $html);

// テンプレートの種類を表示
$typeText = $type === 'approve' ? '承認用' : '非承認用';
$html = str_replace('>承認用</div>', '>' . $typeText . '</div>', $html);

// 現在のテンプレート名と最終更新日を表示
$html = str_replace('>（テンプレート名）</div>', '>' . htmlspecialchars($templateData['template_name']) . '</div>', $html);
$updatedDate = $templateData['updated_at'] ? date('Y/n/j', strtotime($templateData['updated_at'])) : '-';
$html = str_replace('>2025/5/15</div>', '>' . $updatedDate . '</div>', $html);

echo $html;