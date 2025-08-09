<?php
/**
 * シンプルなテストAPI
 */
// エラー表示を完全に無効化
error_reporting(0);
ini_set('display_errors', 0);

// 出力バッファリング開始
ob_start();

// JSONヘッダーを最初に設定
header('Content-Type: application/json');

// セッションはconfig.phpで開始されるので、ここでは開始しない
// ログインチェックをスキップ（テスト用）

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// 必ずJSONを返すためのラッパー
function outputJSON($data, $statusCode = 200) {
    ob_clean(); // バッファをクリア
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

try {
    // config.phpの前に@をつけてエラーを抑制
    @require_once '../../config/config.php';
    
    // セッション変数を設定（テスト用）
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'admin';
    
    $db = Database::getInstance()->getConnection();
    
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
    
    if ($method === 'GET' && $action === 'get') {
        $sql = "SELECT * FROM member_number_settings LIMIT 1";
        $stmt = $db->query($sql);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            $data = [
                'start_number' => 1,
                'exclude_numbers' => []
            ];
        } else {
            if (isset($data['exclude_numbers'])) {
                $data['exclude_numbers'] = json_decode($data['exclude_numbers'], true) ?: [];
            }
        }
        
        outputJSON($data);
    }
    elseif ($method === 'POST' && $action === 'update') {
        $inputData = file_get_contents('php://input');
        $data = json_decode($inputData, true);
        
        $startNumber = $data['start_number'] ?? 1;
        $excludeNumbers = $data['exclude_numbers'] ?? [];
        
        // 既存レコードの確認
        $sql = "SELECT id FROM member_number_settings LIMIT 1";
        $stmt = $db->query($sql);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $sql = "UPDATE member_number_settings SET 
                    start_number = :start_number,
                    exclude_numbers = :exclude_numbers,
                    updated_by = 'admin'
                    WHERE id = :id";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':start_number' => $startNumber,
                ':exclude_numbers' => json_encode($excludeNumbers),
                ':id' => $existing['id']
            ]);
        } else {
            $sql = "INSERT INTO member_number_settings (start_number, exclude_numbers, updated_by) 
                    VALUES (:start_number, :exclude_numbers, 'admin')";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':start_number' => $startNumber,
                ':exclude_numbers' => json_encode($excludeNumbers)
            ]);
        }
        
        outputJSON(['success' => true, 'message' => '保存しました']);
    }
    else {
        outputJSON(['error' => 'Invalid request'], 400);
    }
} catch (Exception $e) {
    outputJSON(['error' => $e->getMessage()], 500);
}
?>