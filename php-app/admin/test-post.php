<?php
session_start();

// POST受信確認
echo "<h2>POSTデータ確認</h2>";
echo "<pre>";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "POST data:\n";
print_r($_POST);
echo "\nGET data:\n";
print_r($_GET);
echo "</pre>";

// フォーム
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
?>
<h2>テストフォーム</h2>
<form method="post" action="test-post.php?type=approve">
    <input type="text" name="template_name" value="テスト名" /><br>
    <textarea name="template_content">テスト内容</textarea><br>
    <button type="submit">送信</button>
</form>
<?php
}
?>