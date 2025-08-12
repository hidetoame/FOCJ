<?php
require_once dirname(dirname(__FILE__)) . '/config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // カラムが存在するか確認
    $sql = "SHOW COLUMNS FROM registrations LIKE 'rejection_reason'";
    $stmt = $db->query($sql);
    $column = $stmt->fetch();
    
    if (!$column) {
        // カラムが存在しない場合は追加
        $sql = "ALTER TABLE registrations ADD COLUMN rejection_reason TEXT DEFAULT NULL AFTER status";
        $db->exec($sql);
        echo "rejection_reason カラムを追加しました。\n";
    } else {
        echo "rejection_reason カラムは既に存在します。\n";
    }
    
    // ステータスの値を確認
    $sql = "SELECT DISTINCT status FROM registrations";
    $stmt = $db->query($sql);
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "\n現在のステータス値: " . implode(', ', $statuses) . "\n";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
?>