<?php
require_once dirname(dirname(__FILE__)) . '/config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // statusカラムの情報を取得
    $sql = "SHOW COLUMNS FROM registrations LIKE 'status'";
    $stmt = $db->query($sql);
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "現在のstatusカラムの情報:\n";
    print_r($column);
    echo "\n";
    
    // 現在のステータス値を確認
    $sql = "SELECT DISTINCT status FROM registrations";
    $stmt = $db->query($sql);
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "現在使用されているステータス値:\n";
    print_r($statuses);
    echo "\n";
    
    // statusカラムを更新（ENUMまたはVARCHARを適切に設定）
    if ($column['Type'] === "enum('pending','approved','rejected')") {
        echo "ENUMを更新します...\n";
        $sql = "ALTER TABLE registrations MODIFY COLUMN status ENUM('pending','approved','rejected','承認','却下','退会') DEFAULT 'pending'";
        $db->exec($sql);
        echo "statusカラムを更新しました。\n";
    } else if (strpos($column['Type'], 'varchar') !== false) {
        echo "VARCHARの長さを確認します...\n";
        $sql = "ALTER TABLE registrations MODIFY COLUMN status VARCHAR(20) DEFAULT 'pending'";
        $db->exec($sql);
        echo "statusカラムを更新しました。\n";
    } else {
        echo "statusカラムのタイプ: " . $column['Type'] . "\n";
        echo "VARCHARに変更します...\n";
        $sql = "ALTER TABLE registrations MODIFY COLUMN status VARCHAR(20) DEFAULT 'pending'";
        $db->exec($sql);
        echo "statusカラムをVARCHAR(20)に変更しました。\n";
    }
    
    // 更新後の情報を確認
    $sql = "SHOW COLUMNS FROM registrations LIKE 'status'";
    $stmt = $db->query($sql);
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\n更新後のstatusカラムの情報:\n";
    print_r($column);
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
?>