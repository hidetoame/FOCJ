<?php
/**
 * 年会費更新APIのテスト
 */
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin';

// エラー報告を有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';

$db = Database::getInstance()->getConnection();

// member_id=1 のmembership_feesレコードを確認
$sql = "SELECT member_id, annual_fee FROM membership_fees WHERE member_id = 1";
$stmt = $db->query($sql);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Current annual_fee for member_id=1:</h3>";
echo "<pre>";
$currentAnnualFees = json_decode($result['annual_fee'], true);
print_r($currentAnnualFees);
echo "</pre>";

// 2025年のデータを更新するテスト
$year = 2025;
$testData = [
    'year' => $year,
    'amount' => '55555',
    'status' => '支払い済み',
    'payment_date' => '2025-08-09',
    'payment_method' => '銀行振込',
    'payment_deadline' => '2025-08-31',
    'notes' => 'テスト更新2025'
];

echo "<h3>Test data for year 2025:</h3>";
echo "<pre>";
print_r($testData);
echo "</pre>";

try {
    $memberId = 1;
    
    // 現在のannual_feeを取得
    $sql = "SELECT annual_fee FROM membership_fees WHERE member_id = :member_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':member_id' => $memberId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $annualFees = json_decode($result['annual_fee'], true) ?? [];
    
    echo "<h3>Before update - Annual fees array:</h3>";
    echo "<pre>";
    print_r($annualFees);
    echo "</pre>";
    
    // 該当年度のデータを探す
    $found = false;
    foreach ($annualFees as &$fee) {
        echo "Checking year: " . $fee['year'] . " === " . $year . " ? ";
        echo ($fee['year'] == $year) ? "YES" : "NO";
        echo "\n";
        
        if ($fee['year'] == $year) {
            echo "Found year 2025! Updating...\n";
            $fee['amount'] = $testData['amount'];
            $fee['status'] = $testData['status'];
            $fee['payment_date'] = $testData['payment_date'] ?: null;
            $fee['payment_method'] = $testData['payment_method'] ?: null;
            $fee['payment_deadline'] = $testData['payment_deadline'] ?: null;
            $fee['notes'] = $testData['notes'] ?: null;
            $found = true;
            break;
        }
    }
    
    // 見つからなかった場合は新規追加
    if (!$found) {
        echo "Year 2025 not found! Adding new entry...\n";
        $annualFees[] = [
            'year' => $year,
            'amount' => $testData['amount'],
            'status' => $testData['status'],
            'payment_date' => $testData['payment_date'] ?: null,
            'payment_method' => $testData['payment_method'] ?: null,
            'payment_deadline' => $testData['payment_deadline'] ?: null,
            'notes' => $testData['notes'] ?: null
        ];
    }
    
    echo "<h3>After update - Annual fees array:</h3>";
    echo "<pre>";
    print_r($annualFees);
    echo "</pre>";
    
    // ソート（年度降順）
    usort($annualFees, function($a, $b) {
        return $b['year'] - $a['year'];
    });
    
    echo "<h3>After sort - Annual fees array:</h3>";
    echo "<pre>";
    print_r($annualFees);
    echo "</pre>";
    
    // JSON化
    $jsonData = json_encode($annualFees);
    echo "<h3>JSON data to save:</h3>";
    echo "<pre>";
    echo $jsonData;
    echo "</pre>";
    
    // データベースを更新
    $sql = "UPDATE membership_fees SET 
            annual_fee = :annual_fee,
            updated_at = CURRENT_TIMESTAMP
        WHERE member_id = :member_id";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        ':member_id' => $memberId,
        ':annual_fee' => $jsonData
    ]);
    
    echo "<h3>Update result:</h3>";
    echo $result ? "SUCCESS" : "FAILED";
    
    if (!$result) {
        echo "<h3>Error Info:</h3>";
        echo "<pre>";
        print_r($stmt->errorInfo());
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    echo "<h3>PDO Error:</h3>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "<h3>General Error:</h3>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "</pre>";
}

// 更新後のレコードを確認
echo "<h3>Updated annual_fee in database:</h3>";
$sql = "SELECT annual_fee FROM membership_fees WHERE member_id = 1";
$stmt = $db->query($sql);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$updatedAnnualFees = json_decode($result['annual_fee'], true);
echo "<pre>";
print_r($updatedAnnualFees);
echo "</pre>";
?>