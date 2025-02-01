CREATE TABLE admin(
name varchar(50) NOT NULL PRIMARY KEY,
email varchar(50) NOT NULL,
password varchar(50) NOT NULL,
token varchar(255) DEFAULT NULL )

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
email varchar(50) NOT NULL, password varchar(50) NOT NULL,
token varchar(255) DEFAULT NULL )