<?php
/**
 * 管理画面 - 会員情報編集
 */
// データベース接続（config.phpがsession_start()を呼ぶ）
require_once '../config/config.php';

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

// 会員データを取得
$sql = "SELECT * FROM registrations WHERE id = :id AND status = 'approved'";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    header('Location: members-list.php');
    exit;
}


// テンプレート読み込み
$html = file_get_contents('/var/www/html/templates/member-management/C3_edit-member-info.html');

// アセットパスを調整
$html = str_replace('href="assets/', 'href="/templates/member-management/assets/', $html);
$html = str_replace('src="assets/', 'src="/templates/member-management/assets/', $html);

// ユーザー名を表示
$username = $_SESSION['admin_username'] ?? 'admin';
$html = str_replace('username01', h($username), $html);

// ログアウトリンクを調整
$html = str_replace('action="0_login.html"', 'action="logout.php"', $html);

// メニューリンクを調整
$html = str_replace('href="A2_registration-list.html"', 'href="registration-list.php"', $html);
$html = str_replace('href="B1_edit-mail-index.html"', 'href="edit-mail.php"', $html);
$html = str_replace('href="C1_members-list.html"', 'href="members-list.php"', $html);

// フォームのアクションを調整（確認画面へ）
$html = str_replace('action="C4_edit-member-info-confirm.html"', 'action="edit-member-info-confirm.php?id=' . $id . '"', $html);
// formタグにenctype追加（method="post"の部分を置換）
$html = str_replace('<form action="edit-member-info-confirm.php?id=' . $id . '" method="post">', 
                    '<form action="edit-member-info-confirm.php?id=' . $id . '" method="post" enctype="multipart/form-data">', 
                    $html);

// 添付書類セクションの更新
// 運転免許証
$licenseImage = $member['license_image'] ?: $member['drivers_license_file'];
if ($licenseImage) {
    $licenseHtml = '<a href="view-user-image.php?user_id=' . $id . '&file=' . h($licenseImage) . '" class="button button--line" target="_blank">現在の運転免許証を確認する</a>';
    $html = str_replace(
        '<a href="/templates/member-management/assets/img/dummy_drivers-license.jpg" class="button button--line" target="_blank">現在の運転免許証を確認する</a>',
        $licenseHtml,
        $html
    );
} else {
    $html = str_replace(
        '<a href="/templates/member-management/assets/img/dummy_drivers-license.jpg" class="button button--line" target="_blank">現在の運転免許証を確認する</a>',
        '<a class="button button--line button--disable" target="_blank">運転免許証がアップロードされていません</a>',
        $html
    );
}

// 車検証
$vehicleImage = $member['vehicle_inspection_image'] ?: $member['vehicle_inspection_file'];
if ($vehicleImage) {
    $vehicleHtml = '<a href="view-user-image.php?user_id=' . $id . '&file=' . h($vehicleImage) . '" class="button button--line" target="_blank">現在の車検証を確認する</a>';
    $html = str_replace(
        '<a href="/templates/member-management/assets/img/dummy_vehicle-inspection.png" class="button button--line" target="_blank">現在の車検証を確認する</a>',
        $vehicleHtml,
        $html
    );
} else {
    $html = str_replace(
        '<a href="/templates/member-management/assets/img/dummy_vehicle-inspection.png" class="button button--line" target="_blank">現在の車検証を確認する</a>',
        '<a class="button button--line button--disable" target="_blank">車検証がアップロードされていません</a>',
        $html
    );
}

// 名刺
$businessCardImage = $member['business_card_image'] ?: $member['business_card_file'];
if ($businessCardImage) {
    $businessCardHtml = '<a href="view-user-image.php?user_id=' . $id . '&file=' . h($businessCardImage) . '" class="button button--line" target="_blank">現在の名刺を確認する</a>';
    // 名刺は元々disableだったので、そのパターンを探して置換
    $html = str_replace(
        '<a class="button button--line button--disable" target="_blank">名刺がアップロードされていません</a>',
        $businessCardHtml,
        $html
    );
}

// 会員番号（IDの下4桁）
$memberNumber = substr('FOCJ-' . str_pad($id, 5, '0', STR_PAD_LEFT), -4);
$html = str_replace('value="2000"', 'value="' . h($memberNumber) . '"', $html);

// 会員データを入力フィールドに設定
// 氏名
$html = preg_replace('/name="familyname"\s+value="[^"]*"/', 'name="familyname" value="' . h($member['family_name']) . '"', $html);
$html = preg_replace('/name="firstname"\s+value="[^"]*"/', 'name="firstname" value="' . h($member['first_name']) . '"', $html);

// フリガナ
$html = preg_replace('/name="familyname-kana"\s+value="[^"]*"/', 'name="familyname-kana" value="' . h($member['family_name_kana']) . '"', $html);
$html = preg_replace('/name="firstname-kana"\s+value="[^"]*"/', 'name="firstname-kana" value="' . h($member['first_name_kana']) . '"', $html);

// ローマ字
$html = preg_replace('/name="name-alphabet"\s+value="[^"]*"/', 'name="name-alphabet" value="' . h($member['name_alphabet']) . '"', $html);

// 郵便番号・住所
$html = preg_replace('/name="postal-code"\s+value="[^"]*"/', 'name="postal-code" value="' . h($member['postal_code']) . '"', $html);
$html = preg_replace('/name="prefecture"\s+value="[^"]*"/', 'name="prefecture" value="' . h($member['prefecture']) . '"', $html);
$html = preg_replace('/name="city-address"\s+value="[^"]*"/', 'name="city-address" value="' . h($member['city_address']) . '"', $html);
$html = preg_replace('/name="building-name"\s+value="[^"]*"/', 'name="building-name" value="' . h($member['building_name']) . '"', $html);

// 住所種別のラジオボタン
if ($member['address_type'] === 'home') {
    $html = str_replace('name="address-type" value="home"', 'name="address-type" value="home" checked', $html);
} else {
    $html = str_replace('name="address-type" value="work"', 'name="address-type" value="work" checked', $html);
}

// 連絡先
$html = preg_replace('/name="mobile-number"\s+value="[^"]*"/', 'name="mobile-number" value="' . h($member['mobile_number']) . '"', $html);
$html = preg_replace('/name="phone-number"\s+value="[^"]*"/', 'name="phone-number" value="' . h($member['phone_number']) . '"', $html);
$html = preg_replace('/name="mail-address"\s+value="[^"]*"/', 'name="mail-address" value="' . h($member['email']) . '"', $html);

// 生年月日
if ($member['birth_date']) {
    $birthParts = explode('-', $member['birth_date']);
    $html = preg_replace('/name="birth-year"\s+value="[^"]*"/', 'name="birth-year" value="' . $birthParts[0] . '"', $html);
    $html = preg_replace('/name="birth-month"\s+value="[^"]*"/', 'name="birth-month" value="' . intval($birthParts[1]) . '"', $html);
    $html = preg_replace('/name="birth-day"\s+value="[^"]*"/', 'name="birth-day" value="' . intval($birthParts[2]) . '"', $html);
}

// 性別のラジオボタン
if ($member['gender'] === 'male') {
    $html = str_replace('name="gender" value="male"', 'name="gender" value="male" checked', $html);
} elseif ($member['gender'] === 'female') {
    $html = str_replace('name="gender" value="female"', 'name="gender" value="female" checked', $html);
}

// 職業・会社
$html = preg_replace('/name="occupation"[^>]*>.*?<\/textarea>/s', 
    'name="occupation">' . h($member['occupation']) . '</textarea>', $html);
$html = preg_replace('/name="company-name"\s+value="[^"]*"/', 'name="company-name" value="' . h($member['company_name']) . '"', $html);

// 自己紹介
$html = preg_replace('/name="self-introduction"[^>]*>.*?<\/textarea>/s', 
    'name="self-introduction">' . h($member['self_introduction']) . '</textarea>', $html);

// ディーラー情報と担当セールス
// ディーラー選択リストの選択状態を設定
if ($member['relationship_dealer']) {
    $html = str_replace('value="' . h($member['relationship_dealer']) . '">', 
                       'value="' . h($member['relationship_dealer']) . '" selected>', $html);
}
// 担当セールス名を設定（name属性も追加）
$html = preg_replace('/<input([^>]*?)id="sales-person"([^>]*?)\/?>/', 
    '<input$1id="sales-person" name="sales-person"$2 value="' . h($member['sales_person']) . '" />', $html);

// 車両情報
// car-model - name属性を追加し、value属性を置換
$html = str_replace('id="car-model" value="○○○○"', 
    'id="car-model" name="car-model" value="' . h($member['car_model']) . '"', $html);

// 年式 - nameをmodel-yearに変更し、valueを置換
$html = str_replace('name="car-year"', 'name="model-year"', $html);
$html = str_replace('id="car-year"', 'id="model-year"', $html);
$html = preg_replace('/name="model-year"\s+value="○○○○"/', 
    'name="model-year" value="' . h($member['model_year']) . '"', $html);

// 車体色
$html = preg_replace('/name="car-color"\s+value="○○○○"/', 
    'name="car-color" value="' . h($member['car_color']) . '"', $html);

// 登録No
$html = preg_replace('/name="car-number"\s+value="○○○○"/', 
    'name="car-number" value="' . h($member['car_number']) . '"', $html);

// 紹介者情報
// ご紹介者-1
$html = preg_replace('/name="referrer1"\s+value="[^"]*"/', 'name="referrer1" value="' . h($member['referrer1']) . '"', $html);

// ご紹介者ディーラー名の選択状態を設定
if ($member['referrer_dealer']) {
    // 既存のオプションを探して選択状態にする
    $pattern = '/<select([^>]*?)name="referrer-dealer"([^>]*?)>(.*?)<\/select>/s';
    $html = preg_replace_callback($pattern, function($matches) use ($member) {
        $selectContent = $matches[3];
        // 該当するオプションにselectedを追加
        $selectContent = str_replace('value="' . h($member['referrer_dealer']) . '"', 
                                    'value="' . h($member['referrer_dealer']) . '" selected', 
                                    $selectContent);
        return '<select' . $matches[1] . 'name="referrer-dealer"' . $matches[2] . '>' . $selectContent . '</select>';
    }, $html);
}

// ご紹介者-2（理事）- value属性がない場合も対応
$html = preg_replace('/<input([^>]*?)id="referrer2"([^>]*?)name="referrer2"([^>]*?)\/?>/', 
    '<input$1id="referrer2"$2name="referrer2"$3 value="' . h($member['referrer2']) . '" />', $html);

// キャンセルボタンのリンクを調整
$html = str_replace('href="C2_member-detail.html"', 'href="member-detail.php?id=' . $id . '"', $html);

// 入会金・年会費のセクションは削除済み（テンプレートファイルから直接削除）

echo $html;