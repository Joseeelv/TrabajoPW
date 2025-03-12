DROP DATABASE IF EXISTS DB_KEBAB;
CREATE DATABASE DB_KEBAB;
USE DB_KEBAB;

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
    points INT NOT NULL DEFAULT 100,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id)
);

/* Tabla de usuarios de tipo manager */
CREATE TABLE MANAGERS (
    user_id INT PRIMARY KEY,
    salary INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id)
);

/* Tabla reviews */
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

/* Tabla ingredientes */
CREATE TABLE INGREDIENTS (
    ingredient_id INT PRIMARY KEY AUTO_INCREMENT,
    ingredient_name VARCHAR(30) NOT NULL,
    cost DECIMAL(5,2) NOT NULL, /* Coste de compra del ingrediente */
    stock INT NOT NULL DEFAULT 0,
    vegan BOOLEAN NOT NULL DEFAULT FALSE
);

/* Tabla de alergenos */
CREATE TABLE ALLERGENS (
    allergen_id INT PRIMARY KEY AUTO_INCREMENT,
    allergen_name VARCHAR(30) NOT NULL,
    img_src VARCHAR(255)
);

/* Alergenos de cada ingrediente */
/* Enlace ->  INGREDIENTS - ALLERGENS */
CREATE TABLE INGREDIENTS_ALLERGENS (
    ingredient_id INT NOT NULL,
    allergen_id INT NOT NULL,
    PRIMARY KEY (ingredient_id, allergen_id),
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE,
    FOREIGN KEY (allergen_id) REFERENCES ALLERGENS(allergen_id) ON DELETE CASCADE
);

/* Tabla de productos */
CREATE TABLE PRODUCTS (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(30) NOT NULL,
    product_price DECIMAL(5,2) NOT NULL,
    category ENUM('Durum', 'Döner', 'Lahmacun', 'Entrantes', 'Bebida', 'Postres') NOT NULL,
    img_src VARCHAR(255)
);

/* Tabla de relación productos-ingredientes */
CREATE TABLE PRODUCTS_INGREDIENTS (
    product_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    PRIMARY KEY (product_id, ingredient_id),
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE
);

/* Tabla de productos sin ingredientes */
CREATE TABLE PRODUCTS_NO_INGREDIENTS (
    product_id INT PRIMARY KEY,
    cost DECIMAL(5,2) NOT NULL, /* Coste de compra del producto */
    stock INT NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE
);

/* Tabla de alérgenos para productos sin ingredientes */
CREATE TABLE PRODUCTS_NO_INGREDIENTS_ALLERGENS (
    product_id INT NOT NULL,
    allergen_id INT NOT NULL,
    PRIMARY KEY (product_id, allergen_id),
    FOREIGN KEY (product_id) REFERENCES PRODUCTS_NO_INGREDIENTS(product_id) ON DELETE CASCADE,
    FOREIGN KEY (allergen_id) REFERENCES ALLERGENS(allergen_id) ON DELETE CASCADE
);

/* Tabla de pedidos */
CREATE TABLE ORDERS (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_date DATE NOT NULL,
    order_status ENUM('pending', 'delivered', 'cancelled') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES CUSTOMERS(user_id) ON DELETE CASCADE
);

/* Tabla de relación pedidos-productos */
CREATE TABLE ORDERS_PRODUCTS (
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    PRIMARY KEY (order_id, product_id),
    FOREIGN KEY (order_id) REFERENCES ORDERS(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE
);

/* Ingredientes seleccionados/excluidos en un pedido */
/* Enlace -> ORDERS_PRODUCTS - INGREDIENTS */
/* Permite personalizar los ingredientes de cada producto en un pedido */
CREATE TABLE ORDERS_PRODUCTS_INGREDIENTS (
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    included BOOLEAN NOT NULL, /* TRUE si se incluye, FALSE si se excluye */
    PRIMARY KEY (order_id, product_id, ingredient_id),
    FOREIGN KEY (order_id, product_id) REFERENCES ORDERS_PRODUCTS(order_id, product_id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE
);
