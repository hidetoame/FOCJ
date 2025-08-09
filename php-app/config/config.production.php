<?php
/**
 * 本番環境用設定ファイル
 */

// セッション設定
session_start();

// エラー表示（本番環境では非表示）
error_reporting(0);
ini_set('display_errors', '0');

// Cloud SQL接続設定
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // Cloud SQL接続情報は環境変数から取得
        $host = getenv('DB_HOST') ?: '/cloudsql/' . getenv('CLOUD_SQL_CONNECTION_NAME');
        $dbname = getenv('DB_NAME') ?: 'focj_db';
        $username = getenv('DB_USER') ?: 'focj_user';
        $password = getenv('DB_PASSWORD');
        
        try {
            // Unix socketを使った接続（Cloud SQL Proxy経由）
            if (strpos($host, '/cloudsql/') === 0) {
                $dsn = "mysql:unix_socket={$host};dbname={$dbname};charset=utf8mb4";
            } else {
                // 通常のTCP接続
                $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            }
            
            $this->connection = new PDO($dsn, $username, $password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("データベース接続エラーが発生しました。");
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

// Cloud Storage設定
define('STORAGE_BUCKET', getenv('STORAGE_BUCKET') ?: 'focj-user-images');
define('STORAGE_BASE_URL', 'https://storage.googleapis.com/' . STORAGE_BUCKET . '/');

// 画像保存パス（Cloud Storageを使用）
define('IMAGE_UPLOAD_PATH', 'gs://' . STORAGE_BUCKET . '/user_images/');
define('TEMP_IMAGE_PATH', '/tmp/'); // Cloud Runの一時ディレクトリ

// ヘルパー関数
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// エラーハンドリング
function handleError($message) {
    error_log($message);
    $_SESSION['error'] = 'エラーが発生しました。';
}

// 成功メッセージ
function setSuccess($message) {
    $_SESSION['success'] = $message;
}

// エラーメッセージ
function setError($message) {
    $_SESSION['error'] = $message;
}