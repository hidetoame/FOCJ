<?php
/**
 * データベース接続設定
 */

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $dbType = getenv('DB_TYPE') ?: 'mysql';
        $host = getenv('DB_HOST') ?: 'focj-db';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'focj_db';
        $user = getenv('DB_USER') ?: 'focj_user';
        $password = getenv('DB_PASSWORD') ?: 'focj_password';
        
        try {
            if ($dbType === 'mysql') {
                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
                $this->connection = new PDO($dsn, $user, $password, [
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]);
                // 追加で文字セットを設定
                $this->connection->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            } else {
                $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
                $this->connection = new PDO($dsn, $user, $password);
            }
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
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