<?php
/**
 * 会員番号管理API
 * member_number_sequenceテーブルとexcluded_member_numbersテーブルを使用
 */
// エラー表示を無効化（本番環境）
ini_set('display_errors', 0);
error_reporting(E_ALL);

// エラーハンドラーを設定
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'PHP Error',
        'message' => $errstr,
        'file' => basename($errfile),
        'line' => $errline
    ]);
    exit;
});

// 例外ハンドラーを設定
set_exception_handler(function($exception) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Exception',
        'message' => $exception->getMessage()
    ]);
    exit;
});

// 出力バッファリングを開始
ob_start();

// セッションが開始されていない場合のみ開始
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../config/config.php';

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// バッファをクリアしてJSONヘッダーを設定
ob_clean();
header('Content-Type: application/json');

try {
    
    if ($method === 'GET' && $action === 'get') {
        // 現在の設定を取得
        // member_number_sequenceテーブルから現在の番号を取得
        $sql = "SELECT next_val FROM member_number_sequence WHERE id = 1";
        $stmt = $db->query($sql);
        $sequence = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // excluded_member_numbersテーブルから除外番号を取得
        $sql = "SELECT excluded_number FROM excluded_member_numbers ORDER BY excluded_number";
        $stmt = $db->query($sql);
        $excludedNumbers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $data = [
            'start_number' => $sequence ? $sequence['next_val'] : 999,
            'exclude_numbers' => $excludedNumbers ?: []
        ];
        
        echo json_encode($data);
        exit;
    }
    
    elseif ($method === 'GET' && $action === 'get_next_number') {
        // 次の利用可能な会員番号を取得して発行
        
        // 現在の番号を取得
        $sql = "SELECT next_val FROM member_number_sequence WHERE id = 1";
        $stmt = $db->query($sql);
        $sequence = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentNumber = $sequence ? $sequence['next_val'] : 999;
        
        // 除外番号を取得
        $sql = "SELECT excluded_number FROM excluded_member_numbers";
        $stmt = $db->query($sql);
        $excludeNumbers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $excludeNumbers = array_map('intval', $excludeNumbers);
        
        // 現在の番号から開始して、除外番号に含まれない番号を探す
        $nextNumber = $currentNumber;
        $maxAttempts = 10000; // 無限ループ防止
        $attempts = 0;
        
        while (in_array($nextNumber, $excludeNumbers) && $attempts < $maxAttempts) {
            $nextNumber++;
            $attempts++;
        }
        
        if ($attempts >= $maxAttempts) {
            throw new Exception('利用可能な会員番号が見つかりません');
        }
        
        // 発行した番号の次を保存
        $sql = "UPDATE member_number_sequence SET next_val = :next_val WHERE id = 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([':next_val' => $nextNumber + 1]);
        
        echo json_encode([
            'next_number' => $nextNumber,
            'formatted' => 'FOCJ-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT)
        ]);
        exit;
    }
    
    else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }
    
} catch (Exception $e) {
    error_log("Error in member-number.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}

// 出力バッファを終了して送信
ob_end_flush();
?>