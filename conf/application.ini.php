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

[email]
; Email configuration
smtp_host = "smtp.gmail.com"       ; SMTP server (e.g., Gmail SMTP)
smtp_port = 587                    ; SMTP port for TLS
smtp_secure = "tls"                ; Security protocol (tls or ssl)
smtp_username = "catalog@dobriceansoftware.com" ; SMTP username (e.g., Gmail address)
smtp_password = "brilizvcoywevwjd" ; SMTP password or app-specific password
smtp_from = "no-reply@example.com" ; Sender email address
smtp_from_name = "Platform Admin"  ; Sender name

[route]
; Routes mapping to actions
/ = "indexAction"
/add = "addNewsAction"
/edit = "updateNewsAction"
/delete = "deleteNewsAction"
/search = "searchNewsAction"
/view = "viewNewsAction"
/login = "loginAction"
/register_editor= "registerEditorAction"
/register_admin= "registerAdminAction"
/logout = "logoutAction"