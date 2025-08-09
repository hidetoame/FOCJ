<?php
/**
 * 会員番号管理APIの直接テスト
 */
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin';

require_once '../config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "<h3>データベース接続成功</h3>";
    
    // テーブルの存在確認
    $sql = 'CREATE TABLE IF NOT EXISTS member_number_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        start_number INT DEFAULT 1,
        exclude_numbers JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by VARCHAR(50)
    )';
    $db->exec($sql);
    echo "<p>テーブル作成/確認完了</p>";
    
    // 既存データの確認
    $sql = "SELECT * FROM member_number_settings";
    $stmt = $db->query($sql);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>既存データ:</h3>";
    echo "<pre>";
    print_r($existing);
    echo "</pre>";
    
    // テストデータの挿入/更新
    $testData = [
        'start_number' => 1000,
        'exclude_numbers' => json_encode([10, 20, 30])
    ];
    
    if ($existing) {
        echo "<h3>既存データを更新</h3>";
        $sql = "UPDATE member_number_settings SET 
                start_number = :start_number,
                exclude_numbers = :exclude_numbers,
                updated_at = CURRENT_TIMESTAMP,
                updated_by = :updated_by
                WHERE id = :id";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':start_number' => $testData['start_number'],
            ':exclude_numbers' => $testData['exclude_numbers'],
            ':updated_by' => 'test',
            ':id' => $existing['id']
        ]);
    } else {
        echo "<h3>新規データを挿入</h3>";
        $sql = "INSERT INTO member_number_settings (
                start_number,
                exclude_numbers,
                updated_by
            ) VALUES (
                :start_number,
                :exclude_numbers,
                :updated_by
            )";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':start_number' => $testData['start_number'],
            ':exclude_numbers' => $testData['exclude_numbers'],
            ':updated_by' => 'test'
        ]);
    }
    
    if ($result) {
        echo "<p style='color: green;'>✓ データ操作成功</p>";
    } else {
        echo "<p style='color: red;'>✗ データ操作失敗</p>";
        echo "<pre>";
        print_r($stmt->errorInfo());
        echo "</pre>";
    }
    
    // 更新後のデータを確認
    $sql = "SELECT * FROM member_number_settings";
    $stmt = $db->query($sql);
    $updated = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>更新後のデータ:</h3>";
    echo "<pre>";
    print_r($updated);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>エラー発生:</h3>";
    echo "<pre>";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}

echo '<p><a href="dashboard.php">ダッシュボードに戻る</a></p>';
?>