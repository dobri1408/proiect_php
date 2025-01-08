<?php

class appController {

    public static function fetchExternalNews() {
        $url = "https://stirileprotv.ro/ultimele-stiri/";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Dacă HTTPS dă erori
        $output = curl_exec($ch);
        curl_close($ch);
    
        if ($output) {
            $dom = new DOMDocument();
            @$dom->loadHTML($output);
            $xpath = new DOMXPath($dom);
    
            // Extragerea articolelor
            $articles = $xpath->query("//div[@class='article-content']");
            $news = [];
            foreach ($articles as $article) {
                $title = $xpath->query(".//h2/a", $article)->item(0)->textContent ?? '';
                $link = $xpath->query(".//h2/a/@href", $article)->item(0)->nodeValue ?? '';
                $summary = $xpath->query(".//p", $article)->item(0)->textContent ?? '';
                $dateRaw = $xpath->query(".//div[@class='article-date']", $article)->item(0)->textContent ?? '';
    
                // Procesarea datei
                $date = self::processExternalDate(trim($dateRaw));
    
                $news[] = [
                    'title' => trim($title),
                    'link' => trim($link),
                    'summary' => trim($summary),
                    'date' => $date,
                ];
            }
    
            return $news;
        }
    
        return [];
    }
    
    private static function processExternalDate($dateRaw) {
        // Transforma "acum 23 minute" sau "acum 1 ore 40 de min" într-un timestamp
        $date = new DateTime();
        if (preg_match('/acum (\d+) minute/', $dateRaw, $matches)) {
            $date->modify("-{$matches[1]} minutes");
        } elseif (preg_match('/acum (\d+) ore(?: (\d+) minute)?/', $dateRaw, $matches)) {
            $date->modify("-{$matches[1]} hours");
            if (isset($matches[2])) {
                $date->modify("-{$matches[2]} minutes");
            }
        }
        return $date->format("M d, Y H:i");
    }
    public static function indexAction($args = array()) {
        self::checkAuthentication();
    
        $db = new DB();
        $query = isset($args[0]) ? App::test_input(strip_tags($args[0])) : "";
        $news = $db->getNewsByQuery($query);
        $externalNews = self::fetchExternalNews(); // Știrile externe
    
        $list_output = "";
    
        if (is_array($news)) {
            foreach ($news as $post) {
                if (!$post["permalink"]) {
                    $post["permalink"] = $post["id"];
                }
                
                // Construirea URL-ului complet pentru știrile interne
                $baseUrl = appTemplate::getBaseUrl();
                $fullPermalink = $baseUrl . '/view/' . htmlspecialchars($post["permalink"]);
                
                $view = new appTemplate("news/news-list.phtml");
                $view->set("title", htmlspecialchars($post["title"]));
                $view->set("author", htmlspecialchars($post["author"]));
                $view->set("news_id", (int)$post["id"]);
                $view->set("permalink", $fullPermalink); // Setarea link-ului complet
                $view->set("date", date("M d, Y H:i", strtotime($post["created"])));
        
                $adminConsole = self::getAdminConsole($post["id"]);
                $view->set("adminconsole", $adminConsole);
        
                $list_output .= $view->output();
            }
        }
        // Adăugarea știrilor externe
        if (!empty($externalNews)) {
            foreach ($externalNews as $external) {
                $view = new appTemplate("news/news-list.phtml");
                $view->set("title", htmlspecialchars($external['title']));
                $view->set("author", "Extern");
                $view->set("news_id", 0); // Nu avem un ID în baza de date pentru știrile externe
        
                // Link-ul extern corect
                $view->set("permalink", htmlspecialchars($external['link']));
                
                // Data procesată din sursa externă
                $view->set("date", htmlspecialchars($external['date']));
        
                // Nu setăm adminConsole pentru știrile externe
                $view->set("adminconsole", "");
        
                $list_output .= $view->output();
            }
        }
    
        $view = new appTemplate("news/index.phtml");
        $view->set("title", "Latest news");
        $view->set("content", $list_output);
    
        return appTemplate::loadLayout(array("content" => $view->output(), "title" => "Homepage", "query" => stripslashes($query)));
    }

    public static function viewNewsAction($args = array()) {
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
        $view->set("title", htmlspecialchars($post["title"]));
        $view->set("author", htmlspecialchars($post["author"]));
        $view->set("date", date("M d, Y H:i", strtotime($post["created"])));
        $view->set("content", htmlspecialchars_decode($post["content"]));
        $view->set("news_id", (int)$post["id"]);
        $adminConsole = self::getAdminConsole($post["id"]);
        $view->set("adminconsole", $adminConsole);

        return appTemplate::loadLayout(array("content" => $view->output(), "title" => $post["title"]));
    }

    public static function loginAction($args = array()) {
        $error = "";
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = App::test_input(strip_tags($_POST['username']));
            $password = App::test_input(strip_tags($_POST['password']));
            $db = new DB();
            $user = $db->getUserByUsername($username);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = htmlspecialchars($username);
                $_SESSION['type_account'] = htmlspecialchars($user['type_account']);

                appTemplate::refreshSessionState();
                appTemplate::redirect(appTemplate::getBaseUrl());
            } else {
                $error = "Invalid username or password!";
            }
        }

        $view = new appTemplate("login.phtml");
        $view->set("error", htmlspecialchars($error));

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

    public static function searchNewsAction($args = array()) {
        self::checkAuthentication();
        $query = htmlspecialchars(strip_tags($_GET["query"]));
        return appController::indexAction(array($query));
    }

    public static function addNewsAction($args = array(), $update_news = 0) {
        self::checkAuthentication();
        $db = new DB();
        $news = array("title" => "", "author" => "", "date" => "", "time" => "", "content" => "", "updated" => "", "id" => "");
        $news_id = 0;

        if ($args && $args[0]) {
            $news_id = (int)$args[0];
            $news = $db->loadNews($news_id);  
            if (!$news) {
                appTemplate::redirect(appTemplate::getBaseUrl());
            }
            $news["date"] = date("Y-m-d", strtotime($news["created"]));
            $news["time"] = date("H:i", strtotime($news["created"]));
        }

        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $news_id = $db->saveNews($news_id);
            appTemplate::redirect(appTemplate::getBaseUrl());
        }

        $view = new appTemplate("news/add.phtml");
        $view->set("pageTitle", $news_id ? "Edit News #" . (int)$news['id'] : "Add News");
        $view->set("news_id", (int)$news['id']);
        $view->set("input_title", htmlspecialchars($news['title']));
        $view->set("input_author", htmlspecialchars($news['author']));
        $view->set("input_date", htmlspecialchars($news['date']));
        $view->set("input_time", htmlspecialchars($news['time']));
        $view->set("input_content", htmlspecialchars($news['content']));

        return appTemplate::loadLayout(array("content" => $view->output(), "title" => $news_id ? "Edit News" : "Add News"));
    }

    public static function updateNewsAction($args = array()) {
        self::checkAuthentication();
        return appController::addNewsAction($args, 1);
    }

    public static function registerEditorAction($args = array()) {
        $error = "";
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = App::test_input(strip_tags($_POST['username']));
            $email = App::test_input(strip_tags($_POST['email']));
            $password = App::test_input(strip_tags($_POST['password']));
            $confirm_password = App::test_input(strip_tags($_POST['confirm_password']));

            if ($password !== $confirm_password) {
                $error = "Passwords do not match!";
            } else {
                $db = new DB();
                $user_exists = $db->checkUserExists($username, $email);
                if ($user_exists) {
                    $error = "Username or Email already exists!";
                } else {
                    $db->registerUser($username, $email, password_hash($password, PASSWORD_BCRYPT),"editor");
                    appTemplate::redirect(appTemplate::getBaseUrl() . "/login");
                }
            }
        }

        $view = new appTemplate("register_editor.phtml");
        $view->set("error", htmlspecialchars($error));

        return appTemplate::loadLayout(array("content" => $view->output(), "title" => "Register"));
    }

    public static function registerAdminAction($args = array()) {
        $error = "";
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = App::test_input(strip_tags($_POST['username']));
            $email = App::test_input(strip_tags($_POST['email']));
            $password = App::test_input(strip_tags($_POST['password']));
            $confirm_password = App::test_input(strip_tags($_POST['confirm_password']));

            if ($password !== $confirm_password) {
                $error = "Passwords do not match!";
            } else {
                $db = new DB();
                $user_exists = $db->checkUserExists($username, $email);
                if ($user_exists) {
                    $error = "Username or Email already exists!";
                } else {
                    $db->registerUser($username, $email, password_hash($password, PASSWORD_BCRYPT),"admin");
                    appTemplate::redirect(appTemplate::getBaseUrl() . "/login");
                }
            }
        }

        $view = new appTemplate("register_admin.phtml");
        $view->set("error", htmlspecialchars($error));

        return appTemplate::loadLayout(array("content" => $view->output(), "title" => "Register"));
    }

    private static function getAdminConsole($newsId) {
        if (!empty($_SESSION['type_account']) && $_SESSION['type_account'] === 'admin') {
            $baseUrl = appTemplate::getBaseUrl();
            return '<a href="' . htmlspecialchars($baseUrl . '/edit/' . $newsId) . '">Edit</a> | <a href="' . htmlspecialchars($baseUrl . '/delete/' . $newsId) . '">Delete</a>';
        }
        return '';
    }
}

?>
