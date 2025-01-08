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
            $articles = $xpath->query("//article");
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
    public static function generateFirebaseAuthToken() {
        // Include detaliile contului de service direct în cod
        $serviceAccount = [
            "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDARGLScUOVqkhT\nV6p+SFUlimzkcAjzMYUjfz9glj7Or1w7JXIXKkavjif216fKCzAixJLwFAHQwXWh\n9Q7WtypNuxRBfz7awLPaU+NL6Mrkj1an16UbwvbMwI3JE2gQcCBtvdzokCIhSdXc\ntQSDBGj3S4Mi/2JWF/XbvBa46LIn+S0Eo5XVwwZ0HU8d/NRuDfp1Mp68Z9rFpDBt\n4tPBq/tu0UFvStsitX4RmQ6zmMVZg/LrquIQ3BBEGE+3SkF5nTbD2j+qBhNF5XI2\nIaolb0Hcbs8AsJyMmnslKE2IeeRSd2vNQsrPLBIYYlgIijztI7Pj5lb9zhSyJgdL\nNTgFepy1AgMBAAECggEAOXeXRAhHp6HceVCwLIupK43a61KTUkFAZrI/erqdrkBa\n5HDWT5c6xnlKd6zQwYKjkhjIRIruWfuhEdYB5+qacd5TeRoz6sWDXGQLgaJklnlD\ncOpzNr+I7f01w96Rkaw5/SMtNrPyB5oempfeb9yvFQ9UdG758Pq7aUCoV/9xVKHQ\nHxPByXBLMJuo9vDtJ2IX7hWtq0wSeAHUo0FlM0W7b/1V3fTJA+2c5ENlede9aWUb\nkl3cSlspH6FddEKX9WFF59cGP7IdEGpTaS2HoVpV4dEAHrZ3zq3gMr5cLSg4obUj\nK01qdY1y3C9VwwzKBAPm65VfOaOikoF8PoAmSgiwJwKBgQDxAV9sZ/LMLYcawbmp\nwC+lHBtaCvNGXV7IM0qUrxTFtpdIlPc6oZEyJHksZ3aNLkXOjk3qXcSg9Ypu05GS\nqxrkgabzU2QY9B7UB0rpKZfn7Zd5gHmrAozMTg6s8gC+SJkyV8AL+6X/Hki6IvU5\noALNgKvpxN6zqW4WcUnn7NoLXwKBgQDMOrtzLkIgvYqwnJAQFiKqoh5SfNtCLu8Z\nKTTEPGR0PQJmA3PV67eIeDMvAIwCPrIOmUqcOkHX9xxDUPayywRC9215JGkoS6Dd\nOIEM8RKpUWbYJdQ6NPj6FuAjZpBjSW6ABnnu/fkCeB1Dgd9PBmaXNbz2FGceuHzi\nwbKIAlGkawKBgQDmPdt942jPqwcRhtXq2BIseMegpCl5paXxOR8dII6FvESXMMlo\nGAZwkuu4gjd99SD3jnfdWSuKYkmYS0MdjZ2phDuM5rQQKthw0267heL7zb4Sc6zI\ntSzx2finPKN9JjpFIBP23rjdG397Y/5GyRkkXrLeKBhiJ5Fmm2Bx05MTnQKBgHqm\nMvjDKRd8fRP/kkz23i7XWZp0PUEL6q+TnYrUMgfUs+IL5L7t5rTgauypSWv3tvsp\neDNGkVBfqOuMbfuGDLMi4O3FvhljAeKZEndxN6HTrw3T+hZSxct7fXQFHmViLihY\nu1WZ1Ld05y4pirBsyaO5tBecvSkn5mhPpyYjLmCtAoGBAKN1eQCUF4l7MenBy/GF\nh/FgV+58kdw8MUNigMI4fcuDTTxdDSZON0eW+Bw/8gHkFDQqHbIrkbCwzrN4QQ5Q\nlyPrSRHMspIUZHj9f5RhdolRmsKc85/yONEpbHU2O83OUOFXz7tbtWfBDK7sNQL6\nEp0Ah3ZCmNQgxTPPGJIp+m+2\n-----END PRIVATE KEY-----\n",
            "client_email" => "firebase-adminsdk-7hzlw@catalog-cce7f.iam.gserviceaccount.com"
        ];
    
        // Setează detaliile token-ului
        $header = [
            "alg" => "RS256",
            "typ" => "JWT"
        ];
    
        $nowSeconds = time();
        $payload = [
            "iss" => $serviceAccount['client_email'],       // Email-ul clientului din JSON
            "sub" => $serviceAccount['client_email'],       // Email-ul clientului din JSON
            "aud" => "https://firestore.googleapis.com/",   // Adresa Firestore
            "iat" => $nowSeconds,                           // Timpul curent
            "exp" => $nowSeconds + 3600                     // Tokenul expiră după 1 oră
        ];
    
        // Codifică header-ul și payload-ul în Base64
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    
        // Creează semnătura
        $unsignedToken = $base64UrlHeader . "." . $base64UrlPayload;
        openssl_sign($unsignedToken, $signature, $serviceAccount['private_key'], OPENSSL_ALGO_SHA256);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
        // Returnează token-ul complet
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
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
    
            // Obține emailul utilizatorului curent
            $currentUserEmail = $_SESSION['email'] ?? null; // Emailul utilizatorului
            $newsTitle = htmlspecialchars($_POST['title']); // Titlul știrii
    
            if (!empty($currentUserEmail)) {
                // Datele necesare pentru extensia Firebase Email
                $emailData = [
                    "fields" => [
                        "to" => ["stringValue" => $currentUserEmail],
                        "message" => [
                            "mapValue" => [
                                "fields" => [
                                    "subject" => ["stringValue" => "Știrea ta a fost postată cu succes!"],
                                    "text" => ["stringValue" => "Salut,\n\nȘtirea ta intitulată \"" . $newsTitle . "\" a fost postată cu succes pe platformă.\n\nMulțumim că ai contribuit!\n\nEchipa"]
                                ]
                            ]
                        ]
                    ]
                ];
    
                // URL Firebase Firestore API pentru colecția `mail`
                $firebaseUrl = "https://firestore.googleapis.com/v1/projects/catalog-cce7f/databases/(default)/documents/mail";
    
                // Token Firebase din variabilele de mediu
                $firebaseToken = self::generateFirebaseAuthToken();
    
                // Trimite documentul în colecția `mail`
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $firebaseUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $firebaseToken",
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
                curl_close($ch);
    
                if ($httpCode >= 400) {
                    error_log("Eroare la trimiterea emailului prin Firebase: $response");
                }
            }
    
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
