<?php

class appController{
    
    public static function indexAction($args = array())
    {
        $db = new DB();
        
        $query = "";
        if(isset($args[0]))
            $query = App::test_input(strip_tags($args[0]));
        
        $news = $db->getNewsByQuery($query);
        
        $list_output = "";
        
        if(is_array($news))
            foreach($news as $post){
                if(!$post["permalink"])
                    $post["permalink"] = $post["id"];
                $view = new appTemplate("news/news-list.phtml");
                $view->set("title", $post["title"]); 
                $view->set("author", $post["author"]);
                $view->set("news_id", $post["id"]);
                $view->set("permalink", $post["permalink"]);
                $view->set("date", date("M d, Y H:i", strtotime($post["created"])));
                $list_output .= $view->output();
            }

        $view = new appTemplate("news/index.phtml");
        $view->set("title", "Latest news");
        $view->set("content", $list_output);
        
        
       return  appTemplate::loadLayout(array("content"=>$view->output(), "title"=>"Homepage", "query"=>stripslashes($query)));
          
    }
    
    public static function searchNewsAction($args = array())
    {
        $query = $_GET["query"];
        return appController::indexAction(array($query));
    }
    
    public static function addNewsAction($args = array(), $update_news=0)
    {
        $db = new DB();
        $news = array("title"=>"","author"=>"","date"=>"","time"=>"","content"=>"", "updated"=>"", "id"=>"");
        $news_id = 0;
        
        if($args && $args[0]){
            $news_id = (int)$args[0];
            $news  = $db->loadNews($news_id);  
            if(!$news)
              appTemplate::redirect(appTemplate::getBaseUrl());
            $news["date"] = date("Y-m-d", strtotime($news["created"]));
            $news["time"] = date("H:i", strtotime($news["created"]));
        }
        
        if($_SERVER['REQUEST_METHOD'] == "POST")
        {
            $news_id = $db->saveNews($news_id);
            
            /*if($update_news)
                appTemplate::redirect(appTemplate::getBaseUrl() . "/edit/".$news_id);
            else*/
                appTemplate::redirect(appTemplate::getBaseUrl());
        }
        
        $view = new appTemplate("news/add.phtml");
        if($news_id)
            $pageTitle = "Edit News #".$news['id'];
        else
            $pageTitle = "Add News";
        $view->set("pageTitle", $pageTitle);
        $view->set("news_id", $news['id']);
        $view->set("input_title", $news['title']);
        $view->set("input_author", $news['author']);
        $view->set("input_date", $news['date']);
        $view->set("input_time", $news['time']);
        $view->set("input_content", $news['content']);
        
        
        return appTemplate::loadLayout(array("content"=>$view->output(), "title"=>$pageTitle));
    }
    
    public static function updateNewsAction($args = array())
    {
        return appController::addNewsAction($args, 1);
    }
    
    public static function viewNewsAction($args = array())
    {
        $db = new DB();
        
        $news_id = $args[0];
        
        if(! is_numeric ($news_id))
            $post = $db->getNewsByPermalink($news_id);
        else
            $post = $db->loadNews($news_id);
        
        if(!$post)
              appTemplate::redirect(appTemplate::getBaseUrl());  
        
        $view = new appTemplate("news/view.phtml");
        $view->set("title", $post["title"]);
        $view->set("author", $post["author"]);
        $view->set("date", date("M d, Y H:i", strtotime($post["created"])));
        $view->set("content", htmlspecialchars_decode($post["content"]));
        $view->set("news_id", $post["id"]);
        
       return  appTemplate::loadLayout(array("content"=>$view->output(), "title"=>$post["title"]));
        
    }
    
    public static function deleteNewsAction($args = array())
    {
        $news_id = (int)$args[0];
        
        $db = new DB();
        $db->deleteNews($news_id);
        
        appTemplate::redirect(appTemplate::getBaseUrl());
    }
    
    public static function noRoute($args)
    {
        $view = new appTemplate("404.phtml");
        $view->set("title", "404 - No page found");
        
        
        
       return  appTemplate::loadLayout(array("content"=>$view->output(), "title"=>"404 - No page found"));
    }
    
}