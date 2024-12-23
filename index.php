<?php

error_reporting(E_ALL);
ini_set("display_errors", "On");
ini_set("date.timezone", "Europe/Bucharest");
session_start();

define("APPLICATION_PATH",  dirname(__FILE__));
define("DS", DIRECTORY_SEPARATOR );

include_once(APPLICATION_PATH . DS. "application". DS . "app.php");

$app = new App();

echo $app->run();