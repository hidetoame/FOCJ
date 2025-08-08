<?php
session_start();
echo "<h2>セッション情報:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>セッション内のform_data:</h2>";
if (isset($_SESSION['form_data'])) {
    echo "<pre>";
    print_r($_SESSION['form_data']);
    echo "</pre>";
} else {
    echo "form_dataが設定されていません";
}

echo "<h2>セッションID:</h2>";
echo session_id();
?>