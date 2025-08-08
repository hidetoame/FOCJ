<?php
/**
 * 管理画面 - 会員情報変更確認
 */
// データベース接続（config.phpがsession_start()を呼ぶ）
require_once '../config/config.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// IDチェック
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: members-list.php');
    exit;
}

// POSTデータがない場合は編集画面へ戻る
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: edit-member-info.php?id=' . $id);
    exit;
}

// POSTデータをセッションに保存（完了処理で使用）
$_SESSION['edit_member_data'] = $_POST;
$_SESSION['edit_member_id'] = $id;

// テンプレート読み込み
$html = file_get_contents('/var/www/html/templates/member-management/C4_edit-member-info-confirm.html');

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

// フォームのアクションを調整し、hidden fieldを追加（C4をedit-member-info-complete.phpに変更）
$html = str_replace('action="C4_edit-member-info-confirm.html"', 'action="edit-member-info-complete.php"', $html);
// フォーム内にhidden IDフィールドを追加（フォームタグの直後に挿入）
$html = str_replace('<form', '<form', $html);
$html = preg_replace('/(<form[^>]*>)/', '$1<input type="hidden" name="confirm" value="1">', $html, 1);

// 会員番号（IDの下4桁）
$memberNumber = substr('FOCJ-' . str_pad($id, 5, '0', STR_PAD_LEFT), -4);
$html = str_replace('<div class="form-item-confirm">2000</div>', '<div class="form-item-confirm">' . h($memberNumber) . '</div>', $html);

// POSTデータから各フィールドの値を設定
// 氏名（姓・名）
$html = str_replace('>山田</div>', '>' . h($_POST['familyname']) . '</div>', $html);
$html = str_replace('>太郎</div>', '>' . h($_POST['firstname']) . '</div>', $html);

// フリガナ（姓・名）
$html = str_replace('>ヤマダ</div>', '>' . h($_POST['familyname-kana']) . '</div>', $html);
$html = str_replace('>タロウ</div>', '>' . h($_POST['firstname-kana']) . '</div>', $html);

// ローマ字
$html = str_replace('>TAROU YAMADA</div>', '>' . h($_POST['name-alphabet']) . '</div>', $html);

// 郵便番号・住所
$html = str_replace('>160-0022</div>', '>' . h($_POST['postal-code']) . '</div>', $html);
$html = str_replace('>東京都</div>', '>' . h($_POST['prefecture']) . '</div>', $html);
$html = str_replace('>東京都新宿区1-1-1</div>', '>' . h($_POST['prefecture'] . $_POST['city-address']) . '</div>', $html);
$html = str_replace('>〇〇〇〇ビル 23F</div>', '>' . h($_POST['building-name'] ?: '-') . '</div>', $html);

// 住所種別
$addressType = $_POST['address-type'] === 'home' ? '自宅' : '勤務先';
$html = str_replace('>勤務先</div>', '>' . $addressType . '</div>', $html);

// 連絡先
$html = str_replace('>090-1234-5678</div>', '>' . h($_POST['mobile-number']) . '</div>', $html);
$html = str_replace('>03-0000-0000</div>', '>' . h($_POST['phone-number'] ?: '-') . '</div>', $html);

// 生年月日（年月日を個別に設定）
if (!empty($_POST['birth-year'])) {
    $html = str_replace('>1975</div>', '>' . h($_POST['birth-year']) . '</div>', $html);
}
if (!empty($_POST['birth-month'])) {
    // 月の最初の出現箇所を置換
    $html = preg_replace('/>1<\/div>(\s*<div class="form-item-name">月<\/div>)/', '>' . h($_POST['birth-month']) . '</div>$1', $html, 1);
}
if (!empty($_POST['birth-day'])) {
    // 日の最初の出現箇所を置換
    $html = preg_replace('/>1<\/div>(\s*<div class="form-item-name">日<\/div>)/', '>' . h($_POST['birth-day']) . '</div>$1', $html, 1);
}

// メールアドレス
$html = str_replace('>example@example.com</div>', '>' . h($_POST['mail-address']) . '</div>', $html);

// 職業・会社名（一つのフィールドに結合）
$occupation = h($_POST['occupation']);
if (!empty($_POST['company-name'])) {
    $occupation .= '<br>' . h($_POST['company-name']);
}
$html = str_replace('>〇〇株式会社 代表取締役<br>その他複数の会社経営</div>', '>' . $occupation . '</div>', $html);

// 自己紹介
$selfIntro = nl2br(h($_POST['self-introduction'] ?: '-'));
$html = preg_replace('/>自己紹介テキスト.*?<\/div>/s', '>' . $selfIntro . '</div>', $html, 1);

// ディーラー・担当セールス
$html = str_replace('>コーンズ芝ショールーム</div>', '>' . h($_POST['relationship-dealer'] ?: '-') . '</div>', $html);
// 担当セールス名の-を置換（form-item-confirm内）
$html = preg_replace('/>担当セールス名<\/div>\s*<div class="form-item-confirm">-<\/div>/', 
    '>担当セールス名</div><div class="form-item-confirm">' . h($_POST['sales-person'] ?: '-') . '</div>', $html, 1);

// 車両情報（○○○○を単純に置換）
$pattern = '/>車種・Model名.*?<\/div>\s*<div class="form-item-confirm">○○○○<\/div>/s';
$html = preg_replace($pattern, 
    '>車種・Model名<div class="form-require">必須</div></div>
                          <div class="form-item-confirm">' . h($_POST['car-model'] ?: '-') . '</div>', $html, 1);

$pattern = '/>年式.*?<\/div>\s*<div class="form-item-confirm">○○○○<\/div>/s';
$html = preg_replace($pattern,
    '>年式<div class="form-require">必須</div></div>
                          <div class="form-item-confirm">' . h($_POST['model-year'] ? $_POST['model-year'] . '年' : '-') . '</div>', $html, 1);

$pattern = '/>車体色.*?<\/div>\s*<div class="form-item-confirm">○○○○<\/div>/s';
$html = preg_replace($pattern,
    '>車体色<div class="form-require">必須</div></div>
                          <div class="form-item-confirm">' . h($_POST['car-color'] ?: '-') . '</div>', $html, 1);

$pattern = '/>登録No.*?<\/div>\s*<div class="form-item-confirm">○○○○<\/div>/s';
$html = preg_replace($pattern,
    '>登録No<div class="form-require">必須</div></div>
                          <div class="form-item-confirm">' . h($_POST['car-number'] ?: '-') . '</div>', $html, 1);

// 紹介者情報
$html = str_replace('>〇〇〇〇さん</div>', '>' . h($_POST['referrer1'] ?: '-') . '</div>', $html);
// ご紹介者ディーラー名の-を置換
$html = preg_replace(
    '/>ご紹介者ディーラー名<\/div>\s*<div class="form-item-confirm">-<\/div>/',
    '>ご紹介者ディーラー名</div><div class="form-item-confirm">' . h($_POST['referrer-dealer'] ?: '-') . '</div>',
    $html, 1
);
// ご紹介者-2（理事）の-を置換
$html = preg_replace(
    '/>ご紹介者-2（理事）<\/div>\s*<div class="form-item-confirm">-<\/div>/',
    '>ご紹介者-2（理事）</div><div class="form-item-confirm">' . h($_POST['referrer2'] ?: '-') . '</div>',
    $html, 1
);

// 戻るボタンのリンクを調整
$html = str_replace('href="C3_edit-member-info.html"', 'href="edit-member-info.php?id=' . $id . '"', $html);

// 「変更を反映」ボタンをsubmitボタンに変更
$html = str_replace(
    '<a href="C5_edit-member-info-complete.html" class="button button--primary">変更を反映</a>',
    '<button type="submit" class="button button--primary">変更を反映</button>',
    $html
);

// 入会金・年会費のセクションは削除済み（テンプレートファイルから直接削除）

echo $html;