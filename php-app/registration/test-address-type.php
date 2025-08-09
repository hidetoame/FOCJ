<?php
session_start();

// テストフォーム
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Address Type Test</title>
</head>
<body>
    <h1>Address Type Test</h1>
    <form method="POST">
        <p>
            <label><input type="radio" name="address-type" value="home"> 自宅</label>
            <label><input type="radio" name="address-type" value="work"> 勤務先</label>
        </p>
        <button type="submit">送信</button>
    </form>
</body>
</html>
<?php
} else {
    // POSTデータ確認
    echo "<h2>POST Data:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // テスト用INSERT文
    require_once '../config/database.php';
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // シンプルなテストデータ
        $sql = "INSERT INTO registrations (
            family_name, first_name, address_type, email
        ) VALUES (
            'テスト', '太郎', :address_type, 'test@example.com'
        )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':address_type' => $_POST['address-type'] ?? 'home'
        ]);
        
        echo "<h2>Success!</h2>";
        echo "ID: " . $db->lastInsertId();
        
    } catch (PDOException $e) {
        echo "<h2>Error:</h2>";
        echo $e->getMessage();
    }
}
?>