<?php
/**
 * member_numberカラムのセットアップ
 */
require_once '../config/config.php';
$db = Database::getInstance()->getConnection();

echo "<h2>member_numberカラムのセットアップ</h2>";

try {
    // カラムが既に存在するか確認
    $sql = "SHOW COLUMNS FROM registrations LIKE 'member_number'";
    $stmt = $db->query($sql);
    
    if ($stmt->rowCount() > 0) {
        echo "<p>member_numberカラムは既に存在します。</p>";
    } else {
        // カラムを追加
        $sql = "ALTER TABLE registrations 
                ADD COLUMN member_number INT UNIQUE DEFAULT NULL AFTER id";
        $db->exec($sql);
        echo "<p>✓ member_numberカラムを追加しました。</p>";
        
        // インデックスを追加
        $sql = "CREATE INDEX idx_member_number ON registrations(member_number)";
        $db->exec($sql);
        echo "<p>✓ インデックスを追加しました。</p>";
    }
    
    // 既存の承認済みレコードに会員番号を設定
    $sql = "UPDATE registrations 
            SET member_number = id 
            WHERE status = 'approved' AND member_number IS NULL";
    $stmt = $db->exec($sql);
    $affected = $stmt;
    echo "<p>✓ {$affected}件の既存レコードに会員番号を設定しました。</p>";
    
    // 結果を確認
    $sql = "SELECT id, member_number, CONCAT(family_name, ' ', first_name) as name, status 
            FROM registrations 
            WHERE status = 'approved' 
            ORDER BY id DESC 
            LIMIT 10";
    $stmt = $db->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>承認済み会員の確認（最新10件）</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>会員番号</th><th>氏名</th><th>ステータス</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . ($row['member_number'] ?? '未設定') . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green;'>セットアップが完了しました。</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . $e->getMessage() . "</p>";
}

echo '<p><a href="dashboard.php">ダッシュボードに戻る</a></p>';
?>