<?php

include_once(APPLICATION_PATH . DS. "controllers". DS . "news.php");
include_once(APPLICATION_PATH . DS. "classes". DS . "template.class.php");
include_once(APPLICATION_PATH . DS. "classes". DS . "db.class.php");

class App{
    
    protected $_CONFIG;

    /* 
    Main Construct Method
    */
    public function __construct()
    {
        $this->_CONFIG = parse_ini_file(APPLICATION_PATH . DS. "conf". DS . "application.ini.php", TRUE);
    }
    
    
    /*
    Initiate the controller action
    */
    public function initController($action, $args=array())
    {
        
         if(!$action)
            $action = "noRoute";

        return call_user_func(array("appController", $action), $args);
    }
    
    
    /*
    Basic route handling
    */
    public function handleRoute()
    {
        if(!isset($_SERVER['REQUEST_URI']))
            return false;
        $path = $_SERVER['REQUEST_URI'];
        $baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $route = str_replace($baseDir, "", $path);
        $parts = explode("/", $route );
        $action = "";
        $request = "";
        
        if(isset($parts[1]))
            $request = $parts[1];
        if(isset($parts[2]))
            $id = $parts[2];
        
        if(isset($this->_CONFIG["route"]["/".$request])){
            $action = $this->_CONFIG["route"]["/".$request];
        }
       
        return $this->initController($action, array_slice($parts, 2));
        
    }
    
    /*
    Doing basic input data filtering
    */
    public static function test_input($data) {
      $data = trim($data);
      $data = addslashes($data);
      $data = htmlspecialchars($data);
      return $data;
    }
    
    /* 
    Initiate the application
    */
    public function run()
    {
        return $this->handleRoute();
        
    }
}
