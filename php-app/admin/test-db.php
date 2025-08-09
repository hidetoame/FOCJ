<?php
require_once '../config/config.php';

$db = Database::getInstance()->getConnection();

// テーブル構造を確認
echo "<h3>mail_templates テーブル構造:</h3>";
$sql = "SHOW COLUMNS FROM mail_templates WHERE Field = 'template_type'";
$stmt = $db->query($sql);
$column = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
var_dump($column);
echo "</pre>";

// テストデータ挿入
echo "<h3>テストデータ挿入:</h3>";
$testData = [
    ['承認通知', 'テスト1'],
    ['却下通知', 'テスト2'],
    ['拒否通知', 'テスト3']
];

foreach ($testData as $data) {
    try {
        $sql = "INSERT INTO mail_templates (template_name, template_type, subject, body, is_active) 
                VALUES (:name, :type, :subject, :body, :is_active)";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':name' => 'テスト_' . $data[1],
            ':type' => $data[0],
            ':subject' => 'テスト件名',
            ':body' => 'テスト内容',
            ':is_active' => 0
        ]);
        echo "'{$data[0]}' -> 成功<br>";
    } catch (PDOException $e) {
        echo "'{$data[0]}' -> エラー: " . $e->getMessage() . "<br>";
    }
}

// 現在のデータを確認
echo "<h3>現在のテンプレート一覧:</h3>";
$sql = "SELECT id, template_name, template_type FROM mail_templates ORDER BY id DESC LIMIT 10";
$stmt = $db->query($sql);
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($templates);
echo "</pre>";