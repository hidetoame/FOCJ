<?php
require_once '../config/database.php';

// テスト用のシンプルなフォーム
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
?>
<!DOCTYPE html>
<html>
<head>
    <title>シンプル登録テスト</title>
    <meta charset="UTF-8">
</head>
<body>
    <h1>シンプル登録テスト</h1>
    <form method="POST">
        <p>姓: <input type="text" name="family_name" value="テスト" required></p>
        <p>名: <input type="text" name="first_name" value="太郎" required></p>
        <p>住所タイプ: 
            <label><input type="radio" name="address_type" value="home" checked> 自宅</label>
            <label><input type="radio" name="address_type" value="work"> 勤務先</label>
        </p>
        <p>メール: <input type="email" name="email" value="test@example.com" required></p>
        <button type="submit">登録</button>
    </form>
</body>
</html>
<?php
} else {
    // POSTデータを処理
    echo "<h2>POSTデータ:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // 最小限のデータで登録
        $sql = "INSERT INTO registrations (
            family_name, first_name, address_type, email
        ) VALUES (
            :family_name, :first_name, :address_type, :email
        )";
        
        $stmt = $db->prepare($sql);
        $params = [
            ':family_name' => $_POST['family_name'],
            ':first_name' => $_POST['first_name'],
            ':address_type' => $_POST['address_type'],
            ':email' => $_POST['email']
        ];
        
        echo "<h2>SQLパラメータ:</h2>";
        echo "<pre>";
        print_r($params);
        echo "</pre>";
        
        $stmt->execute($params);
        
        echo "<h2>✅ 登録成功!</h2>";
        echo "ID: " . $db->lastInsertId();
        
    } catch (PDOException $e) {
        echo "<h2>❌ エラー:</h2>";
        echo "<pre>" . $e->getMessage() . "</pre>";
        echo "<h2>トレース:</h2>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}
?>