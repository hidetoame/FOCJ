<?php
/**
 * 管理画面 - メールテンプレート新規作成
 */
require_once '../config/config.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// パラメータ取得
$type = $_GET['type'] ?? 'approve';

// POST処理（保存）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateName = $_POST['template_name'] ?? '';
    $templateContent = $_POST['template_content'] ?? '';
    
    // 入力チェック
    if (empty($templateName) || empty($templateContent)) {
        die("エラー: 入力データが不足しています。");
    }
    
    try {
        // データベースに新規登録
        $typeMap = ['approve' => '承認通知', 'reject' => '却下通知'];
        $dbType = $typeMap[$type] ?? '承認通知';
        
        // 同じタイプのテンプレートが既に存在するかチェック
        $checkSql = "SELECT COUNT(*) as count FROM mail_templates WHERE template_type = :type";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([':type' => $dbType]);
        $existingCount = $checkStmt->fetch()['count'];
        
        // 初めての登録の場合は使用フラグをONにする
        $isActive = ($existingCount == 0) ? 1 : 0;
        
        // 初めての登録で使用フラグをONにする場合、他の同じタイプのテンプレートの使用フラグをOFFにする
        if ($isActive == 1) {
            $updateSql = "UPDATE mail_templates SET is_active = 0 WHERE template_type = :type";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([':type' => $dbType]);
        }
        
        $sql = "INSERT INTO mail_templates (template_name, template_type, subject, body, is_active) VALUES (:name, :type, :subject, :body, :is_active)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':name' => $templateName,
            ':type' => $dbType,
            ':subject' => $templateName, // 仮の件名としてテンプレート名を使用
            ':body' => $templateContent,
            ':is_active' => $isActive
        ]);
        
        $_SESSION['last_template_name'] = $templateName;
        
        // 完了ページへリダイレクト
        header('Location: create-mail-template-complete.php?type=' . $type);
        exit;
    } catch (PDOException $e) {
        // エラー処理
        error_log("Database error in create-mail-template.php: " . $e->getMessage());
        die("データベースエラーが発生しました: " . $e->getMessage());
    }
}

// テンプレート読み込み
$html = file_get_contents('/var/www/html/templates/member-management/B5_create-mail-template.html');

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

// フォームのアクションを調整
$html = str_replace('action="B6_create-mail-template-complete.html"', 'action="create-mail-template.php?type=' . $type . '"', $html);

// 戻るリンクを調整
$html = str_replace('href="B4_mail-template-list.html"', 'href="mail-template-list.php?type=' . $type . '"', $html);

// input要素にname属性を追加
$html = str_replace('<input type="text" class="input-text" value="">', 
    '<input type="text" name="template_name" class="input-text" value="" required>', $html);

// textareaにname属性を追加
$html = str_replace('<textarea class="input-textarea --large"></textarea>', 
    '<textarea name="template_content" class="input-textarea --large" required></textarea>', $html);

// テンプレートの種類を表示
$typeText = $type === 'approve' ? '承認用' : '非承認用';
$html = str_replace('>承認用</div>', '>' . $typeText . '</div>', $html);

echo $html;