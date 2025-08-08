<?php
session_start();
header('Content-Type: text/plain');

echo "=== DEBUG INFORMATION ===\n\n";

echo "REQUEST METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "REQUEST URI: " . $_SERVER['REQUEST_URI'] . "\n\n";

echo "POST DATA:\n";
print_r($_POST);
echo "\n";

echo "FILES DATA:\n";
print_r($_FILES);
echo "\n";

echo "SESSION DATA:\n";
print_r($_SESSION);
echo "\n";

echo "PHP Upload Settings:\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";