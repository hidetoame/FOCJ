<?php
require_once '../config/config.php';

// セッションにテストデータを設定
$_SESSION['form_data'] = [
    'familyname' => 'テスト',
    'firstname' => '太郎',
    'familyname-kana' => 'テスト',
    'firstname-kana' => 'タロウ',
    'name-alphabet' => 'TEST TARO',
    'mail-address' => 'test@example.com',
    'postal-code' => '100-0001',
    'prefecture' => '東京都',
    'city-address' => '千代田区',
    'mobile-number' => '090-1234-5678',
    'phone-number' => '03-1234-5678',
    'birth-year' => '1990',
    'birth-month' => '1',
    'birth-day' => '1',
    'occupation' => 'テスト職業',
    'car-model' => 'テストモデル',
    'car-year' => '2020',
];
$_SESSION['csrf_token'] = md5(time());

echo "<h2>Simple Complete Test</h2>";
echo "<p>Session data has been set.</p>";
echo "<p>CSRF Token: " . $_SESSION['csrf_token'] . "</p>";

?>
<form action="complete.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <button type="submit">Complete.phpへ送信</button>
</form>

<h3>現在のセッションデータ:</h3>
<pre><?php print_r($_SESSION); ?></pre>