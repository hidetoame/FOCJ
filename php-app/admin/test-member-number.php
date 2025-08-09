<?php
/**
 * 会員番号API動作テスト
 */
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin';

require_once '../config/config.php';

$db = Database::getInstance()->getConnection();

echo "<h2>会員番号発行テスト</h2>";

// 現在の設定を確認
$sql = "SELECT next_val FROM member_number_sequence WHERE id = 1";
$stmt = $db->query($sql);
$sequence = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>現在のnext_val: " . $sequence['next_val'] . "</p>";

// 除外番号を確認
$sql = "SELECT excluded_number FROM excluded_member_numbers ORDER BY excluded_number";
$stmt = $db->query($sql);
$excludedNumbers = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<p>除外番号: " . implode(', ', $excludedNumbers) . "</p>";

// APIを内部的に実行
try {
    // 現在の番号を取得
    $sql = "SELECT next_val FROM member_number_sequence WHERE id = 1";
    $stmt = $db->query($sql);
    $sequence = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentNumber = $sequence ? $sequence['next_val'] : 999;
    
    // 除外番号を取得
    $sql = "SELECT excluded_number FROM excluded_member_numbers";
    $stmt = $db->query($sql);
    $excludeNumbers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $excludeNumbers = array_map('intval', $excludeNumbers);
    
    // 現在の番号から開始して、除外番号に含まれない番号を探す
    $nextNumber = $currentNumber;
    while (in_array($nextNumber, $excludeNumbers)) {
        $nextNumber++;
    }
    
    echo "<h3>発行する番号: " . $nextNumber . "</h3>";
    echo "<p>フォーマット済み: FOCJ-" . str_pad($nextNumber, 5, '0', STR_PAD_LEFT) . "</p>";
    
    // 発行した番号の次を保存
    $sql = "UPDATE member_number_sequence SET next_val = :next_val WHERE id = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':next_val' => $nextNumber + 1]);
    
    $response = json_encode([
        'next_number' => $nextNumber,
        'formatted' => 'FOCJ-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT)
    ]);
} catch (Exception $e) {
    $response = json_encode(['error' => $e->getMessage()]);
}

echo "<h3>API応答:</h3>";
echo "<pre>" . $response . "</pre>";

// 更新後の値を確認
$sql = "SELECT next_val FROM member_number_sequence WHERE id = 1";
$stmt = $db->query($sql);
$sequence = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>更新後のnext_val: " . $sequence['next_val'] . "</p>";
?>