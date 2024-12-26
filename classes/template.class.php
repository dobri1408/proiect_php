<?php

class appTemplate {
    
    protected $file;
    protected $values = array();

    public function __construct($file) {
        $this->file = APPLICATION_PATH . DS . "views" . DS . $file;
    }
    
    public function init() {
        $baseUrl = self::getBaseUrl();
        $this->set("baseUrl", $baseUrl);
    }
    
    public static function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $scriptPath = dirname($_SERVER['PHP_SELF']);
        return rtrim($protocol . "://" . $host . $scriptPath, '/');
    }

    public function set($key, $value) {
        $this->values[$key] = $value;
    }

    public function output() {
        if (!file_exists($this->file)) {
            return "Error loading template file ($this->file).<br />";
        }

        $output = file_get_contents($this->file);

        foreach ($this->values as $key => $value) {
            $tagToReplace = "[@$key]";
            $output = str_replace($tagToReplace, $value, $output);
        }

        // Inject admin menu dynamically
        $adminMenu = $_SESSION['admin_menu'] ?? '';
        $output = str_replace("[@admin_menu]", $adminMenu, $output);

        return $output;
    }

    public static function refreshSessionState() {
        if (!empty($_SESSION['type_account']) && $_SESSION['type_account'] === 'admin') {
            $_SESSION['admin_menu'] = '<li><a href="' . self::getBaseUrl() . '/add/">Submit News</a></li>';
        } else {
            $_SESSION['admin_menu'] = '';
        }
    }

    public static function clearSessionState() {
        unset($_SESSION['admin_menu']);
    }

    public static function loadLayout($params = array()) {
        $layout = new self("layout.phtml");

        if (isset($_SESSION['msg'])) {
            $layout->set("message", $_SESSION['msg']);
            $layout->set("message_class", "extra");
            unset($_SESSION['msg']);
        }

        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $layout->set($key, $value);
            }
        }

        $layout->init();

        return preg_replace("/\[@(.*)\]/", "", $layout->output());
    }
}
