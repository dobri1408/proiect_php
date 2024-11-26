<?php


class DB {
    
    protected $_CONFIG;
    protected $_DB;
    
    public function __construct()
    {
        $this->_CONFIG = parse_ini_file(APPLICATION_PATH . DS. "conf". DS . "application.ini.php", TRUE);
    }

    public function initDb()
    {
        $config = $this->_CONFIG;
        $this->_DB = new mysqli($config["database"]["server"], $config["database"]["username"], $config["database"]["password"], $config["database"]["db"], $config["database"]["port"]);
        if ($this->_DB->connect_errno) {
            throw new Exception( "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
        }
    }
    
    /*
    Execute a mysql query and return result
    */    
    public function executeQuery($query)
    {
        if(!$this->_DB)
            $this->initDb();
        
        if(!$return = $this->_DB->query($query))
        {
            throw new Exception("Query failed: ". $this->_DB->error. " == $query");
        }
        
        return $return;
    }
    
    /*
    Get last inserted id
    */
    public function getLastInsertedNewsId()
    {
        return $this->_DB->insert_id;
    }

    /*
    Save news to database
    */
    public function saveNews($news_id=0)
    {
        $title = App::test_input($_POST["title"]);
        $content = App::test_input($_POST["content"]);
        $author = App::test_input($_POST["author"]);
        
        $permalink= $this->sterilize($title).".html";
        
        $permalink = $this->validatePermalink($permalink, $news_id);
        
        if($_POST["date"])
            $date = date("Y-m-d H:i:s",strtotime($_POST["date"]." ".$_POST["time"]));
        else
            $date= date("Y-m-d H:i:s");

        if(!$news_id){
            $query = "insert into posts set permalink='".$permalink."', title=\"".$title."\", content=\"".$content."\", author=\"".$author."\", created=\"".$date."\"; ";
            $msg = "News has been added successfully!";
        }
        else{
            $query = "update posts set permalink='".$permalink."', title=\"".$title."\", content=\"".$content."\", author=\"".$author."\", created=\"".$date."\", updated=now() WHERE id='".$news_id."';";
            $msg = "News has been updated successsfully!";
        }

        $this->executeQuery($query);
        
        $_SESSION['msg']  = $msg;
        
        if($news_id)
            return $news_id;
        else
            return $this->getLastInsertedNewsId();
    }
    
    /*
    Get news using the filter $search
    */
    public function getNewsByQuery($search = "")
    {
        $array = array();
        
        $query = "select * from posts ";
        if($search)
            $query .= " where title like '%".$search."%' or content like '%".$search."%' or author like '%".$search."%'";

        $query .= " order by created desc";
        
        $result  = $this->executeQuery($query);
        
        while ($row = $result->fetch_assoc()) {
            $array[] = $row;
        }
        
        return $array;
    }

    /*
    Delete news post by id
    */
    public function deleteNews($news_id)
    {
        $query = "DELETE FROM posts where id='".$news_id."'";
        return $result  = $this->executeQuery($query); 
    }
    
    /*
    Load news post by id
    */
    public function loadNews($news_id)
    {
        $query = "select * from posts where id='".$news_id."' order by id desc";
        $result = $this->executeQuery($query);

        if($result)
            $row = $result->fetch_array();
        
        return $row;
        
    }
    
    /*
    Get news ID from permalink
    */
    
    public function getNewsByPermalink($permalink)
    {
        $permalink = str_replace(".html", "", $permalink);
        $permalink = $this->sterilize($permalink).".html";
            
        $query = "select * from posts where permalink='".$permalink."'";
        $result = $this->executeQuery($query);

        if($result)
            $row = $result->fetch_array();
        
        return $row;
        
    }
    
    private function sterilize($title)
    {
        $result = strtolower($title);
        // strip all non word chars
        $result = preg_replace('/\W/', ' ', $result);
        // replace all white space sections with a dash
        $result = preg_replace('/\ +/', '-', $result);
        // trim dashes
        $result = preg_replace('/\-$/', '', $result);
        $result = preg_replace('/^\-/', '', $result);

        return $result;
    }
    
    private function validatePermalink($permalink, $news_id = 0)
    {
        $query = "select count(*) from posts where permalink='".$permalink."' and id!='".$news_id."'";
        $result = $this->executeQuery($query);

        if($result)
            $row = $result->fetch_row();
        
        if($row[0] > 0){
            $permalink = ($row[0]+1)."_".$permalink;
            return $this->validatePermalink($permalink, $news_id);
        }
        
        return($permalink);
        
    }
    
}