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
            throw new Exception("Failed to connect to MySQL: (" . $this->_DB->connect_errno . ") " . $this->_DB->connect_error);
        }
    }
    
    /*
    Execute a MySQL query and return the result
    */
    public function executeQuery($query)
    {
        if (!$this->_DB) {
            $this->initDb();
        }
        
        $result = $this->_DB->query($query);
        if (!$result) {
            error_log("Query failed: " . $this->_DB->error . " | Query: " . $query);
            throw new Exception("Query failed: " . $this->_DB->error . " | Query: " . $query);
        }
        
        return $result;
    }
    public function getUserByUsername($username)
    {
        if (!$this->_DB) {
            $this->initDb();
        }

        $query = "SELECT * FROM users WHERE username = ?";
        $stmt = $this->_DB->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->_DB->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();

        return $user;
    }

    // 
    
    /*
    Get last inserted ID
    */
    public function getLastInsertedNewsId()
    {
        return $this->_DB->insert_id;
    }

    /*
    Save news to database
    */
    public function saveNews($news_id = 0)
    {
        $title = App::test_input($_POST["title"]);
        $content = App::test_input($_POST["content"]);
        $author = App::test_input($_POST["author"]);
        
        $permalink = $this->sterilize($title) . ".html";
        $permalink = $this->validatePermalink($permalink, $news_id);
        
        $date = $_POST["date"] 
            ? date("Y-m-d H:i:s", strtotime($_POST["date"] . " " . $_POST["time"])) 
            : date("Y-m-d H:i:s");

        if (!$news_id) {
            $query = "INSERT INTO posts (permalink, title, content, author, created) 
                      VALUES ('$permalink', '$title', '$content', '$author', '$date')";
            $msg = "News has been added successfully!";
        } else {
            $query = "UPDATE posts SET 
                      permalink = '$permalink', 
                      title = '$title', 
                      content = '$content', 
                      author = '$author', 
                      created = '$date', 
                      updated = NOW() 
                      WHERE id = $news_id";
            $msg = "News has been updated successfully!";
        }

        $this->executeQuery($query);
        
        $_SESSION['msg'] = $msg;
        
        return $news_id ?: $this->getLastInsertedNewsId();
    }
    
    /*
    Get news using the filter $search
    */
    public function getNewsByQuery($search = "")
    {
        $query = "SELECT * FROM posts";
        if ($search) {
            $query .= " WHERE title LIKE '%$search%' OR content LIKE '%$search%' OR author LIKE '%$search%'";
        }
        $query .= " ORDER BY created DESC";
        
        error_log("Executing query: " . $query);
        $result = $this->executeQuery($query);
        
        $array = [];
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
        $query = "DELETE FROM posts WHERE id = $news_id";
        $this->executeQuery($query); 
    }
    
    /*
    Load news post by id
    */
    public function loadNews($news_id)
    {
        $query = "SELECT * FROM posts WHERE id = $news_id";
        $result = $this->executeQuery($query);

        return $result->fetch_assoc();
    }
    
    /*
    Get news by permalink
    */
    public function getNewsByPermalink($permalink)
    {
        $permalink = str_replace(".html", "", $permalink);
        $permalink = $this->sterilize($permalink) . ".html";
        
        $query = "SELECT * FROM posts WHERE permalink = '$permalink'";
        $result = $this->executeQuery($query);

        return $result->fetch_assoc();
    }
    public function checkUserExists($username, $email)
{
    if (!$this->_DB) {
        $this->initDb();
    }

    $query = "SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?";
    $stmt = $this->_DB->prepare($query);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $this->_DB->error);
    }

    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $stmt->close();

    return $data['count'] > 0;
}
public function registerUser($username, $email, $password,$type)
{
    if (!$this->_DB) {
        $this->initDb();
    }

    // Hash the password using password_hash

    $query = "INSERT INTO users (username, email, password_hash, type_account) VALUES (?, ?, ?,?)";
    $stmt = $this->_DB->prepare($query);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $this->_DB->error);
    }

    $stmt->bind_param("ssss", $username, $email, $password,$type);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("User registration failed.");
    }

    $stmt->close();

    return true;
}



    /*
    Sterilize input for permalink
    */
    private function sterilize($title)
    {
        $result = strtolower($title);
        $result = preg_replace('/\W/', ' ', $result); // Strip non-word characters
        $result = preg_replace('/\s+/', '-', $result); // Replace spaces with dashes
        $result = trim($result, '-'); // Trim dashes

        return $result;
    }
    
    /*
    Validate permalink uniqueness
    */
    private function validatePermalink($permalink, $news_id = 0)
    {
        $query = "SELECT COUNT(*) FROM posts WHERE permalink = '$permalink' AND id != $news_id";
        $result = $this->executeQuery($query);

        $row = $result->fetch_row();
        if ($row[0] > 0) {
            $permalink = uniqid() . "-" . $permalink;
            return $this->validatePermalink($permalink, $news_id);
        }
        
        return $permalink;
    }
}
