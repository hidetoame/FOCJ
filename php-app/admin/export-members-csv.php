<?php
/**
 * 管理画面 - 承認済み会員CSVエクスポート
 */
// データベース接続（config.phpがsession_start()を呼ぶ）
require_once '../config/config.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// 承認済みかつ退会していない会員のみを取得
$sql = "SELECT 
        member_number,
        approved_at,
        family_name,
        first_name,
        family_name_kana,
        first_name_kana,
        name_alphabet,
        postal_code,
        prefecture,
        city_address,
        building_name,
        address_type,
        mobile_number,
        phone_number,
        email,
        birth_date,
        occupation,
        company_name,
        self_introduction,
        relationship_dealer,
        sales_person,
        car_model,
        model_year,
        car_color,
        car_number,
        referrer1,
        referrer_dealer,
        referrer2,
        created_at
    FROM registrations 
    WHERE status = 'approved' AND is_withdrawn = FALSE
    ORDER BY approved_at DESC, id DESC";

$stmt = $db->prepare($sql);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CSVファイル名（日付付き）
$filename = 'focj_members_' . date('YmdHis') . '.csv';

// HTTPヘッダーを設定
header('Content-Type: text/csv; charset=Shift_JIS');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');

// 出力バッファリングを開始
$output = fopen('php://output', 'w');

// CSVヘッダー行を書き込み
$headers = [
    '会員番号',
    '承認日',
    '姓',
    '名',
    '姓（フリガナ）',
    '名（フリガナ）',
    'ローマ字',
    '郵便番号',
    '都道府県',
    '市区町村番地',
    '建物名',
    '住所種別',
    '携帯電話',
    '固定電話',
    'メールアドレス',
    '生年月日',
    '職業',
    '会社名',
    '自己紹介',
    'ディーラー名',
    '担当セールス',
    '車種・モデル名',
    '年式',
    '車体色',
    '登録番号',
    '紹介者1',
    '紹介者ディーラー',
    '紹介者2（理事）',
    '申込日'
];

// UTF-8からShift_JISに変換して出力
$converted_headers = array_map(function($header) {
    return mb_convert_encoding($header, 'SJIS-win', 'UTF-8');
}, $headers);
fputcsv($output, $converted_headers);

// データ行を書き込み
foreach ($members as $member) {
    // 承認日フォーマット
    $approved_date = $member['approved_at'] ? date('Y/m/d', strtotime($member['approved_at'])) : '';
    
    // 生年月日フォーマット
    $birth_date = $member['birth_date'] ? date('Y/m/d', strtotime($member['birth_date'])) : '';
    
    // 申込日フォーマット
    $created_date = $member['created_at'] ? date('Y/m/d', strtotime($member['created_at'])) : '';
    
    // 住所種別
    $address_type = $member['address_type'] === 'home' ? '自宅' : '勤務先';
    
    // 年式
    $model_year = $member['model_year'] ? $member['model_year'] . '年' : '';
    
    // 会員番号をフォーマット
    $memberNumber = '';
    if ($member['member_number']) {
        $memberNumber = 'FOCJ-' . str_pad($member['member_number'], 5, '0', STR_PAD_LEFT);
    }
    
    $row = [
        $memberNumber,
        $approved_date,
        $member['family_name'],
        $member['first_name'],
        $member['family_name_kana'],
        $member['first_name_kana'],
        $member['name_alphabet'],
        $member['postal_code'],
        $member['prefecture'],
        $member['city_address'],
        $member['building_name'] ?: '',
        $address_type,
        $member['mobile_number'],
        $member['phone_number'] ?: '',
        $member['email'],
        $birth_date,
        $member['occupation'] ?: '',
        $member['company_name'] ?: '',
        $member['self_introduction'] ?: '',
        $member['relationship_dealer'] ?: '',
        $member['sales_person'] ?: '',
        $member['car_model'] ?: '',
        $model_year,
        $member['car_color'] ?: '',
        $member['car_number'] ?: '',
        $member['referrer1'] ?: '',
        $member['referrer_dealer'] ?: '',
        $member['referrer2'] ?: '',
        $created_date
    ];
    
    // UTF-8からShift_JISに変換して出力
    $converted_row = array_map(function($field) {
        return mb_convert_encoding($field, 'SJIS-win', 'UTF-8');
    }, $row);
    fputcsv($output, $converted_row);
}

fclose($output);
exit;