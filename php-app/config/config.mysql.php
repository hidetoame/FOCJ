<?php
/**
 * MySQL用設定ファイル（ローカル開発環境）
 */

// セッション設定
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// エラー表示設定
error_reporting(E_ALL);
ini_set('display_errors', '1');

// データベース接続クラス
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $host = getenv('DB_HOST') ?: 'mysql';
        $dbname = getenv('DB_NAME') ?: 'focj_db';
        $username = getenv('DB_USER') ?: 'focj_user';
        $password = getenv('DB_PASSWORD') ?: 'password';
        
        try {
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $this->connection = new PDO($dsn, $username, $password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // MySQLの厳密モードを設定
            $this->connection->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
        } catch (PDOException $e) {
            die("データベース接続エラー: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// ファイルアップロードディレクトリ
define('UPLOAD_DIR', '/var/www/html/user_images/');
define('TEMP_DIR', '/var/www/html/user_images/temp/');

// ヘルパー関数
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// エラーハンドリング
function handleError($message) {
    error_log($message);
    $_SESSION['error'] = $message;
}

// 成功メッセージ
function setSuccess($message) {
    $_SESSION['success'] = $message;
}

// エラーメッセージ
function setError($message) {
    $_SESSION['error'] = $message;
}