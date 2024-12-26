<?php

class appController {

    public static function indexAction($args = array()) {
        self::checkAuthentication();

        $db = new DB();
        $query = isset($args[0]) ? App::test_input(strip_tags($args[0])) : "";
        $news = $db->getNewsByQuery($query);
        $list_output = "";

        if (is_array($news)) {
            foreach ($news as $post) {
                if (!$post["permalink"]) {
                    $post["permalink"] = $post["id"];
                }
                $view = new appTemplate("news/news-list.phtml");
                $view->set("title", $post["title"]); 
                $view->set("author", $post["author"]);
                $view->set("news_id", $post["id"]);
                $view->set("permalink", $post["permalink"]);
                $view->set("date", date("M d, Y H:i", strtotime($post["created"])));

                // Dynamically set the admin console links based on the session
                $adminConsole = self::getAdminConsole($post["id"]);
                $view->set("adminconsole", $adminConsole);

                $list_output .= $view->output();
            }
        }

        $view = new appTemplate("news/index.phtml");
        $view->set("title", "Latest news");
        $view->set("content", $list_output);

        return appTemplate::loadLayout(array("content" => $view->output(), "title" => "Homepage", "query" => stripslashes($query)));
    }
    public static function viewNewsAction($args = array())
    {
        self::checkAuthentication();
        $db = new DB();
        $news_id = $args[0];
        
        if (!is_numeric($news_id)) {
            $post = $db->getNewsByPermalink($news_id);
        } else {
            $post = $db->loadNews($news_id);
        }
        
        if (!$post) {
            appTemplate::redirect(appTemplate::getBaseUrl());
        }
        
        $view = new appTemplate("news/view.phtml");
        $view->set("title", $post["title"]);
        $view->set("author", $post["author"]);
        $view->set("date", date("M d, Y H:i", strtotime($post["created"])));
        $view->set("content", htmlspecialchars_decode($post["content"]));
        $view->set("news_id", $post["id"]);
        $adminConsole = self::getAdminConsole($post["id"]);
        $view->set("adminconsole", $adminConsole);
        
        return appTemplate::loadLayout(array("content" => $view->output(), "title" => $post["title"]));
    }
    

    public static function loginAction($args = array()) {
        $error = "";
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = App::test_input($_POST['username']);
            $password = App::test_input($_POST['password']);
            $db = new DB();
            $user = $db->getUserByUsername($username);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['type_account'] = $user['type_account'];

                appTemplate::refreshSessionState();
                appTemplate::redirect(appTemplate::getBaseUrl());
            } else {
                $error = "Invalid username or password!";
            }
        }

        $view = new appTemplate("login.phtml");
        $view->set("error", $error);

        return appTemplate::loadLayout(array("content" => $view->output(), "title" => "Login"));
    }

    public static function logoutAction($args = array()) {
        session_destroy();
        appTemplate::clearSessionState();
        appTemplate::redirect(appTemplate::getBaseUrl() . "/login");
    }

    private static function checkAuthentication() {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            appTemplate::redirect(appTemplate::getBaseUrl() . "/login");
        }
    }

    private static function getAdminConsole($newsId) {
        if (!empty($_SESSION['type_account']) && $_SESSION['type_account'] === 'admin') {
            $baseUrl = appTemplate::getBaseUrl();
            return '<a href="' . $baseUrl . '/edit/' . $newsId . '">Edit</a> | <a href="' . $baseUrl . '/delete/' . $newsId . '">Delete</a>';
        }
        return '';
    }
}
