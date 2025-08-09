<?php
/**
 * registrationsテーブルにmember_numberカラムを追加
 */
require_once '../config/config.php';
$db = Database::getInstance()->getConnection();

echo "<h2>member_numberカラムの追加処理</h2>";

try {
    // 1. カラムが既に存在するか確認
    $sql = "SHOW COLUMNS FROM registrations LIKE 'member_number'";
    $stmt = $db->query($sql);
    
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: orange;'>⚠️ member_numberカラムは既に存在します。</p>";
    } else {
        // 2. カラムを追加（NULLを許可、承認時に割り当て）
        $sql = "ALTER TABLE registrations 
                ADD COLUMN member_number INT UNIQUE DEFAULT NULL AFTER id";
        $db->exec($sql);
        echo "<p style='color: green;'>✓ member_numberカラムを追加しました。</p>";
        
        // インデックスを追加
        $sql = "CREATE INDEX idx_member_number ON registrations(member_number)";
        $db->exec($sql);
        echo "<p style='color: green;'>✓ インデックスを追加しました。</p>";
    }
    
    // 3. 既存の承認済みレコードに会員番号を設定（IDと同じ値）
    $sql = "UPDATE registrations 
            SET member_number = id 
            WHERE status = 'approved' AND member_number IS NULL";
    $stmt = $db->exec($sql);
    $affected = $stmt;
    echo "<p style='color: green;'>✓ {$affected}件の既存承認済み会員に会員番号（=ID）を設定しました。</p>";
    
    // 4. 結果を確認
    echo "<h3>データベースの状態確認</h3>";
    
    // 未承認の会員（member_numberはNULL）
    $sql = "SELECT COUNT(*) FROM registrations WHERE status = 'pending' AND member_number IS NULL";
    $pending = $db->query($sql)->fetchColumn();
    echo "<p>未承認会員: {$pending}件（member_number = NULL）</p>";
    
    // 承認済みの会員（member_numberが設定済み）
    $sql = "SELECT COUNT(*) FROM registrations WHERE status = 'approved' AND member_number IS NOT NULL";
    $approved = $db->query($sql)->fetchColumn();
    echo "<p>承認済み会員: {$approved}件（member_numberが設定済み）</p>";
    
    // サンプルデータを表示
    $sql = "SELECT id, member_number, CONCAT(family_name, ' ', first_name) as name, status, approved_at
            FROM registrations 
            ORDER BY id DESC 
            LIMIT 10";
    $stmt = $db->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>最新10件のデータ</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th style='padding: 5px;'>ID</th>";
    echo "<th style='padding: 5px;'>会員番号</th>";
    echo "<th style='padding: 5px;'>氏名</th>";
    echo "<th style='padding: 5px;'>ステータス</th>";
    echo "<th style='padding: 5px;'>承認日</th>";
    echo "</tr>";
    
    foreach ($results as $row) {
        $memberNumDisplay = $row['member_number'] ? 
            "FOCJ-" . str_pad($row['member_number'], 5, '0', STR_PAD_LEFT) : 
            "未割当";
        $statusDisplay = $row['status'] === 'approved' ? '承認済み' : '未承認';
        $approvedDate = $row['approved_at'] ? date('Y/m/d', strtotime($row['approved_at'])) : '-';
        
        echo "<tr>";
        echo "<td style='padding: 5px;'>" . $row['id'] . "</td>";
        echo "<td style='padding: 5px;'>" . $memberNumDisplay . "</td>";
        echo "<td style='padding: 5px;'>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td style='padding: 5px;'>" . $statusDisplay . "</td>";
        echo "<td style='padding: 5px;'>" . $approvedDate . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green; font-weight: bold; margin-top: 20px;'>✅ 処理が完了しました。</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ エラー: " . $e->getMessage() . "</p>";
}

echo '<p style="margin-top: 30px;"><a href="dashboard.php">ダッシュボードに戻る</a></p>';
?>