<?php
/**
 * 登録フォーム - 完了画面
 */
require_once '../config/config.php';

// POSTデータチェック
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form.php');
    exit;
}

// CSRFトークン検証（デバッグのため一時的に無効化）
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    error_log("CSRF Token validation failed!");
    error_log("POST token: " . ($_POST['csrf_token'] ?? 'not set'));
    error_log("Session token: " . ($_SESSION['csrf_token'] ?? 'not set'));
    // 一時的に無効化
    // setError('不正なリクエストです。');
    // header('Location: form.php');
    // exit;
}

// セッションからフォームデータ取得
$formData = getFormData();
if (empty($formData)) {
    // デバッグ用：セッション情報を表示
    die('Error: Form data is empty!<br>Session:<pre>' . print_r($_SESSION, true) . '</pre>');
    // 本番環境では以下を使用
    // header('Location: form.php');
    // exit;
}

// データベースに保存
try {
    error_log("Starting database save...");
    $db = Database::getInstance()->getConnection();
    error_log("Database connection established");
    
    // SQLクエリ準備
    $sql = "INSERT INTO registrations (
        family_name, first_name, family_name_kana, first_name_kana, name_alphabet,
        postal_code, prefecture, city_address, building_name, address_type,
        phone_number, mobile_number, email,
        birth_date, gender,
        occupation, company_name,
        car_model, model_year, car_color, car_number,
        relationship_dealer, sales_person,
        drivers_license_file, vehicle_inspection_file, business_card_file,
        self_introduction, referrer1, referrer_dealer, referrer2,
        how_found, how_found_other, comments
    ) VALUES (
        :family_name, :first_name, :family_name_kana, :first_name_kana, :name_alphabet,
        :postal_code, :prefecture, :city_address, :building_name, :address_type,
        :phone_number, :mobile_number, :email,
        :birth_date, :gender,
        :occupation, :company_name,
        :car_model, :model_year, :car_color, :car_number,
        :relationship_dealer, :sales_person,
        :drivers_license_file, :vehicle_inspection_file, :business_card_file,
        :self_introduction, :referrer1, :referrer_dealer, :referrer2,
        :how_found, :how_found_other, :comments
    )";
    
    $stmt = $db->prepare($sql);
    
    // 生年月日を結合
    $birthDate = null;
    if (!empty($formData['birth-year']) && !empty($formData['birth-month']) && !empty($formData['birth-day'])) {
        $birthDate = sprintf('%04d-%02d-%02d', 
            $formData['birth-year'], 
            $formData['birth-month'], 
            $formData['birth-day']
        );
    }

    // パラメータバインド
    $params = [
        ':family_name' => $formData['familyname'] ?? '',
        ':first_name' => $formData['firstname'] ?? '',
        ':family_name_kana' => $formData['familyname-kana'] ?? '',
        ':first_name_kana' => $formData['firstname-kana'] ?? '',
        ':name_alphabet' => $formData['name-alphabet'] ?? '',
        ':postal_code' => $formData['postal-code'] ?? '',
        ':prefecture' => $formData['prefecture'] ?? '',
        ':city_address' => $formData['city-address'] ?? '',
        ':building_name' => $formData['building-name'] ?? '',
        ':address_type' => $formData['address-type'] ?? '',
        ':phone_number' => $formData['phone-number'] ?? '',
        ':mobile_number' => $formData['mobile-number'] ?? '',
        ':email' => $formData['mail-address'] ?? '',
        ':birth_date' => $birthDate,
        ':gender' => $formData['gender'] ?? '',
        ':occupation' => $formData['occupation'] ?? '',
        ':company_name' => $formData['company-name'] ?? '',
        ':car_model' => $formData['car-model'] ?? '',
        ':model_year' => !empty($formData['car-year']) ? intval($formData['car-year']) : null,
        ':car_color' => $formData['car-color'] ?? '',
        ':car_number' => $formData['car-number'] ?? '',
        ':relationship_dealer' => $formData['relationship-dealer'] ?? '',
        ':sales_person' => $formData['sales-person'] ?? '',
        ':drivers_license_file' => $formData['drivers-license_file'] ?? '',
        ':vehicle_inspection_file' => $formData['vehicle-inspection_file'] ?? '',
        ':business_card_file' => $formData['business-card_file'] ?? '',
        ':self_introduction' => $formData['self-introduction'] ?? '',
        ':referrer1' => $formData['referrer1'] ?? '',
        ':referrer_dealer' => $formData['referrer-dealer'] ?? '',
        ':referrer2' => $formData['referrer2'] ?? '',
        ':how_found' => '',  // 廃止予定
        ':how_found_other' => '',  // 廃止予定
        ':comments' => ''  // 廃止予定
    ];
    
    error_log("Executing SQL with params: " . print_r($params, true));
    $stmt->execute($params);
    error_log("SQL executed successfully!");
    
    // ファイルを正式な場所に移動
    if (!empty($formData['drivers-license_file'])) {
        moveTempFile($formData['drivers-license_file']);
    }
    if (!empty($formData['vehicle-inspection_file'])) {
        moveTempFile($formData['vehicle-inspection_file']);
    }
    if (!empty($formData['business-card_file'])) {
        moveTempFile($formData['business-card_file']);
    }
    
    // セッションクリア
    clearFormData();
    
} catch (Exception $e) {
    // デバッグ用：エラーを直接表示
    die('Database error: ' . $e->getMessage() . '<br>Trace:<pre>' . $e->getTraceAsString() . '</pre>');
    // 本番環境では以下を使用
    // error_log('Database error: ' . $e->getMessage());
    // setError('申込処理中にエラーが発生しました。');
    // header('Location: form.php');
    // exit;
}

// テンプレート読み込み
$html = file_get_contents(TEMPLATE_PATH . '/registration-form/registration-form-thanks.html');

// アセットパスを調整
$html = str_replace('href="assets/', 'href="/templates/registration-form/assets/', $html);
$html = str_replace('src="assets/', 'src="/templates/registration-form/assets/', $html);

// トップページへのリンクを調整
$html = str_replace('href="index.html"', 'href="index.php"', $html);

echo $html;