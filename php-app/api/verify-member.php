<?php
/**
 * 外部連携API - 正規会員確認
 * 
 * POSTリクエストで以下を受け取る:
 * - email: メールアドレス
 * - phone: 電話番号（携帯電話番号）
 * 
 * 返却値:
 * - member_exists: 会員有無（true/false）
 * - member_number: 会員番号（会員の場合のみ）
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
$required = ['email', 'phone'];
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

// メールアドレスの形式チェック
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid email format',
        'email' => $data['email']
    ]);
    exit;
}

// 電話番号の正規化（ハイフンを除去）
$phone = str_replace(['-', ' ', '　'], '', $data['phone']);

// データベース接続
require_once '../config/config.php';
$db = Database::getInstance()->getConnection();

try {
    // 会員の検索
    $sql = "SELECT id, email, family_name, first_name, status, 
                   entry_fee_status, current_year_fee_status
            FROM registrations 
            WHERE email = :email 
               AND REPLACE(REPLACE(REPLACE(mobile_number, '-', ''), ' ', ''), '　', '') = :phone
               AND status = 'approved'";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':email' => $data['email'],
        ':phone' => $phone
    ]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        // 会員が見つからない場合
        http_response_code(200);
        echo json_encode([
            'member_exists' => false,
            'message' => 'Member not found'
        ]);
        exit;
    }
    
    // 会員番号の生成（IDの下4桁）
    $memberNumber = 'FOCJ-' . str_pad($member['id'], 5, '0', STR_PAD_LEFT);
    
    // 会費の支払い状況を確認（オプション）
    $isActive = true;
    $warnings = [];
    
    if ($member['entry_fee_status'] !== 'paid') {
        $warnings[] = 'Entry fee not paid';
        $isActive = false;
    }
    
    if ($member['current_year_fee_status'] !== 'paid') {
        $warnings[] = 'Current year fee not paid';
        // 年会費未払いでも会員であることは確認できる
    }
    
    // 成功レスポンス
    http_response_code(200);
    $response = [
        'member_exists' => true,
        'member_number' => $memberNumber,
        'member_name' => $member['family_name'] . ' ' . $member['first_name'],
        'is_active' => $isActive
    ];
    
    if (!empty($warnings)) {
        $response['warnings'] = $warnings;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>