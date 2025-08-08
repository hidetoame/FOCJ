<?php
/**
 * 管理画面 - 申請者詳細
 */
// データベース接続（config.phpがsession_start()を呼ぶ）
require_once '../config/config.php';
require_once '../includes/mail_functions.php';

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

// ステータス更新処理（否認のみ）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $username = $_SESSION['admin_username'] ?? 'admin';
    
    if ($action === 'reject') {
        // 否認処理
        $reason = $_POST['reason'] ?? '';
        $sql = "UPDATE registrations SET 
                status = 'rejected', 
                rejection_reason = :reason,
                rejected_at = NOW(),
                rejected_by = :username
                WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id' => $id, 
            ':reason' => $reason,
            ':username' => $username
        ]);
        
        // 否認メール送信
        sendRejectionEmail($db, $id, $reason);
        
        header('Location: registration-reject.php?id=' . $id);
        exit;
    }
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

// テンプレート読み込み
$html = file_get_contents('/var/www/html/templates/member-management/A3_registration-detail.html');

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

// データを置換
$html = str_replace('>山田 太郎</div>', '>' . h($registration['family_name'] . ' ' . $registration['first_name']) . '</div>', $html);
$html = str_replace('>ヤマダ タロウ</div>', '>' . h($registration['family_name_kana'] . ' ' . $registration['first_name_kana']) . '</div>', $html);
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

// 連絡先
$html = str_replace('>090-1234-5678</div>', '>' . h($registration['mobile_number']) . '</div>', $html);
$html = str_replace('>03-0000-0000</div>', '>' . h($registration['phone_number'] ?: '-') . '</div>', $html);
$html = str_replace('>example@example.com</div>', '>' . h($registration['email']) . '</div>', $html);

// 生年月日
$birthDate = $registration['birth_date'] ? date('Y年n月j日', strtotime($registration['birth_date'])) : '-';
$html = str_replace('>1975年1月 1日</div>', '>' . $birthDate . '</div>', $html);

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

// 担当セールス（最初の->を置換）
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

// 紹介者情報をデバッグ
error_log("Referrer data - referrer1: " . ($registration['referrer1'] ?? 'null'));
error_log("Referrer data - referrer_dealer: " . ($registration['referrer_dealer'] ?? 'null'));
error_log("Referrer data - referrer2: " . ($registration['referrer2'] ?? 'null'));

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
    $businessCardHtml = '<img src="view-user-image.php?user_id=' . $id . '&file=' . h($businessCardImage) . '" width="400" height="250" alt="">';
} else {
    $businessCardHtml = '-';
}
$html = preg_replace(
    '/<div class="registration-detail-name">名刺<\/div>\s*<div class="registration-detail-value">-<\/div>/',
    '<div class="registration-detail-name">名刺</div>
                  <div class="registration-detail-value">' . $businessCardHtml . '</div>',
    $html
);

// ステータス表示を更新
$statusText = '未対応';
$statusColor = '';

// 退会済みチェック
if ($registration['is_withdrawn']) {
    $statusText = '退会済';
    $statusColor = 'style="color: gray; font-weight: bold;"';
    
    // 退会日を追加表示
    if ($registration['withdrawn_at']) {
        $withdrawnDate = date('Y年n月j日', strtotime($registration['withdrawn_at']));
        $withdrawnBy = h($registration['withdrawn_by'] ?? '-');
        $statusText .= '<br><span style="font-size: 12px; font-weight: normal;">退会日: ' . $withdrawnDate . '<br>処理者: ' . $withdrawnBy . '</span>';
    }
} elseif ($registration['status'] === 'approved') {
    $statusText = '承認済';
    $statusColor = 'style="color: green; font-weight: bold;"';
} elseif ($registration['status'] === 'rejected') {
    $statusText = '否認済';
    $statusColor = 'style="color: red; font-weight: bold;"';
}
$html = preg_replace('/<div class="registration-detail-name">ステータス<\/div>\s*<div class="registration-detail-value">.*?<\/div>/',
    '<div class="registration-detail-name">ステータス</div><div class="registration-detail-value" ' . $statusColor . '>' . $statusText . '</div>', $html);

// ステータスに応じてボタンを調整
if ($registration['is_withdrawn']) {
    // 退会済みの場合
    $withdrawnBy = h($registration['withdrawn_by'] ?? 'システム');
    $withdrawnDate = $registration['withdrawn_at'] ? date('Y/m/d H:i', strtotime($registration['withdrawn_at'])) : '-';
    
    $buttons = '
        <div class="registration-detail-control-item" style="width: 100%;">
            <div style="background: #e0e0e0; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                <div style="color: gray; font-weight: bold;">退会済み</div>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                    退会日時: ' . $withdrawnDate . '<br>
                    処理者: ' . $withdrawnBy . '
                </div>
            </div>
        </div>
        <div class="registration-detail-control-item">
            <a href="registration-list.php" class="button button--line">一覧へ戻る</a>
        </div>';
    
    $html = preg_replace('/<div class="registration-detail-control button-area">.*?<\/div>\s*<\/div>/s', 
        '<div class="registration-detail-control button-area">' . $buttons . '</div></div>', $html);
        
} elseif ($registration['status'] === 'approved') {
    // 承認済みの場合
    $approvedBy = h($registration['approved_by'] ?? 'システム');
    $approvedDate = $registration['approved_at'] ? date('Y/m/d H:i', strtotime($registration['approved_at'])) : '-';
    
    $buttons = '
        <div class="registration-detail-control-item" style="width: 100%;">
            <div style="background: #e7f5e1; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                <div style="color: green; font-weight: bold;">✓ 承認済み</div>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                    承認日時: ' . $approvedDate . '<br>
                    承認者: ' . $approvedBy . '
                </div>
            </div>
        </div>
        <div class="registration-detail-control-item">
            <a href="registration-list.php" class="button button--line">一覧へ戻る</a>
        </div>';
    
    $html = preg_replace('/<div class="registration-detail-control button-area">.*?<\/div>\s*<\/div>/s', 
        '<div class="registration-detail-control button-area">' . $buttons . '</div></div>', $html);
        
} elseif ($registration['status'] === 'rejected') {
    // 否認済みの場合
    $rejectedBy = h($registration['rejected_by'] ?? 'システム');
    $rejectedDate = $registration['rejected_at'] ? date('Y/m/d H:i', strtotime($registration['rejected_at'])) : '-';
    $reason = h($registration['rejection_reason'] ?? '');
    
    $buttons = '
        <div class="registration-detail-control-item" style="width: 100%;">
            <div style="background: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                <div style="color: red; font-weight: bold;">✗ 否認済み</div>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                    否認日時: ' . $rejectedDate . '<br>
                    否認者: ' . $rejectedBy . '<br>
                    理由: ' . $reason . '
                </div>
            </div>
        </div>
        <div class="registration-detail-control-item">
            <a href="registration-list.php" class="button button--line">一覧へ戻る</a>
        </div>';
    
    $html = preg_replace('/<div class="registration-detail-control button-area">.*?<\/div>\s*<\/div>/s', 
        '<div class="registration-detail-control button-area">' . $buttons . '</div></div>', $html);
        
} else {
    // 未対応の場合 - ボタンのリンクを変更（確認画面へ）
    
    // 承認ボタンを探して置換
    $count1 = 0;
    $html = str_replace('<a href="A4_registration-approve.html" class="button button--line">承認する</a>', 
        '<a href="registration-approve.php?id=' . $id . '" class="button button--line">承認する</a>', $html, $count1);
    
    // 却下ボタンを探して置換
    $count2 = 0;
    $html = str_replace('<a href="A6_registration-reject.html" class="button button--line">却下する</a>', 
        '<a href="registration-reject.php?id=' . $id . '" class="button button--line">却下する</a>', $html, $count2);
    
    // デバッグ：置換が行われたか確認
    if ($count1 === 0 || $count2 === 0) {
        // テンプレートのボタン部分を確認
        if (strpos($html, 'A4_registration-approve.html') !== false) {
            error_log("承認ボタンは見つかったが置換されなかった");
        }
        if (strpos($html, 'A6_registration-reject.html') !== false) {
            error_log("却下ボタンは見つかったが置換されなかった");
        }
    }
}

echo $html;