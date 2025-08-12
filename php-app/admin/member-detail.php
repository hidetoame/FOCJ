<?php
/**
 * 管理画面 - 会員詳細
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

// 承認済み会員データを取得
$sql = "SELECT * FROM registrations WHERE id = :id AND status = 'approved'";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    header('Location: members-list.php');
    exit;
}

// 会費情報の取得は削除（会費管理は別画面で行うため）

// テンプレート読み込み
$html = file_get_contents(getTemplateFilePath('member-management/C2_member-detail.html'));

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

// 会員番号（データベースから取得）
if ($member['member_number']) {
    $memberNumber = 'FOCJ-' . str_pad($member['member_number'], 5, '0', STR_PAD_LEFT);
} else {
    $memberNumber = '未割当';
}
$html = str_replace('>2000</div>', '>' . $memberNumber . '</div>', $html);

// 氏名
$html = str_replace('>山田 太郎</div>', '>' . h($member['family_name'] . ' ' . $member['first_name']) . '</div>', $html);

// フリガナ
$html = str_replace('>ヤマダ タロウ</div>', '>' . h($member['family_name_kana'] . ' ' . $member['first_name_kana']) . '</div>', $html);

// ローマ字
$html = str_replace('>TAROU YAMADA</div>', '>' . h($member['name_alphabet']) . '</div>', $html);

// 添付書類セクションの画像を置換
// 運転免許証
$licenseImage = $member['license_image'] ?: $member['drivers_license_file'];
if ($licenseImage) {
    $licenseHtml = '<img src="view-user-image.php?user_id=' . $id . '&file=' . h($licenseImage) . '" width="640" height="427" alt="">';
} else {
    $licenseHtml = '-';
}
$html = preg_replace(
    '/<div class="member-detail-name">運転免許証<\/div>\s*<div class="member-detail-value"><img[^>]*><\/div>/',
    '<div class="member-detail-name">運転免許証</div>
                  <div class="member-detail-value">' . $licenseHtml . '</div>',
    $html
);

// 車検証
$vehicleImage = $member['vehicle_inspection_image'] ?: $member['vehicle_inspection_file'];
if ($vehicleImage) {
    $vehicleHtml = '<img src="view-user-image.php?user_id=' . $id . '&file=' . h($vehicleImage) . '" width="480" height="288" alt="">';
} else {
    $vehicleHtml = '-';
}
$html = preg_replace(
    '/<div class="member-detail-name">車検証<\/div>\s*<div class="member-detail-value"><img[^>]*><\/div>/',
    '<div class="member-detail-name">車検証</div>
                  <div class="member-detail-value">' . $vehicleHtml . '</div>',
    $html
);

// 名刺
$businessCardImage = $member['business_card_image'] ?: $member['business_card_file'];
if ($businessCardImage) {
    $businessCardHtml = '<img src="view-user-image.php?user_id=' . $id . '&file=' . h($businessCardImage) . '" width="400" height="250" alt="">';
} else {
    $businessCardHtml = '-';
}
$html = preg_replace(
    '/<div class="member-detail-name">名刺<\/div>\s*<div class="member-detail-value">-<\/div>/',
    '<div class="member-detail-name">名刺</div>
                  <div class="member-detail-value">' . $businessCardHtml . '</div>',
    $html
);

// 住所
$address = '〒' . h($member['postal_code']) . '<br>' . h($member['prefecture'] . $member['city_address']);
if ($member['building_name']) {
    $address .= ' ' . h($member['building_name']);
}
$html = str_replace('>〒160-0022<br>東京都新宿区1-1-1 〇〇〇〇ビル 23F</div>', '>' . $address . '</div>', $html);

// 住所種別
$addressType = $member['address_type'] === 'home' ? '自宅' : '勤務先';
$html = str_replace('>勤務先</div>', '>' . $addressType . '</div>', $html);

// 連絡先
$html = str_replace('>090-1234-5678</div>', '>' . h($member['mobile_number']) . '</div>', $html);
$html = str_replace('>03-0000-0000</div>', '>' . h($member['phone_number'] ?: '-') . '</div>', $html);
$html = str_replace('>example@example.com</div>', '>' . h($member['email']) . '</div>', $html);

// 生年月日
$birthDate = $member['birth_date'] ? date('Y年n月j日', strtotime($member['birth_date'])) : '-';
$html = str_replace('>1975年1月 1日</div>', '>' . $birthDate . '</div>', $html);

// 職業・会社
$html = str_replace('>〇〇株式会社 代表取締役<br>その他複数の会社経営</div>', 
    '>' . h($member['occupation']) . 
    ($member['company_name'] ? '<br>' . h($member['company_name']) : '') . '</div>', $html);

// 自己紹介
$selfIntro = nl2br(h($member['self_introduction'] ?: '-'));
$html = preg_replace('/>自己紹介テキスト.*?<\/div>/s', '>' . $selfIntro . '</div>', $html, 1);

// 車両情報
$html = preg_replace('/>車種・Model名<\/div>\s*<div class="member-detail-value">○○○○<\/div>/',
    '>車種・Model名</div><div class="member-detail-value">' . h($member['car_model'] ?: '-') . '</div>', $html, 1);
$html = preg_replace('/>年式<\/div>\s*<div class="member-detail-value">○○○○<\/div>/',
    '>年式</div><div class="member-detail-value">' . h($member['model_year'] ? $member['model_year'] . '年' : '-') . '</div>', $html, 1);
$html = preg_replace('/>車体色<\/div>\s*<div class="member-detail-value">○○○○<\/div>/',
    '>車体色</div><div class="member-detail-value">' . h($member['car_color'] ?: '-') . '</div>', $html, 1);
$html = preg_replace('/>登録No<\/div>\s*<div class="member-detail-value">○○○○<\/div>/',
    '>登録No</div><div class="member-detail-value">' . h($member['car_number'] ?: '-') . '</div>', $html, 1);

// ディーラー・担当セールス
$html = str_replace('>コーンズ芝ショールーム</div>', '>' . h($member['relationship_dealer'] ?: '-') . '</div>', $html);
$html = preg_replace('/>担当セールス名<\/div>\s*<div class="member-detail-value">-<\/div>/', 
    '>担当セールス名</div><div class="member-detail-value">' . h($member['sales_person'] ?: '-') . '</div>', $html, 1);

// 紹介者情報
$html = str_replace('>〇〇〇〇さん</div>', '>' . h($member['referrer1'] ?: '-') . '</div>', $html);

// ご紹介者ディーラー名（正規表現で柔軟に対応）
$html = preg_replace(
    '/>ご紹介者ディーラー名<\/div>\s*<div class="member-detail-value">.*?<\/div>/',
    '>ご紹介者ディーラー名</div><div class="member-detail-value">' . h($member['referrer_dealer'] ?: '-') . '</div>',
    $html
);

// ご紹介者-2（理事）（正規表現で柔軟に対応）
$html = preg_replace(
    '/>ご紹介者-2（理事）<\/div>\s*<div class="member-detail-value">.*?<\/div>/',
    '>ご紹介者-2（理事）</div><div class="member-detail-value">' . h($member['referrer2'] ?: '-') . '</div>',
    $html
);

// 申込年月日
$applicationDate = $member['created_at'] ? date('Y年n月j日', strtotime($member['created_at'])) : '-';
$html = str_replace('>2025年1月1日</div>', '>' . $applicationDate . '</div>', $html);

// 承認年月日
$approvalDate = $member['approved_at'] ? date('Y年n月j日', strtotime($member['approved_at'])) : '-';
$html = str_replace('>2025年1月5日</div>', '>' . $approvalDate . '</div>', $html);

// 編集ボタンのリンクを調整
$html = str_replace('href="C3_edit-member-info.html"', 'href="edit-member-info.php?id=' . $id . '"', $html);

// 退会ボタンのリンクを調整
$html = str_replace('href="C6_remove-member.html"', 'href="remove-member.php?id=' . $id . '"', $html);

// 戻るボタンのリンクを調整
$html = str_replace('href="C1_members-list.html"', 'href="members-list.php"', $html);

// 入会金・年会費のセクションは削除しない（テンプレートに残す）

echo $html;