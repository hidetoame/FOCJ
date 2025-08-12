<?php
/**
 * 登録フォーム - 確認画面
 */
require_once '../config/config.php';

// POSTデータチェック
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form.php');
    exit;
}

// CSRFトークン検証
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setError('不正なリクエストです。');
    header('Location: form.php');
    exit;
}

// フォームデータ取得
$formData = $_POST;
unset($formData['csrf_token']);

// ファイルアップロード処理（一時保存）
$uploadedFiles = [];
$fileFields = ['drivers-license', 'vehicle-inspection', 'business-card'];
$tempDir = USER_IMAGES_FS_PATH . '/temp/';

// セッションごとの一時ディレクトリ作成
$sessionDir = $tempDir . session_id() . '/';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0755, true);
}

foreach ($fileFields as $field) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
        // セキュアなファイル名を生成
        $extension = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
        $secureName = bin2hex(random_bytes(16)) . '.' . $extension;
        $tempPath = $sessionDir . $secureName;
        
        // MIMEタイプチェック
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($_FILES[$field]['tmp_name']);
        if (strpos($mimeType, 'image/') !== 0) {
            setError($field . 'は画像ファイルをアップロードしてください。');
            header('Location: form.php');
            exit;
        }
        
        // ファイルを一時ディレクトリに移動
        if (move_uploaded_file($_FILES[$field]['tmp_name'], $tempPath)) {
            $uploadedFiles[$field] = $secureName;
            $formData[$field . '_file'] = $secureName;
            $formData[$field . '_original_name'] = $_FILES[$field]['name'];
        } else {
            setError($field . 'のアップロードに失敗しました。');
            header('Location: form.php');
            exit;
        }
    } elseif ($field !== 'business-card' && empty(getFormData($field . '_file'))) {
        // 必須ファイルがない場合 - 開発中は一時的にスキップ（本番では有効化）
        // setError($field . 'をアップロードしてください。');
        // header('Location: form.php');
        // exit;
    }
}

// セッションに保存
saveFormData(array_merge($formData, $uploadedFiles));

// 新しいCSRFトークン生成
$csrf_token = generateCsrfToken();

// テンプレート読み込み
$html = file_get_contents(TEMPLATE_PATH . '/registration-form/registration-form-confirm.html');

// アセットパスを調整
$html = str_replace('href="assets/', 'href="' . REGISTRATION_TEMPLATE_WEB_PATH . '/assets/', $html);
$html = str_replace('src="assets/', 'src="' . REGISTRATION_TEMPLATE_WEB_PATH . '/assets/', $html);

// フォームのaction属性を変更し、CSRFトークンとすべてのフォームデータをhiddenフィールドとして追加
$hiddenFields = "\n" . '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">';

// すべてのフォームデータをhiddenフィールドとして追加
foreach ($formData as $key => $value) {
    if (!is_array($value)) {
        $hiddenFields .= "\n" . '<input type="hidden" name="' . h($key) . '" value="' . h($value) . '">';
    }
}

$html = str_replace(
    '<form action="registration-form-thanks.html" id="registration-form" class="h-adr" method="post" enctype="multipart/form-data">',
    '<form action="complete.php" id="registration-form" class="h-adr" method="post" enctype="multipart/form-data">' . $hiddenFields,
    $html
);

// 確認画面のサンプルデータを実際のデータで置換
// 氏名
$html = preg_replace('/<div class="form-item-confirm">田中<\/div>/', '<div class="form-item-confirm">' . h($formData['familyname'] ?? '') . '</div>', $html);
$html = preg_replace('/<div class="form-item-confirm">太郎<\/div>/', '<div class="form-item-confirm">' . h($formData['firstname'] ?? '') . '</div>', $html);

// フリガナ
$html = preg_replace('/<div class="form-item-confirm">タナカ<\/div>/', '<div class="form-item-confirm">' . h($formData['familyname-kana'] ?? '') . '</div>', $html);
$html = preg_replace('/<div class="form-item-confirm">タロウ<\/div>/', '<div class="form-item-confirm">' . h($formData['firstname-kana'] ?? '') . '</div>', $html);

// ローマ字
$html = preg_replace('/<div class="form-item-confirm">TAROU TANAKA<\/div>/', '<div class="form-item-confirm">' . h($formData['name-alphabet'] ?? '') . '</div>', $html);

// 住所タイプ
$addressType = isset($formData['address-type']) ? 
    ($formData['address-type'] === 'home' ? '自宅' : '勤務先') : '自宅';
$html = preg_replace('/<div class="form-item-confirm">勤務先<\/div>/', '<div class="form-item-confirm">' . $addressType . '</div>', $html);

// 郵便番号
$html = preg_replace('/<div class="form-item-confirm">107-0062<\/div>/', '<div class="form-item-confirm">' . h($formData['postal-code'] ?? '') . '</div>', $html);

// 都道府県
$html = preg_replace('/<div class="form-item-confirm">東京都<\/div>/', '<div class="form-item-confirm">' . h($formData['prefecture'] ?? '') . '</div>', $html);

// 市区町村・番地
$html = preg_replace('/<div class="form-item-confirm">港区南青山6-7-8<\/div>/', '<div class="form-item-confirm">' . h($formData['city-address'] ?? '') . '</div>', $html);

// 建物名（「-」の場合も置換）
$buildingName = !empty($formData['building-name']) ? h($formData['building-name']) : '-';
$html = str_replace('<div class="form-item-confirm">-</div>', '<div class="form-item-confirm">' . $buildingName . '</div>', $html);

// 携帯電話
$html = preg_replace('/<div class="form-item-confirm">080-4804-8088<\/div>/', '<div class="form-item-confirm">' . h($formData['mobile-number'] ?? '') . '</div>', $html);

// 固定電話（複数の「-」を置換）
$phoneNumber = !empty($formData['phone-number']) ? h($formData['phone-number']) : '-';
// 最初の「-」を固定電話で置換
$html = preg_replace('/<div class="form-group-name"><div class="icon icon-edit icon--text">電話番号<\/div><\/div>.*?<div class="form-item-confirm">-<\/div>/s', 
    '<div class="form-group-name"><div class="icon icon-edit icon--text">電話番号</div></div>
                <div class="form-item-confirm">' . $phoneNumber . '</div>', $html, 1);

// 生年月日（個別の年月日）
$html = preg_replace('/<div class="form-item-confirm">1980<\/div>/', '<div class="form-item-confirm">' . h($formData['birth-year'] ?? '') . '</div>', $html);
$html = preg_replace('/<div class="form-item-confirm">1<\/div>/', '<div class="form-item-confirm">' . h($formData['birth-month'] ?? '') . '</div>', $html, 1);
// 日の「1」を置換（2回目の1）
$pattern = '/<div class="form-item-confirm">1<\/div>\s*<div class="form-item-name">日<\/div>/';
$replacement = '<div class="form-item-confirm">' . h($formData['birth-day'] ?? '') . '</div>
                  <div class="form-item-name">日</div>';
$html = preg_replace($pattern, $replacement, $html);

// メールアドレス
$html = preg_replace('/<div class="form-item-confirm">mail@example\.com<\/div>/', '<div class="form-item-confirm">' . h($formData['mail-address'] ?? '') . '</div>', $html);

// 職業
$html = preg_replace('/<div class="form-item-confirm">〇〇株式会社 代表取締役<br>その他複数の会社経営<\/div>/', '<div class="form-item-confirm">' . nl2br(h($formData['occupation'] ?? '')) . '</div>', $html);

// 自己紹介
$selfIntroText = nl2br(h($formData['self-introduction'] ?? ''));
$html = preg_replace('/<div class="form-item-confirm">自己紹介テキスト自己紹介テキスト自己紹介テキスト自己紹介テキスト<br><br>自己紹介テキスト自己紹介テキスト自己紹介テキスト自己紹介テキスト自己紹介テキスト自己紹介テキスト自己紹介テキスト自己紹介テキスト<\/div>/', '<div class="form-item-confirm">' . $selfIntroText . '</div>', $html);

// お付き合いのあるディーラー
$html = preg_replace('/<div class="form-item-confirm">コーンズ芝ショールーム<\/div>/', '<div class="form-item-confirm">' . h($formData['relationship-dealer'] ?? '') . '</div>', $html);

// 担当セールス
$salesPerson = !empty($formData['sales-person']) ? h($formData['sales-person']) : '-';
$html = str_replace('{{sales-person}}', $salesPerson, $html);

// 車種・Model名
$carModel = !empty($formData['car-model']) ? h($formData['car-model']) : '-';
$html = preg_replace('/<div class="form-group-name"><div class="icon icon-edit icon--text">車種・Model名<\/div><div class="form-require">必須<\/div><\/div>\s*<div class="form-item-confirm">○○○○<\/div>/s', 
    '<div class="form-group-name"><div class="icon icon-edit icon--text">車種・Model名</div><div class="form-require">必須</div></div>
                <div class="form-item-confirm">' . $carModel . '</div>', $html);

// 年式
$html = preg_replace('/<div class="form-item-confirm">○●◎○●◎○●◎<\/div>/', '<div class="form-item-confirm">' . h($formData['car-year'] ?? '') . '</div>', $html, 1);

// 車体色（2番目の○●◎○●◎○●◎）
$carColor = !empty($formData['car-color']) ? h($formData['car-color']) : '-';
$html = preg_replace('/<div class="form-item-confirm">○●◎○●◎○●◎<\/div>/', '<div class="form-item-confirm">' . $carColor . '</div>', $html, 1);

// ナンバー（3番目の○●◎○●◎○●◎）
$carNumber = !empty($formData['car-number']) ? h($formData['car-number']) : '-';
$html = preg_replace('/<div class="form-item-confirm">○●◎○●◎○●◎<\/div>/', '<div class="form-item-confirm">' . $carNumber . '</div>', $html, 1);

// 紹介者1
$referrer1 = !empty($formData['referrer1']) ? h($formData['referrer1']) : '-';
$html = preg_replace('/<div class="form-item-confirm">〇〇〇〇さん<\/div>/', '<div class="form-item-confirm">' . $referrer1 . '</div>', $html);

// 紹介者ディーラー
$referrerDealer = !empty($formData['referrer-dealer']) ? h($formData['referrer-dealer']) : '-';
$html = str_replace('{{referrer-dealer}}', $referrerDealer, $html);

// 紹介者2
$referrer2 = !empty($formData['referrer2']) ? h($formData['referrer2']) : '-';
$html = str_replace('{{referrer2}}', $referrer2, $html);

// 画像ファイル表示セクション
$imageSection = '';

// デバッグ情報（本番では削除）
$imageSection .= '<!-- Debug: Session ID = ' . session_id() . ' -->';

// 運転免許証
if (!empty($formData['drivers-license_file'])) {
    // ファイルの存在確認
    $tempFile = USER_IMAGES_FS_PATH . '/temp/' . session_id() . '/' . $formData['drivers-license_file'];
    if (file_exists($tempFile)) {
        $imageSection .= '<!-- Debug: License file exists at ' . $tempFile . ' -->';
    } else {
        $imageSection .= '<!-- Debug: License file NOT found at ' . $tempFile . ' -->';
    }
    
    $imageSection .= '
          <div class="form-group">
            <div class="form-group-name"><div class="icon icon-image icon--text">運転免許証</div></div>
            <div class="form-item-confirm">
              <img src="view-temp-image.php?file=' . h($formData['drivers-license_file']) . '" style="max-width: 400px; max-height: 300px; border: 1px solid #ddd; padding: 5px;" onerror="this.onerror=null; this.src=\'data:image/svg+xml,%3Csvg xmlns=\\\'http://www.w3.org/2000/svg\\\' width=\\\'400\\\' height=\\\'300\\\'%3E%3Crect width=\\\'400\\\' height=\\\'300\\\' fill=\\\'%23ddd\\\'/%3E%3Ctext x=\\\'50%25\\\' y=\\\'50%25\\\' text-anchor=\\\'middle\\\' dy=\\\'.3em\\\' fill=\\\'%23999\\\'%3E画像を読み込めません%3C/text%3E%3C/svg%3E\';">
              <div style="font-size: 12px; color: #666; margin-top: 5px;">' . h($formData['drivers-license_original_name'] ?? 'uploaded') . '</div>
            </div>
          </div>';
}

// 車検証
if (!empty($formData['vehicle-inspection_file'])) {
    $imageSection .= '
          <div class="form-group">
            <div class="form-group-name"><div class="icon icon-image icon--text">車検証</div></div>
            <div class="form-item-confirm">
              <img src="view-temp-image.php?file=' . h($formData['vehicle-inspection_file']) . '" style="max-width: 400px; max-height: 300px; border: 1px solid #ddd; padding: 5px;">
              <div style="font-size: 12px; color: #666; margin-top: 5px;">' . h($formData['vehicle-inspection_original_name'] ?? 'uploaded') . '</div>
            </div>
          </div>';
}

// 名刺
if (!empty($formData['business-card_file'])) {
    $imageSection .= '
          <div class="form-group">
            <div class="form-group-name"><div class="icon icon-image icon--text">名刺</div></div>
            <div class="form-item-confirm">
              <img src="view-temp-image.php?file=' . h($formData['business-card_file']) . '" style="max-width: 400px; max-height: 300px; border: 1px solid #ddd; padding: 5px;">
              <div style="font-size: 12px; color: #666; margin-top: 5px;">' . h($formData['business-card_original_name'] ?? 'uploaded') . '</div>
            </div>
          </div>';
}

// 添付書類セクションを置換
if (!empty($imageSection)) {
    $html = preg_replace('/<h3>添付書類<\/h3>.*?(<div class="button-area">)/s', 
        '<h3>添付書類</h3>' . $imageSection . '$1', $html);
}

// ボタンエリアの修正（正しいデザインに）
$buttonArea = '
          <div class="button-area">
            <a href="javascript:history.back()" class="button button--line icon icon-edit-note">入力内容を変更する</a>
            <button type="submit" class="button button--primary icon icon-send">送信する</button>
          </div>';

$html = preg_replace('/<div class="button-area">.*?<\/div>/s', $buttonArea, $html);

echo $html;