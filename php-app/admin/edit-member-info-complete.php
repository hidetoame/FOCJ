<?php
/**
 * 管理画面 - 会員情報変更完了
 */
// データベース接続（config.phpがsession_start()を呼ぶ）
require_once '../config/config.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// POSTリクエストの場合はセッションのデータを使用して更新処理を実行
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // POSTでない場合は会員一覧へ
    header('Location: members-list.php');
    exit;
}

// セッションからデータを取得
$id = $_SESSION['edit_member_id'] ?? 0;
$postData = $_SESSION['edit_member_data'] ?? [];

if (!$id || empty($postData)) {
    header('Location: members-list.php');
    exit;
}

// データベース更新処理
$db = Database::getInstance()->getConnection();

$sql = "UPDATE registrations SET 
        family_name = :family_name,
        first_name = :first_name,
        family_name_kana = :family_name_kana,
        first_name_kana = :first_name_kana,
        name_alphabet = :name_alphabet,
        postal_code = :postal_code,
        prefecture = :prefecture,
        city_address = :city_address,
        building_name = :building_name,
        address_type = :address_type,
        mobile_number = :mobile_number,
        phone_number = :phone_number,
        email = :email,
        birth_date = :birth_date,
        gender = :gender,
        occupation = :occupation,
        company_name = :company_name,
        car_model = :car_model,
        model_year = :model_year,
        car_color = :car_color,
        car_number = :car_number,
        relationship_dealer = :relationship_dealer,
        sales_person = :sales_person,
        self_introduction = :self_introduction,
        referrer1 = :referrer1,
        referrer_dealer = :referrer_dealer,
        referrer2 = :referrer2
    WHERE id = :id";

$stmt = $db->prepare($sql);

// 生年月日を結合
$birthDate = null;
if (!empty($postData['birth-year']) && !empty($postData['birth-month']) && !empty($postData['birth-day'])) {
    $birthDate = sprintf('%04d-%02d-%02d', 
        $postData['birth-year'], 
        $postData['birth-month'], 
        $postData['birth-day']
    );
}

$params = [
    ':id' => $id,
    ':family_name' => $postData['familyname'] ?? '',
    ':first_name' => $postData['firstname'] ?? '',
    ':family_name_kana' => $postData['familyname-kana'] ?? '',
    ':first_name_kana' => $postData['firstname-kana'] ?? '',
    ':name_alphabet' => $postData['name-alphabet'] ?? '',
    ':postal_code' => $postData['postal-code'] ?? '',
    ':prefecture' => $postData['prefecture'] ?? '',
    ':city_address' => $postData['city-address'] ?? '',
    ':building_name' => $postData['building-name'] ?? '',
    ':address_type' => $postData['address-type'] ?? 'home',
    ':mobile_number' => $postData['mobile-number'] ?? '',
    ':phone_number' => $postData['phone-number'] ?? '',
    ':email' => $postData['mail-address'] ?? '',
    ':birth_date' => $birthDate,
    ':gender' => $postData['gender'] ?? null,
    ':occupation' => $postData['occupation'] ?? '',
    ':company_name' => $postData['company-name'] ?? '',
    ':car_model' => $postData['car-model'] ?? '',
    ':model_year' => $postData['model-year'] ?? null,
    ':car_color' => $postData['car-color'] ?? '',
    ':car_number' => $postData['car-number'] ?? '',
    ':relationship_dealer' => $postData['relationship-dealer'] ?? '',
    ':sales_person' => $postData['sales-person'] ?? '',
    ':self_introduction' => $postData['self-introduction'] ?? '',
    ':referrer1' => $postData['referrer1'] ?? '',
    ':referrer_dealer' => $postData['referrer-dealer'] ?? '',
    ':referrer2' => $postData['referrer2'] ?? ''
];

$stmt->execute($params);

// セッションをクリア
unset($_SESSION['edit_member_data']);
unset($_SESSION['edit_member_id']);

// テンプレート読み込み
$html = file_get_contents('/var/www/html/templates/member-management/C5_edit-member-info-complete.html');

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

// 会員詳細へのリンクを調整
$html = str_replace('href="C2_member-detail.html"', 'href="member-detail.php?id=' . $id . '"', $html);

// 会員番号を表示
$memberNumber = substr('FOCJ-' . str_pad($id, 5, '0', STR_PAD_LEFT), -4);
$html = str_replace('会員番号：2000', '会員番号：' . h($memberNumber), $html);

echo $html;