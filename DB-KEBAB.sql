DROP DATABASE IF EXISTS DB_KEBAB;
CREATE DATABASE DB_KEBAB;
USE DB_KEBAB;

/* General users table */
CREATE TABLE USERS (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(30) NOT NULL UNIQUE,
    user_secret VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(30) NOT NULL UNIQUE,
    img_src VARCHAR(255) NOT NULL DEFAULT 'default.jpg'
);

/* Customers users table */
CREATE TABLE CUSTOMERS (
    user_id INT PRIMARY KEY,
    customer_address VARCHAR(255) NOT NULL,
    points INT NOT NULL DEFAULT 100,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id)
);

/* Managers users table */
CREATE TABLE MANAGERS (
    user_id INT PRIMARY KEY,
    salary INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id)
);

/* Reviews table */
CREATE TABLE REVIEWS (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    review_date DATE NOT NULL,
    review_text TEXT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    manager_id INT,
    answer TEXT,
    FOREIGN KEY (user_id) REFERENCES CUSTOMERS(user_id),
    FOREIGN KEY (manager_id) REFERENCES MANAGERS(user_id)
);

/* Ingredients table */
CREATE TABLE INGREDIENTS (
    ingredient_id INT PRIMARY KEY AUTO_INCREMENT,
    ingredient_name VARCHAR(30) NOT NULL,
    cost DECIMAL(5,2) NOT NULL, /* Purchase cost of the ingredient */
    stock INT NOT NULL DEFAULT 0,
    vegan BOOLEAN NOT NULL DEFAULT FALSE
);

/* Allergens table */
CREATE TABLE ALLERGENS (
    allergen_id INT PRIMARY KEY AUTO_INCREMENT,
    allergen_name VARCHAR(30) NOT NULL,
    img_src VARCHAR(255)
);

/* Allergens for each ingredient */
/* Link -> INGREDIENTS - ALLERGENS */
CREATE TABLE INGREDIENTS_ALLERGENS (
    ingredient_id INT NOT NULL,
    allergen_id INT NOT NULL,
    PRIMARY KEY (ingredient_id, allergen_id),
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE,
    FOREIGN KEY (allergen_id) REFERENCES ALLERGENS(allergen_id) ON DELETE CASCADE
);

/* Products table */
CREATE TABLE PRODUCTS (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(30) NOT NULL,
    product_price DECIMAL(5,2) NOT NULL,
    category ENUM('Durum', 'Döner', 'Lahmacun', 'Appetizers', 'Drink', 'Desserts') NOT NULL,
    img_src VARCHAR(255)
);

/* Products-ingredients relationship table */
CREATE TABLE PRODUCTS_INGREDIENTS (
    product_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    PRIMARY KEY (product_id, ingredient_id),
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE
);

/* Table for products without ingredients */
CREATE TABLE PRODUCTS_NO_INGREDIENTS (
    product_id INT PRIMARY KEY,
    cost DECIMAL(5,2) NOT NULL, /* Purchase cost of the product */
    stock INT NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE
);

/* Allergens for products without ingredients */
CREATE TABLE PRODUCTS_NO_INGREDIENTS_ALLERGENS (
    product_id INT NOT NULL,
    allergen_id INT NOT NULL,
    PRIMARY KEY (product_id, allergen_id),
    FOREIGN KEY (product_id) REFERENCES PRODUCTS_NO_INGREDIENTS(product_id) ON DELETE CASCADE,
    FOREIGN KEY (allergen_id) REFERENCES ALLERGENS(allergen_id) ON DELETE CASCADE
);

/* Orders table */
CREATE TABLE ORDERS (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_date DATE NOT NULL,
    order_status ENUM('pending', 'delivered', 'cancelled') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES CUSTOMERS(user_id) ON DELETE CASCADE
);

/* Orders-products relationship table */
CREATE TABLE ORDERS_PRODUCTS (
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    PRIMARY KEY (order_id, product_id),
    FOREIGN KEY (order_id) REFERENCES ORDERS(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE
);

/* Selected/excluded ingredients in an order */
/* Link -> ORDERS_PRODUCTS - INGREDIENTS */
/* Allows customization of ingredients for each product in an order */
CREATE TABLE ORDERS_PRODUCTS_INGREDIENTS (
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    included BOOLEAN NOT NULL, /* TRUE if included, FALSE if excluded */
    PRIMARY KEY (order_id, product_id, ingredient_id),
    FOREIGN KEY (order_id, product_id) REFERENCES ORDERS_PRODUCTS(order_id, product_id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE
);
