<?php
/**
 * テンプレート読み込み機能
 */

class Template {
    private $templatePath;
    private $data = [];
    
    public function __construct($templateFile) {
        $this->templatePath = TEMPLATE_PATH . '/' . $templateFile;
        if (!file_exists($this->templatePath)) {
            throw new Exception("テンプレートファイルが見つかりません: " . $templateFile);
        }
    }
    
    /**
     * テンプレート変数設定
     */
    public function assign($key, $value = null) {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
    }
    
    /**
     * テンプレート表示
     */
    public function display() {
        extract($this->data);
        $content = file_get_contents($this->templatePath);
        
        // 基本的な変数置換
        foreach ($this->data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $content = str_replace('{{' . $key . '}}', h($value), $content);
            }
        }
        
        // PHPコード実行のための一時ファイル
        $tempFile = sys_get_temp_dir() . '/template_' . uniqid() . '.php';
        file_put_contents($tempFile, $content);
        
        ob_start();
        include $tempFile;
        $output = ob_get_clean();
        
        unlink($tempFile);
        
        echo $output;
    }
    
    /**
     * テンプレート内容取得
     */
    public function fetch() {
        ob_start();
        $this->display();
        return ob_get_clean();
    }
}