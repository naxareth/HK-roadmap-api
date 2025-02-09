run the ff. in the terminal:
php -S localhost:8000


```
CREATE TABLE admin (
admin_id varchar(50) NOT NULL PRIMARY KEY,
email varchar(50) NOT NULL,
password varchar(255) DEFAULT NULL
)

CREATE TABLE student_tokens (
student_id varchar(255) NOT NULL,
token varchar(255) NOT NULL,
created_at timestamp NOT NULL DEFAULT current_timestamp()
)

CREATE TABLE admin_tokens (
admin_id varchar(255) NOT NULL,
token varchar(255) DEFAULT NULL,
created_at timestamp NOT NULL DEFAULT current_timestamp()
)

CREATE TABLE document (
student_id varchar(50) NOT NULL PRIMARY KEY,
file_path varchar(50) NOT NULL,
created_at datetime NOT NULL )

CREATE TABLE requirements (
student_id varchar(50) NOT NULL PRIMARY KEY,
event_name varchar(50) NOT NULL,
due_date date NOT NULL,
shared int(1) NOT NULL )

CREATE TABLE student (
student_id varchar(50) NOT NULL PRIMARY KEY,
email varchar(50) NOT NULL,
password varchar(50) NOT NULL, )
```
