DROP DATABASE IF EXISTS DB_KEBAB;
CREATE DATABASE DB_KEBAB;
USE DB_KEBAB;

CREATE USER 'root'@'localhost' IDENTIFIED BY 'root';
GRANT ALL PRIVILEGES ON DB_KEBAB.* TO 'root'@'localhost';

/* Tabla de usuarios general */
CREATE TABLE USERS (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(30) NOT NULL UNIQUE,
    user_secret VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(30) NOT NULL UNIQUE,
    img_src VARCHAR(255) NOT NULL DEFAULT 'default.jpg'
);

/* Tabla de usuarios de tipo cliente */
CREATE TABLE CUSTOMERS (
    user_id INT PRIMARY KEY,
    customer_address VARCHAR(255) NOT NULL,
    points INT NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id)
);

/* Tabla de usuarios de tipo manager */
CREATE TABLE MANAGERS (
    user_id INT PRIMARY KEY,
    salary INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id)
);