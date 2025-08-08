<?php
// デバッグ用
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';

// 強制的にログイン状態にする（テスト用）
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin';

echo "Session OK<br>";

$db = Database::getInstance()->getConnection();
echo "DB OK<br>";

$id = 17;

// 会員データを取得
$sql = "SELECT * FROM registrations WHERE id = :id AND status = 'approved'";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if ($member) {
    echo "Member found: " . $member['family_name'] . "<br>";
} else {
    echo "Member not found<br>";
}

// テンプレート読み込み
echo "Reading template...<br>";
$html = @file_get_contents('/var/www/html/templates/member-management/C2_member-detail.html');
if ($html) {
    echo "Template size: " . strlen($html) . " bytes<br>";
    echo "Template loaded successfully<br>";
} else {
    echo "Failed to load template<br>";
}
?>