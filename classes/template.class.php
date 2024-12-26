<?php

class appTemplate {
    
    /*
    The filename of the template to load from views/
    */
    protected $file;

    /*
    An array of values for replacing each tag on the template
    */
    protected $values = array();

    /*
    Creates a new Template object and sets its associated file.
    */
    public function __construct($file) {
        $this->file = APPLICATION_PATH . DS . "views" . DS . $file;
    }
    
    public function init()
    {
        $baseUrl = appTemplate::getBaseUrl();
        $this->set("baseUrl", $baseUrl);
    }
    
    public static function getBaseUrl()
    {
        // Determine if HTTPS is being used
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";

        // Get host and current script path
        $host = $_SERVER['HTTP_HOST'];
        $scriptPath = dirname($_SERVER['PHP_SELF']);

        // Build the base URL
        $baseUrl = $protocol . "://" . $host . $scriptPath;

        // Remove trailing slash if present
        return rtrim($baseUrl, '/');
    }

    /*
    Sets a value for replacing a specific tag.
    */
    public function set($key, $value) {
        $this->values[$key] = $value;
    }

    /*
    Outputs the content of the template, replacing the keys for its respective values.
    */
    public function output() {

        if (!file_exists($this->file)) {
            return "Error loading template file ($this->file).<br />";
        }
        $output = file_get_contents($this->file);

        foreach ($this->values as $key => $value) {
            $tagToReplace = "[@$key]";
            $output = str_replace($tagToReplace, $value, $output);
        }

        // Dynamically inject admin menu based on session
        $output = str_replace("[@admin_menu]", $this->getAdminMenu(), $output);

        return $output;
    }

    /*
    Merges the content from an array of templates and separates it with $separator.
    */
    static public function merge($templates, $separator = "\n") {

        $output = "";

        foreach ($templates as $template) {
            $content = (get_class($template) !== "appTemplate")
                ? "Error, incorrect type - expected appTemplate."
                : $template->output();
            $output .= $content . $separator;
        }
        
        return $output;
    }
    
    /*
    Redirect the page to $url
    */
    public static function redirect($url)
    {
        header("Location: $url");
        exit;
    }
    
    /*
    Load main layout
    */
    public static function loadLayout($params = array())
    {
        $layout = new appTemplate("layout.phtml");
        
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

    /*
    Determines if the user is an admin and returns the admin menu HTML
    */
    private function getAdminMenu()
    {
        // Check for admin rights dynamically
        if (!empty($_SESSION['type_account']) && $_SESSION['type_account'] === 'admin' && !empty($_SESSION['logged_in']) && $_SESSION['logged_in']) {
            return '<li><a href="[@baseUrl]/add/">Submit News</a></li>';
        }
        return '';
    }
}
