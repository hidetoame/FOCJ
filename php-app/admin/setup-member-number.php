<?php
/**
 * 会員番号設定テーブルのセットアップ
 */
require_once '../config/config.php';
$db = Database::getInstance()->getConnection();

try {
    // テーブル作成
    $sql = 'CREATE TABLE IF NOT EXISTS member_number_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        start_number INT DEFAULT 1,
        exclude_numbers JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by VARCHAR(50)
    )';
    $db->exec($sql);
    echo "テーブル作成完了<br>\n";

    // 初期データの存在確認
    $sql = 'SELECT COUNT(*) FROM member_number_settings';
    $count = $db->query($sql)->fetchColumn();
    
    if ($count == 0) {
        // 初期データ挿入
        $sql = 'INSERT INTO member_number_settings (start_number, exclude_numbers, updated_by) 
                VALUES (:start_number, :exclude_numbers, :updated_by)';
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':start_number' => 1,
            ':exclude_numbers' => json_encode([]),
            ':updated_by' => 'admin'
        ]);
        echo "初期データ挿入完了<br>\n";
    } else {
        echo "データは既に存在します<br>\n";
    }

    // 確認
    $sql = 'SELECT * FROM member_number_settings';
    $stmt = $db->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "データ確認:<br>\n";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    echo "<br>セットアップ完了しました。<br>";
    echo '<a href="dashboard.php">ダッシュボードに戻る</a>';
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage();
}
?>