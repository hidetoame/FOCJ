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

// ファイルアップロード処理
$uploadedFiles = [];
$fileFields = ['drivers-license', 'vehicle-inspection', 'business-card'];

foreach ($fileFields as $field) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
        $filename = uploadFile($_FILES[$field], $field);
        if ($filename) {
            $uploadedFiles[$field] = $filename;
            $formData[$field . '_file'] = $filename;
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
$html = str_replace('href="assets/', 'href="/templates/registration-form/assets/', $html);
$html = str_replace('src="assets/', 'src="/templates/registration-form/assets/', $html);

// フォームのaction属性を変更し、CSRFトークンを追加
// 一度の置換で両方を処理
$hiddenFields = "\n" . '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">';
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
    ($formData['address-type'] === 'address-home' ? '自宅' : '勤務先') : '自宅';
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

// 運転免許証
if (!empty($formData['drivers-license_file'])) {
    $imageSection .= '
          <div class="form-group">
            <div class="form-group-name"><div class="icon icon-image icon--text">運転免許証</div></div>
            <div class="form-item-confirm">
              <img src="/uploads/temp/' . h($formData['drivers-license_file']) . '" style="max-width: 400px; max-height: 300px; border: 1px solid #ddd; padding: 5px;">
            </div>
          </div>';
}

// 車検証
if (!empty($formData['vehicle-inspection_file'])) {
    $imageSection .= '
          <div class="form-group">
            <div class="form-group-name"><div class="icon icon-image icon--text">車検証</div></div>
            <div class="form-item-confirm">
              <img src="/uploads/temp/' . h($formData['vehicle-inspection_file']) . '" style="max-width: 400px; max-height: 300px; border: 1px solid #ddd; padding: 5px;">
            </div>
          </div>';
}

// 名刺
if (!empty($formData['business-card_file'])) {
    $imageSection .= '
          <div class="form-group">
            <div class="form-group-name"><div class="icon icon-image icon--text">名刺</div></div>
            <div class="form-item-confirm">
              <img src="/uploads/temp/' . h($formData['business-card_file']) . '" style="max-width: 400px; max-height: 300px; border: 1px solid #ddd; padding: 5px;">
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