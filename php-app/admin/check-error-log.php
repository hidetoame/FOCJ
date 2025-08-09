<?php
/**
 * エラーログ確認
 */
session_start();

// ログインチェック（オプション）
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // テスト用にログインチェックをスキップすることも可能
    // header('Location: index.php');
    // exit;
}

echo "<h2>PHP エラーログ</h2>";
echo "<pre>";

// エラーログのパスを確認
$errorLog = ini_get('error_log');
echo "エラーログのパス: " . $errorLog . "\n\n";

// エラーログの最後の50行を表示
if (file_exists($errorLog)) {
    $lines = file($errorLog);
    $lastLines = array_slice($lines, -50);
    foreach ($lastLines as $line) {
        if (strpos($line, 'member-number.php') !== false) {
            echo "<b style='color: red;'>" . htmlspecialchars($line) . "</b>";
        } else {
            echo htmlspecialchars($line);
        }
    }
} else {
    echo "エラーログファイルが見つかりません。";
}

echo "</pre>";

// データベースのmember_number_settingsテーブルの内容を確認
require_once '../config/config.php';
$db = Database::getInstance()->getConnection();

echo "<h2>member_number_settings テーブルの内容</h2>";
echo "<pre>";

try {
    // テーブルが存在するか確認
    $sql = "SHOW TABLES LIKE 'member_number_settings'";
    $stmt = $db->query($sql);
    if ($stmt->rowCount() > 0) {
        echo "テーブルが存在します。\n\n";
        
        // テーブル構造を確認
        $sql = "DESCRIBE member_number_settings";
        $stmt = $db->query($sql);
        echo "テーブル構造:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("  %-20s %-20s %s\n", $row['Field'], $row['Type'], $row['Null']);
        }
        echo "\n";
        
        // データを確認
        $sql = "SELECT * FROM member_number_settings";
        $stmt = $db->query($sql);
        echo "データ:\n";
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        print_r($data);
    } else {
        echo "テーブルが存在しません。\n";
    }
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}

echo "</pre>";

echo '<p><a href="dashboard.php">ダッシュボードに戻る</a></p>';
?>