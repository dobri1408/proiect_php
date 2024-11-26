; Configuration file for the application
; Ensure this file is properly secured and not accessible publicly

[main]
; Directory where the application is located
application.directory = "/application/"

[database]
; Database connection details
username = "ionut"
password = "ionut"
db = "news"
server = "127.0.0.1"
port = 3306

[route]
; Routes mapping to actions
/ = "indexAction"
/add = "addNewsAction"
/edit = "updateNewsAction"
/delete = "deleteNewsAction"
/search = "searchNewsAction"
/view = "viewNewsAction"
