<?php
/**
 * 管理画面 - 承認内容確認
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
    header('Location: registration-list.php');
    exit;
}

// 申込データを取得
$sql = "SELECT * FROM registrations WHERE id = :id";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $id]);
$registration = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$registration) {
    header('Location: registration-list.php');
    exit;
}

// アクティブな承認用テンプレートを取得
$sql = "SELECT * FROM mail_templates 
        WHERE template_type = '承認通知' AND is_active = true 
        LIMIT 1";
$stmt = $db->query($sql);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

$templateName = '';
if (!$template) {
    // テンプレートがない場合はデフォルトメッセージ
    $mailContent = "承認用テンプレートが設定されていません。";
    $templateName = '(テンプレート未設定)';
} else {
    // テンプレート変数を置換してプレビュー生成
    $mailContent = $template['body'];
    $mailContent = str_replace('{{name}}', $registration['family_name'] . ' ' . $registration['first_name'], $mailContent);
    $mailContent = str_replace('{NAME}', $registration['family_name'] . ' ' . $registration['first_name'], $mailContent);
    $mailContent = str_replace('{{email}}', $registration['email'], $mailContent);
    $mailContent = str_replace('{{member_number}}', 'FOCJ-' . str_pad($id, 5, '0', STR_PAD_LEFT), $mailContent);
    $templateName = $template['template_name'];
}

// テンプレート読み込み
$html = file_get_contents(getTemplateFilePath('member-management/A4_registration-approve.html'));

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

// 送信メール内容のラベルにテンプレート名を追加
$html = str_replace('<h3>送信メール内容<a href="#"', 
    '<h3>送信メール内容 <span style="font-size: 14px; color: #999; font-weight: normal;">（使用テンプレート: ' . h($templateName) . '）</span><a href="#"', $html);

// メール内容の表示と編集機能
$mailContentDisplay = nl2br(h($mailContent));
$mailContentEdit = h($mailContent);

// 表示用と編集用の両方のHTMLを作成
$mailContentHtml = '
<div id="mailContentDisplay" class="registration-detail-mail-content">' . $mailContentDisplay . '</div>
<textarea id="mailContentEdit" class="registration-detail-mail-content" style="display:none; width:100%; min-height:400px; padding:15px; font-family:inherit; font-size:14px; line-height:1.8; border:1px solid #666; border-radius:4px; background-color:#222; color:#fff; resize:vertical; overflow:hidden;">' . $mailContentEdit . '</textarea>';

$html = preg_replace('/<div class="registration-detail-mail-content">.*?<\/div>/s', 
    $mailContentHtml, $html);

// 編集ボタンを編集切り替えボタンに変更
$html = str_replace('<a href="#" class="button button--line button--small">編集する</a>', 
    '<a href="#" onclick="toggleMailEdit(); return false;" id="editButton" class="button button--line button--small">編集する</a>', $html);

// 申込者情報を表示
// 氏名
$html = str_replace('>山田 太郎</div>', '>' . h($registration['family_name'] . ' ' . $registration['first_name']) . '</div>', $html);

// フリガナ
$html = str_replace('>ヤマダ タロウ</div>', '>' . h($registration['family_name_kana'] . ' ' . $registration['first_name_kana']) . '</div>', $html);

// ローマ字
$html = str_replace('>TAROU YAMADA</div>', '>' . h($registration['name_alphabet']) . '</div>', $html);

// 住所
$address = '〒' . h($registration['postal_code']) . '<br>' . h($registration['prefecture'] . $registration['city_address']);
if ($registration['building_name']) {
    $address .= ' ' . h($registration['building_name']);
}
$html = str_replace('>〒160-0022<br>東京都新宿区1-1-1 〇〇〇〇ビル 23F</div>', '>' . $address . '</div>', $html);

// 住所種別
$addressType = $registration['address_type'] === 'home' ? '自宅' : '勤務先';
$html = str_replace('>勤務先</div>', '>' . $addressType . '</div>', $html);

// 電話番号
$html = str_replace('>090-1234-5678</div>', '>' . h($registration['mobile_number']) . '</div>', $html);
$html = str_replace('>03-0000-0000</div>', '>' . h($registration['phone_number'] ?: '-') . '</div>', $html);

// 生年月日
$birthDate = $registration['birth_date'] ? date('Y年n月j日', strtotime($registration['birth_date'])) : '-';
$html = str_replace('>1975年1月 1日</div>', '>' . $birthDate . '</div>', $html);

// メールアドレス
$html = str_replace('>example@example.com</div>', '>' . h($registration['email']) . '</div>', $html);

// 職業
$occupation = h($registration['occupation']);
if ($registration['company_name']) {
    $occupation .= '<br>' . h($registration['company_name']);
}
$html = str_replace('>〇〇株式会社 代表取締役<br>その他複数の会社経営</div>', '>' . $occupation . '</div>', $html);

// 自己紹介
$selfIntro = nl2br(h($registration['self_introduction'] ?: '-'));
$html = preg_replace('/>自己紹介テキスト.*?<\/div>/s', '>' . $selfIntro . '</div>', $html, 1);

// ディーラー情報
$html = str_replace('>コーンズ芝ショールーム</div>', '>' . h($registration['relationship_dealer'] ?: '-') . '</div>', $html);

// 担当セールス
$html = preg_replace('/>担当セールス名<\/div>\s*<div class="registration-detail-value">-<\/div>/', 
    '>担当セールス名</div><div class="registration-detail-value">' . h($registration['sales_person'] ?: '-') . '</div>', $html, 1);

// 車両情報
$html = preg_replace('/>車種・Model名<\/div>\s*<div class="registration-detail-value">○○○○<\/div>/',
    '>車種・Model名</div><div class="registration-detail-value">' . h($registration['car_model'] ?: '-') . '</div>', $html, 1);
$html = preg_replace('/>年式<\/div>\s*<div class="registration-detail-value">○○○○<\/div>/',
    '>年式</div><div class="registration-detail-value">' . h($registration['model_year'] ? $registration['model_year'] . '年' : '-') . '</div>', $html, 1);
$html = preg_replace('/>車体色<\/div>\s*<div class="registration-detail-value">○○○○<\/div>/',
    '>車体色</div><div class="registration-detail-value">' . h($registration['car_color'] ?: '-') . '</div>', $html, 1);
$html = preg_replace('/>登録No<\/div>\s*<div class="registration-detail-value">○○○○<\/div>/',
    '>登録No</div><div class="registration-detail-value">' . h($registration['car_number'] ?: '-') . '</div>', $html, 1);

// 紹介者情報
// ご紹介者-1
$html = str_replace('>〇〇〇〇さん</div>', '>' . h($registration['referrer1'] ?: '-') . '</div>', $html);

// ご紹介者ディーラー名（正規表現で柔軟に対応）
$html = preg_replace(
    '/>ご紹介者ディーラー名<\/div>\s*<div class="registration-detail-value">.*?<\/div>/',
    '>ご紹介者ディーラー名</div><div class="registration-detail-value">' . h($registration['referrer_dealer'] ?: '-') . '</div>',
    $html
);

// ご紹介者-2（理事）（正規表現で柔軟に対応）
$html = preg_replace(
    '/>ご紹介者-2（理事）<\/div>\s*<div class="registration-detail-value">.*?<\/div>/',
    '>ご紹介者-2（理事）</div><div class="registration-detail-value">' . h($registration['referrer2'] ?: '-') . '</div>',
    $html
);

// 添付書類セクションの画像を置換
// 運転免許証
$licenseImage = $registration['license_image'] ?: $registration['drivers_license_file'];
if ($licenseImage) {
    $licenseHtml = '<img src="view-user-image.php?user_id=' . $id . '&file=' . h($licenseImage) . '" width="640" height="427" alt="">';
} else {
    $licenseHtml = '<span style="color: #999;">画像なし</span>';
}
$html = preg_replace(
    '/<div class="registration-detail-name">運転免許証<\/div>\s*<div class="registration-detail-value"><img[^>]*><\/div>/',
    '<div class="registration-detail-name">運転免許証</div>
                  <div class="registration-detail-value">' . $licenseHtml . '</div>',
    $html
);

// 車検証
$vehicleImage = $registration['vehicle_inspection_image'] ?: $registration['vehicle_inspection_file'];
if ($vehicleImage) {
    $vehicleHtml = '<img src="view-user-image.php?user_id=' . $id . '&file=' . h($vehicleImage) . '" width="480" height="288" alt="">';
} else {
    $vehicleHtml = '<span style="color: #999;">画像なし</span>';
}
$html = preg_replace(
    '/<div class="registration-detail-name">車検証<\/div>\s*<div class="registration-detail-value"><img[^>]*><\/div>/',
    '<div class="registration-detail-name">車検証</div>
                  <div class="registration-detail-value">' . $vehicleHtml . '</div>',
    $html
);

// 名刺
$businessCardImage = $registration['business_card_image'] ?: $registration['business_card_file'];
if ($businessCardImage) {
    $businessCardHtml = '<img src="view-user-image.php?user_id=' . $id . '&file=' . h($businessCardImage) . '" width="640" height="427" alt="">';
} else {
    $businessCardHtml = '<span style="color: #999;">画像なし</span>';
}
$html = preg_replace(
    '/<div class="registration-detail-name">名刺<\/div>\s*<div class="registration-detail-value">.*?<\/div>/',
    '<div class="registration-detail-name">名刺</div>
                  <div class="registration-detail-value">' . $businessCardHtml . '</div>',
    $html
);

// ボタンエリアのリンクを調整
// 戻るボタン
$html = str_replace('href="A3_registration-detail.html"', 'href="registration-detail.php?id=' . $id . '"', $html);

// 承認するボタン（編集内容を含めて送信）
$html = str_replace('href="A5_registration-approve-complete.html"', 'href="#" onclick="submitApprovalForm(); return false;"', $html);

// 承認実行用のフォームとJavaScriptを追加
$form = '
<form id="approveForm" method="POST" action="approve-handler.php" style="display: none;">
    <input type="hidden" name="id" value="' . $id . '">
    <input type="hidden" name="action" value="approve">
    <input type="hidden" name="custom_mail_content" id="customMailContent" value="">
</form>

<script>
var isEditing = false;

function adjustTextareaHeight(textarea) {
    textarea.style.height = "auto";
    textarea.style.height = textarea.scrollHeight + "px";
}

function toggleMailEdit() {
    var display = document.getElementById("mailContentDisplay");
    var edit = document.getElementById("mailContentEdit");
    var button = document.getElementById("editButton");
    
    if (!isEditing) {
        // 編集モードに切り替え
        display.style.display = "none";
        edit.style.display = "block";
        button.textContent = "編集完了";
        isEditing = true;
        
        // テキストエリアの高さを調整
        adjustTextareaHeight(edit);
        edit.focus();
        
        // 入力時に高さを自動調整
        edit.oninput = function() {
            adjustTextareaHeight(edit);
        };
    } else {
        // 表示モードに戻す
        display.style.display = "block";
        edit.style.display = "none";
        button.textContent = "編集する";
        
        // 編集内容を表示に反映
        var editedContent = edit.value;
        display.innerHTML = editedContent.replace(/\n/g, "<br>");
        isEditing = false;
    }
}

// フォーム送信前に編集内容を設定
function submitApprovalForm() {
    if (isEditing) {
        // 編集中の場合は編集を完了させる
        toggleMailEdit();
    }
    
    var editContent = document.getElementById("mailContentEdit").value;
    document.getElementById("customMailContent").value = editContent;
    
    document.getElementById("approveForm").submit();
}
</script>';

$html = str_replace('</body>', $form . '</body>', $html);

echo $html;