<?php
/**
 * 入会金・年会費マスタ管理API
 */
require_once '../config/config.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();

// リクエストメソッドとアクションを取得
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

try {
    if ($method === 'GET' && $action === 'get') {
        // マスタ設定を取得
        $sql = "SELECT * FROM fee_master LIMIT 1";
        $stmt = $db->query($sql);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            // データがない場合はデフォルト値を返す
            $data = [
                'entry_fee' => 300000,
                'annual_fees' => [],
                'entry_fee_description' => '',
                'annual_fee_description' => ''
            ];
        } else {
            // annual_feesをデコード
            $data['annual_fees'] = json_decode($data['annual_fees'], true) ?? [];
        }
        
        echo json_encode($data);
        
    } elseif ($method === 'POST' && $action === 'update') {
        // マスタ設定を更新
        $input = json_decode(file_get_contents('php://input'), true);
        
        // データのバリデーション
        if (!isset($input['entry_fee']) || !is_numeric($input['entry_fee'])) {
            throw new Exception('入会金が正しくありません');
        }
        
        // 年会費データのソート
        $annualFees = $input['annual_fees'] ?? [];
        usort($annualFees, function($a, $b) {
            return $a['year'] - $b['year'];
        });
        
        // 更新者情報
        $updatedBy = $_SESSION['admin_username'] ?? 'admin';
        
        // 既存レコードの確認
        $sql = "SELECT COUNT(*) FROM fee_master";
        $count = $db->query($sql)->fetchColumn();
        
        if ($count > 0) {
            // 更新
            $sql = "UPDATE fee_master SET 
                    entry_fee = :entry_fee,
                    annual_fees = :annual_fees,
                    entry_fee_description = :entry_fee_description,
                    annual_fee_description = :annual_fee_description,
                    updated_at = NOW(),
                    updated_by = :updated_by
                WHERE id = 1";
        } else {
            // 新規作成
            $sql = "INSERT INTO fee_master (
                    entry_fee,
                    annual_fees,
                    entry_fee_description,
                    annual_fee_description,
                    updated_by
                ) VALUES (
                    :entry_fee,
                    :annual_fees,
                    :entry_fee_description,
                    :annual_fee_description,
                    :updated_by
                )";
        }
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':entry_fee' => $input['entry_fee'],
            ':annual_fees' => json_encode($annualFees),
            ':entry_fee_description' => $input['entry_fee_description'] ?? '',
            ':annual_fee_description' => $input['annual_fee_description'] ?? '',
            ':updated_by' => $updatedBy
        ]);
        
        echo json_encode(['success' => $result]);
        
    } elseif ($method === 'GET' && $action === 'get_fee_for_year') {
        // 特定年度の年会費を取得
        $year = $_GET['year'] ?? date('Y');
        
        $sql = "SELECT get_master_annual_fee(:year) as fee_data";
        $stmt = $db->prepare($sql);
        $stmt->execute([':year' => $year]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $feeData = json_decode($result['fee_data'], true);
        
        if ($feeData) {
            echo json_encode($feeData);
        } else {
            // その年度のデータがない場合はデフォルト値を返す
            echo json_encode([
                'year' => $year,
                'amount' => 50000,
                'description' => $year . '年度年会費'
            ]);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>