<?php
/**
 * 管理画面 - ログイン
 */
session_start();

// ログイン済みの場合はダッシュボードへ
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// ログイン処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 簡易的な認証（本番環境ではデータベースで管理）
    if ($username === 'admin' && $password === 'focj2024') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'ユーザー名またはパスワードが正しくありません。';
    }
}

// テンプレート読み込み
$template = $error ? '0_login-error.html' : '0_login.html';
$html = file_get_contents('/var/www/html/templates/member-management/' . $template);

// アセットパスを調整
$html = str_replace('href="assets/', 'href="/templates/member-management/assets/', $html);
$html = str_replace('src="assets/', 'src="/templates/member-management/assets/', $html);

// フォームのアクションを調整
$html = str_replace('action="A1_admin-index.html"', 'action="index.php"', $html);

// input要素にname属性を追加
$html = str_replace('<input type="text" class="input-text">', '<input type="text" name="username" class="input-text" required>', $html);
$html = str_replace('<input type="password" class="input-text">', '<input type="password" name="password" class="input-text" required>', $html);

// エラーメッセージがある場合
if ($error) {
    // エラーテンプレートの場合、エラーメッセージを置換
    $html = str_replace('入力に誤りがあります。もう一度入力をしてください。', $error, $html);
}

echo $html;