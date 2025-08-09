<?php
/**
 * manage-fees.php APIのテスト
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
$sql = "SELECT * FROM membership_fees WHERE member_id = 1";
$stmt = $db->query($sql);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Current membership_fees record for member_id=1:</h3>";
echo "<pre>";
print_r($result);
echo "</pre>";

// 入会金更新のテスト
$_GET['action'] = 'update_entry_fee';
$_GET['member_id'] = 1;
$_SERVER['REQUEST_METHOD'] = 'POST';

$testData = [
    'amount' => 10000,
    'status' => '未払い',
    'payment_date' => '',
    'payment_method' => '',
    'payment_deadline' => '',
    'notes' => 'テスト'
];

echo "<h3>Test data to send:</h3>";
echo "<pre>";
print_r($testData);
echo "</pre>";

// データを設定
$input = json_encode($testData);

echo "<h3>Testing update_entry_fee action:</h3>";

try {
    // APIコードを直接実行
    $data = $testData;
    $memberId = 1;
    $action = 'update_entry_fee';
    
    if ($action === 'update_entry_fee') {
        // 入会金の更新
        $sql = "UPDATE membership_fees SET 
                entry_fee = :amount,
                payment_status = :status,
                entry_fee_payment_date = :payment_date,
                entry_fee_payment_method = :payment_method,
                entry_fee_payment_deadline = :payment_deadline,
                entry_fee_notes = :notes,
                updated_at = CURRENT_TIMESTAMP
            WHERE member_id = :member_id";
        
        echo "<h3>SQL Query:</h3>";
        echo "<pre>$sql</pre>";
        
        $params = [
            ':member_id' => $memberId,
            ':amount' => $data['amount'] ?: 300000,
            ':status' => $data['status'],
            ':payment_date' => $data['payment_date'] ?: null,
            ':payment_method' => $data['payment_method'] ?: null,
            ':payment_deadline' => $data['payment_deadline'] ?: null,
            ':notes' => $data['notes'] ?: null
        ];
        
        echo "<h3>Parameters:</h3>";
        echo "<pre>";
        print_r($params);
        echo "</pre>";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);
        
        echo "<h3>Result:</h3>";
        echo $result ? "SUCCESS" : "FAILED";
        
        if (!$result) {
            echo "<h3>Error Info:</h3>";
            echo "<pre>";
            print_r($stmt->errorInfo());
            echo "</pre>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h3>PDO Error:</h3>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "<h3>General Error:</h3>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "</pre>";
}

// 更新後のレコードを確認
echo "<h3>Updated membership_fees record:</h3>";
$sql = "SELECT * FROM membership_fees WHERE member_id = 1";
$stmt = $db->query($sql);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($result);
echo "</pre>";
?>