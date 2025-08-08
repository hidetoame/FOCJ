<?php
/**
 * 外部連携API - 年会費決済
 * 
 * POSTリクエストで以下を受け取る:
 * - user_id: ユーザーID
 * - email: メールアドレス
 * - amount: 決済金額
 * - payment_method: 決済方法
 * - year: 対象年度（オプション、未指定の場合は現在年）
 * 
 * 返却値:
 * - status: 決済ステータス（success/failure）
 * - user_id: ユーザーID
 * - email: メールアドレス
 * - amount: 金額
 * - year: 対象年度
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONSリクエストの処理（CORS対応）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POSTリクエストのみ受け付ける
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Method not allowed',
        'message' => 'Only POST method is accepted'
    ]);
    exit;
}

// JSONデータを取得
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// 必須パラメータのチェック
$required = ['user_id', 'email', 'amount', 'payment_method'];
$missing = [];

foreach ($required as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        $missing[] = $field;
    }
}

if (!empty($missing)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing required parameters',
        'missing_fields' => $missing
    ]);
    exit;
}

// 年度の設定（未指定の場合は現在年）
$year = isset($data['year']) ? intval($data['year']) : date('Y');

// メールアドレスの形式チェック
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid email format',
        'email' => $data['email']
    ]);
    exit;
}

// 金額の妥当性チェック
if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid amount',
        'amount' => $data['amount']
    ]);
    exit;
}

// データベース接続
require_once '../config/config.php';
$db = Database::getInstance()->getConnection();

try {
    // トランザクション開始
    $db->beginTransaction();
    
    // ユーザーの存在確認
    $sql = "SELECT id, email, family_name, first_name, status, annual_fees 
            FROM registrations 
            WHERE id = :user_id AND email = :email";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':user_id' => $data['user_id'],
        ':email' => $data['email']
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode([
            'status' => 'failure',
            'error' => 'User not found',
            'user_id' => $data['user_id'],
            'email' => $data['email']
        ]);
        exit;
    }
    
    // 承認済みユーザーかチェック
    if ($user['status'] !== 'approved') {
        $db->rollBack();
        http_response_code(400);
        echo json_encode([
            'status' => 'failure',
            'error' => 'User not approved',
            'user_id' => $data['user_id'],
            'email' => $data['email']
        ]);
        exit;
    }
    
    // 年会費データの準備
    $annualFees = $user['annual_fees'] ? json_decode($user['annual_fees'], true) : [];
    
    // 指定年度の年会費情報を更新
    $annualFees[$year] = [
        'status' => 'paid',
        'payment_date' => date('Y-m-d'),
        'amount' => $data['amount'],
        'payment_method' => $data['payment_method']
    ];
    
    // 年会費データを更新
    $sql = "UPDATE registrations 
            SET annual_fees = :annual_fees,
                current_year_fee_status = CASE WHEN :year = EXTRACT(YEAR FROM CURRENT_DATE) THEN 'paid' ELSE current_year_fee_status END,
                current_year_fee_payment_date = CASE WHEN :year2 = EXTRACT(YEAR FROM CURRENT_DATE) THEN CURRENT_TIMESTAMP ELSE current_year_fee_payment_date END,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :user_id";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        ':annual_fees' => json_encode($annualFees),
        ':year' => $year,
        ':year2' => $year,
        ':user_id' => $data['user_id']
    ]);
    
    if (!$result) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode([
            'status' => 'failure',
            'error' => 'Failed to update payment status',
            'user_id' => $data['user_id'],
            'email' => $data['email']
        ]);
        exit;
    }
    
    // 決済履歴を記録
    $sql = "INSERT INTO payment_history (
                user_id,
                payment_type,
                amount,
                payment_method,
                payment_date,
                status,
                year,
                created_at
            ) VALUES (
                :user_id,
                'annual_fee',
                :amount,
                :payment_method,
                CURRENT_TIMESTAMP,
                'completed',
                :year,
                CURRENT_TIMESTAMP
            )";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':user_id' => $data['user_id'],
        ':amount' => $data['amount'],
        ':payment_method' => $data['payment_method'],
        ':year' => $year
    ]);
    
    // コミット
    $db->commit();
    
    // 成功レスポンス
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'user_id' => $data['user_id'],
        'email' => $data['email'],
        'amount' => $data['amount'],
        'year' => $year,
        'payment_date' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode([
        'status' => 'failure',
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>