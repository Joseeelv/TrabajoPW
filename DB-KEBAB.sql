DROP DATABASE IF EXISTS DB_KEBAB;

CREATE DATABASE DB_KEBAB;

USE DB_KEBAB;

/* Tabla de usuarios generales: Almacena la información general de los usuarios en el sistema */
CREATE TABLE USERS (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(30) NOT NULL UNIQUE,
    user_secret VARCHAR(100) NOT NULL,
    email VARCHAR(30) NOT NULL UNIQUE,
    user_type ENUM('customer', 'manager', 'admin') NOT NULL,
    img_src VARCHAR(255) NOT NULL DEFAULT 'default.jpg'
);

/* Tabla de clientes: Almacena información adicional para los clientes */
CREATE TABLE CUSTOMERS (
    user_id INT PRIMARY KEY,
    customer_address VARCHAR(255) NOT NULL,
    points INT NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id) ON DELETE CASCADE
);

/* Tabla de gerentes: Almacena información adicional para los gerentes */
CREATE TABLE MANAGERS (
    user_id INT PRIMARY KEY,
    salary INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id) ON DELETE CASCADE
);

/* Tabla de ingredientes: Almacena los ingredientes disponibles para los productos */
CREATE TABLE INGREDIENTS (
    ingredient_id INT PRIMARY KEY AUTO_INCREMENT,
    ingredient_name VARCHAR(30) NOT NULL UNIQUE,
    cost DECIMAL(5, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    vegan BOOLEAN NOT NULL DEFAULT FALSE
);

/* Tabla de alérgenos: Almacena información sobre los alérgenos */
CREATE TABLE ALLERGENS (
    allergen_id INT PRIMARY KEY AUTO_INCREMENT,
    allergen_name VARCHAR(30) NOT NULL UNIQUE,
    img_src VARCHAR(255)
);

/* Enlace entre INGREDIENTES y ALÉRGENOS */
CREATE TABLE INGREDIENTS_ALLERGENS (
    ingredient_id INT NOT NULL,
    allergen_id INT NOT NULL,
    PRIMARY KEY (ingredient_id, allergen_id),
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE,
    FOREIGN KEY (allergen_id) REFERENCES ALLERGENS(allergen_id) ON DELETE CASCADE
);

/* Tabla de productos: Almacena los elementos del menú disponibles para ordenar */
CREATE TABLE PRODUCTS (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(30) NOT NULL UNIQUE,
    product_price DECIMAL(5, 2) NOT NULL,
    /* Precio en el momento de la creación (puede cambiar) */
    category ENUM(
        'Menu',
        'Durum',
        'Döner',
        'Lahmacun',
        'Starter',
        'Drink',
        'Dessert'
    ) NOT NULL,
    img_src VARCHAR(255),
    /* Solo si la categoría es 'Drink' o 'Dessert' */
    cost DECIMAL(5, 2) DEFAULT NULL,
    stock INT DEFAULT NULL
);

/* Enlace entre MENÚS y los productos dentro del menú */
CREATE TABLE MENUS_CONTENTS (
    menu_product_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    PRIMARY KEY (menu_product_id, product_id),
    FOREIGN KEY (menu_product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    /* El producto del menú debe ser un menú */
    CHECK (menu_product_id != product_id)
    /* Un menú no puede contenerse a sí mismo */
);

/* Enlace entre PRODUCTOS e INGREDIENTES */
CREATE TABLE PRODUCTS_INGREDIENTS (
    product_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    PRIMARY KEY (product_id, ingredient_id),
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE
);

/* Enlace entre PRODUCTOS SIN INGREDIENTES y ALÉRGENOS */
CREATE TABLE PRODUCTS_NO_INGREDIENTS_ALLERGENS (
    product_id INT NOT NULL,
    allergen_id INT NOT NULL,
    PRIMARY KEY (product_id, allergen_id),
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    FOREIGN KEY (allergen_id) REFERENCES ALLERGENS(allergen_id) ON DELETE CASCADE
);

/* Tabla de pedidos: Almacena los detalles de los pedidos de los clientes */
CREATE TABLE ORDERS (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_date DATE NOT NULL,
    order_status ENUM('pending', 'delivered', 'cancelled') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES CUSTOMERS(user_id) ON DELETE CASCADE
);

/* Tabla de artículos en un pedido: Almacena los productos dentro de un pedido */
CREATE TABLE ORDER_ITEMS (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(5, 2) NOT NULL,
    /* Precio en el momento del pedido (no puede cambiar) */
    FOREIGN KEY (order_id) REFERENCES ORDERS(order_id),
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id)
);

/* Enlace entre ITEMS DE PEDIDO e INGREDIENTES (modificaciones por pedido) */
CREATE TABLE ORDER_ITEMS_INGREDIENTS (
    order_item_ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    extra BOOLEAN DEFAULT FALSE,
    removed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (order_item_id) REFERENCES ORDER_ITEMS(order_item_id),
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id),
    CHECK (
        NOT (
            extra = TRUE
            AND removed = TRUE
        )
    )
);

/* Tabla de ofertas: Almacena las ofertas disponibles */
CREATE TABLE OFFERS (
    offer_id INT PRIMARY KEY AUTO_INCREMENT,
    prod_id INT NOT NULL,
    cost INT NOT NULL DEFAULT 100,
    discount DECIMAL(5, 2) NOT NULL,
    offer_text TEXT
);

/* Enlace entre CLIENTES y OFERTAS */
CREATE TABLE CUSTOMERS_OFFERS (
    user_id INT NOT NULL,
    offer_id INT NOT NULL,
    activation_date DATE NOT NULL,
    PRIMARY KEY (user_id, offer_id),
    FOREIGN KEY (user_id) REFERENCES CUSTOMERS(user_id) ON DELETE CASCADE,
    FOREIGN KEY (offer_id) REFERENCES OFFERS(offer_id) ON DELETE CASCADE
);

/* Tabla de reabastecimientos: Almacena los detalles de los reabastecimientos de ingredientes y productos por los gerentes */
CREATE TABLE REPLENISHMENTS (
    replenishment_id INT PRIMARY KEY AUTO_INCREMENT,
    manager_id INT NOT NULL,
    replenishment_date DATE NOT NULL,
    ingredient_id INT,
    product_id INT,
    quantity INT NOT NULL,
    FOREIGN KEY (manager_id) REFERENCES MANAGERS(user_id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    CHECK (
        ingredient_id IS NOT NULL
        OR product_id IS NOT NULL
    )
);

/* Tabla de transacciones: Almacena los detalles de las transacciones */
CREATE TABLE TRANSACTIONS (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    replenishment_id INT,
    transaction_money DECIMAL(5, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES ORDERS(order_id) ON DELETE CASCADE,
    FOREIGN KEY (replenishment_id) REFERENCES REPLENISHMENTS(replenishment_id) ON DELETE CASCADE,
    CHECK (
        order_id IS NOT NULL
        OR replenishment_id IS NOT NULL
    )
);