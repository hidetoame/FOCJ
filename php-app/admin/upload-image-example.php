<?php
/**
 * 画像アップロードのサンプル実装
 * registration-confirm.php などで使用する例
 */
require_once '../config/config.php';
require_once '../includes/image-upload.php';

// ログインチェック
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

// POSTリクエストの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? 0;
    
    if (!$userId) {
        $error = 'ユーザーIDが指定されていません';
    } else {
        // 運転免許証のアップロード
        if (isset($_FILES['license']) && $_FILES['license']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = ImageUpload::upload($_FILES['license'], $userId, 'license');
            if ($result['success']) {
                $message .= "運転免許証をアップロードしました: " . $result['path'] . "\n";
                
                // データベースに保存パスを記録
                $db = Database::getInstance()->getConnection();
                $sql = "UPDATE registrations SET license_image = :path WHERE id = :id";
                $stmt = $db->prepare($sql);
                $stmt->execute([':path' => $result['path'], ':id' => $userId]);
            } else {
                $error .= "運転免許証のアップロードに失敗: " . $result['error'] . "\n";
            }
        }
        
        // 車検証のアップロード
        if (isset($_FILES['vehicle_inspection']) && $_FILES['vehicle_inspection']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = ImageUpload::upload($_FILES['vehicle_inspection'], $userId, 'vehicle_inspection');
            if ($result['success']) {
                $message .= "車検証をアップロードしました: " . $result['path'] . "\n";
                
                // データベースに保存パスを記録
                $db = Database::getInstance()->getConnection();
                $sql = "UPDATE registrations SET vehicle_inspection_image = :path WHERE id = :id";
                $stmt = $db->prepare($sql);
                $stmt->execute([':path' => $result['path'], ':id' => $userId]);
            } else {
                $error .= "車検証のアップロードに失敗: " . $result['error'] . "\n";
            }
        }
        
        // 名刺のアップロード
        if (isset($_FILES['business_card']) && $_FILES['business_card']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = ImageUpload::upload($_FILES['business_card'], $userId, 'business_card');
            if ($result['success']) {
                $message .= "名刺をアップロードしました: " . $result['path'] . "\n";
                
                // データベースに保存パスを記録
                $db = Database::getInstance()->getConnection();
                $sql = "UPDATE registrations SET business_card_image = :path WHERE id = :id";
                $stmt = $db->prepare($sql);
                $stmt->execute([':path' => $result['path'], ':id' => $userId]);
            } else {
                $error .= "名刺のアップロードに失敗: " . $result['error'] . "\n";
            }
        }
    }
}

// テスト用のユーザーIDを取得
$db = Database::getInstance()->getConnection();
$sql = "SELECT id, family_name, first_name FROM registrations WHERE status = 'approved' LIMIT 1";
$stmt = $db->query($sql);
$testUser = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>画像アップロードテスト</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .form-group {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="file"] {
            display: block;
            margin: 10px 0;
        }
        button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 3px;
            white-space: pre-wrap;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .preview {
            margin: 20px 0;
        }
        .preview img {
            max-width: 300px;
            max-height: 200px;
            margin: 10px;
            border: 1px solid #ddd;
            padding: 5px;
        }
    </style>
</head>
<body>
    <h1>画像アップロードテスト</h1>
    
    <?php if ($message): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($testUser): ?>
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <h3>テストユーザー: <?= htmlspecialchars($testUser['family_name'] . ' ' . $testUser['first_name']) ?> (ID: <?= $testUser['id'] ?>)</h3>
            <input type="hidden" name="user_id" value="<?= $testUser['id'] ?>">
        </div>
        
        <div class="form-group">
            <label for="license">運転免許証</label>
            <input type="file" name="license" id="license" accept="image/*">
            <small>JPEG, PNG, GIF, WebP形式（最大5MB）</small>
        </div>
        
        <div class="form-group">
            <label for="vehicle_inspection">車検証</label>
            <input type="file" name="vehicle_inspection" id="vehicle_inspection" accept="image/*">
            <small>JPEG, PNG, GIF, WebP形式（最大5MB）</small>
        </div>
        
        <div class="form-group">
            <label for="business_card">名刺</label>
            <input type="file" name="business_card" id="business_card" accept="image/*">
            <small>JPEG, PNG, GIF, WebP形式（最大5MB）</small>
        </div>
        
        <button type="submit">アップロード</button>
    </form>
    
    <div class="preview">
        <h3>現在の画像</h3>
        <?php
        // 既存の画像を表示
        $licenseImage = ImageUpload::getImage($testUser['id'], 'license');
        $vehicleImage = ImageUpload::getImage($testUser['id'], 'vehicle_inspection');
        $cardImage = ImageUpload::getImage($testUser['id'], 'business_card');
        ?>
        
        <?php if ($licenseImage): ?>
            <div>
                <h4>運転免許証</h4>
                <img src="view-image.php?user_id=<?= $testUser['id'] ?>&type=license" alt="運転免許証">
            </div>
        <?php endif; ?>
        
        <?php if ($vehicleImage): ?>
            <div>
                <h4>車検証</h4>
                <img src="view-image.php?user_id=<?= $testUser['id'] ?>&type=vehicle_inspection" alt="車検証">
            </div>
        <?php endif; ?>
        
        <?php if ($cardImage): ?>
            <div>
                <h4>名刺</h4>
                <img src="view-image.php?user_id=<?= $testUser['id'] ?>&type=business_card" alt="名刺">
            </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
        <p>承認済みユーザーが存在しません。</p>
    <?php endif; ?>
    
    <div style="margin-top: 40px;">
        <h3>使用方法</h3>
        <pre style="background: #f5f5f5; padding: 15px; border-radius: 3px;">
// 画像のアップロード
require_once '../includes/image-upload.php';

$result = ImageUpload::upload($_FILES['license'], $userId, 'license');
if ($result['success']) {
    echo "アップロード成功: " . $result['path'];
} else {
    echo "エラー: " . $result['error'];
}

// 画像の取得
$imagePath = ImageUpload::getImage($userId, 'license');
if ($imagePath) {
    echo '&lt;img src="view-image.php?user_id=' . $userId . '&type=license"&gt;';
}

// 画像の削除
ImageUpload::deleteImage($userId, 'license');
        </pre>
        
        <h3>ディレクトリ構造</h3>
        <pre style="background: #f5f5f5; padding: 15px; border-radius: 3px;">
/user_images/
  ├── 17/                      # ユーザーID: 17
  │   ├── license_1704858000.jpg
  │   ├── vehicle_inspection_1704858100.png
  │   └── business_card_1704858200.jpg
  ├── 18/                      # ユーザーID: 18
  │   └── license_1704859000.jpg
  └── .htaccess               # 直接アクセス禁止
        </pre>
    </div>
</body>
</html>