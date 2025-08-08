<?php
/**
 * 管理画面 - メールテンプレート一覧
 */
require_once '../config/config.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// テンプレートタイプを取得
$type = $_GET['type'] ?? 'approve';
$typeText = $type === 'approve' ? '承認用メール' : '非承認用メール';

// POSTリクエスト処理（テンプレート選択）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_template'])) {
    $selectedId = intval($_POST['selected_template']);
    $typeMap = ['approve' => '承認通知', 'reject' => '却下通知'];
    $dbType = $typeMap[$type] ?? '承認通知';
    
    // まず全てのis_activeをfalseにする
    $sql = "UPDATE mail_templates SET is_active = false WHERE template_type = :type";
    $stmt = $db->prepare($sql);
    $stmt->execute([':type' => $dbType]);
    
    // 選択したテンプレートをアクティブにする
    $sql = "UPDATE mail_templates SET is_active = true WHERE template_id = :id AND template_type = :type";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $selectedId, ':type' => $dbType]);
    
    // リダイレクト
    header('Location: mail-template-list.php?type=' . $type . '&updated=1');
    exit;
}

// テンプレート読み込み
$html = file_get_contents('/var/www/html/templates/member-management/B4_mail-template-list.html');

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

// タイトルを調整
$html = str_replace('テンプレート一覧：承認用メール', 'テンプレート一覧：' . $typeText, $html);

// 編集リンクを調整
$html = str_replace('href="B2_edit-mail-template.html"', 'href="edit-mail-template.php?type=' . $type . '&id=1"', $html);

// データベースからテンプレートを取得
$typeMap = ['approve' => '承認通知', 'reject' => '却下通知'];
$dbType = $typeMap[$type] ?? '承認通知';
$sql = "SELECT * FROM mail_templates WHERE template_type = :type ORDER BY updated_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute([':type' => $dbType]);
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 更新メッセージ
$updateMessage = '';
if (isset($_GET['updated'])) {
    $updateMessage = '<div style="background: #e7f5e1; color: #2e7d32; padding: 10px; margin-bottom: 20px; border-radius: 5px;">テンプレートの選択を更新しました。</div>';
}

// フォーム開始とテーブルヘッダーを修正
$tableHtml = $updateMessage . '
<form method="POST" action="mail-template-list.php?type=' . $type . '">
<div class="table-list-contents" data-simplebar>
    <table>
        <thead>
            <tr>
                <th class="--slim">選択</th>
                <th class="--wide">テンプレート名</th>
                <th class="--medium-slim">最終更新日</th>
                <th class="--slim">編集</th>
            </tr>
        </thead>
        <tbody>';

// テーブル行を作成
foreach ($templates as $template) {
    $updatedDate = date('Y/n/j', strtotime($template['updated_at']));
    $checked = $template['is_active'] ? 'checked' : '';
    $activeLabel = $template['is_active'] ? ' <span style="color: green; font-weight: bold;">[使用中]</span>' : '';
    
    $tableHtml .= '
        <tr>
            <td style="text-align: center;">
                <input type="radio" name="selected_template" value="' . $template['template_id'] . '" ' . $checked . ' onchange="this.form.submit()">
            </td>
            <td>' . htmlspecialchars($template['template_name']) . $activeLabel . '</td>
            <td>' . $updatedDate . '</td>
            <td><a href="edit-mail-template.php?type=' . $type . '&id=' . $template['template_id'] . '" class="button button--line button--small">編集</a></td>
        </tr>';
}

$tableHtml .= '
        </tbody>
    </table>
</div>
</form>';

// テーブル部分を置換
$html = preg_replace('/<div class="table-list-contents".*?<\/div>\s*<\/div>/s', $tableHtml . '</div>', $html);

echo $html;