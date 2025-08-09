<?php
require_once '../config/config.php';

$db = Database::getInstance()->getConnection();

// テストデータ
$type = 'approve';
$typeMap = ['approve' => '承認通知', 'reject' => '却下通知'];
$dbType = $typeMap[$type] ?? '承認通知';

echo "Testing mail template insert...\n";
echo "Type: $type\n";
echo "DB Type: $dbType\n\n";

// データベースのCHECK制約を確認
$sql = "SHOW CREATE TABLE mail_templates";
$stmt = $db->query($sql);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Table structure:\n";
echo $result['Create Table'] . "\n\n";

// テスト挿入
try {
    $sql = "INSERT INTO mail_templates (template_name, template_type, subject, body, is_active) VALUES (:name, :type, :subject, :body, :is_active)";
    $stmt = $db->prepare($sql);
    
    $testData = [
        ':name' => 'テストテンプレート',
        ':type' => $dbType,
        ':subject' => 'テスト件名',
        ':body' => 'テスト本文',
        ':is_active' => 0
    ];
    
    echo "Inserting data:\n";
    print_r($testData);
    
    $stmt->execute($testData);
    
    echo "\n✅ 挿入成功！\n";
    
    // 削除
    $lastId = $db->lastInsertId();
    $db->exec("DELETE FROM mail_templates WHERE template_id = $lastId");
    echo "テストデータを削除しました。\n";
    
} catch (PDOException $e) {
    echo "\n❌ エラー: " . $e->getMessage() . "\n";
}
?>