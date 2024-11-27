[main]
; Directory where the application is located
application.directory = "/application/"

[database]
; Database connection details
username = "${DB_USERNAME}"
password = "${DB_PASSWORD}"
db = "${DB_NAME}"
server = "${DB_HOST}"
port = "${DB_PORT}"

[route]
; Routes mapping to actions
/ = "indexAction"
/add = "addNewsAction"
/edit = "updateNewsAction"
/delete = "deleteNewsAction"
/search = "searchNewsAction"
/view = "viewNewsAction"
/login = "loginAction"