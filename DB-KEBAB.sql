DROP DATABASE IF EXISTS DB_KEBAB;

CREATE DATABASE DB_KEBAB;

USE DB_KEBAB;

/* General users table: Stores general user information in the system */
CREATE TABLE USERS (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(30) NOT NULL UNIQUE,
    user_secret VARCHAR(100) NOT NULL,
    email VARCHAR(30) NOT NULL UNIQUE,
    img_src VARCHAR(255) NOT NULL DEFAULT 'default.jpg'
);

/* Customers users table: Stores additional information for customers */
CREATE TABLE CUSTOMERS (
    user_id INT PRIMARY KEY,
    customer_address VARCHAR(255) NOT NULL,
    points INT NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id) ON DELETE CASCADE
);

/* Managers users table: Stores additional information for managers */
CREATE TABLE MANAGERS (
    user_id INT PRIMARY KEY,
    salary INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id) ON DELETE CASCADE
);

/* Reviews table: Stores customer reviews and manager responses */
-- CREATE TABLE REVIEWS (
--     review_id INT PRIMARY KEY AUTO_INCREMENT,
--     user_id INT NOT NULL,
--     review_date DATE NOT NULL,
--     review_text TEXT NOT NULL,
--     rating INT NOT NULL CHECK (
--         rating BETWEEN 1
--         AND 5
--     ),
--     manager_id INT DEFAULT NULL,
--     -- NULL if no response yet
--     answer TEXT DEFAULT NULL,
--     -- NULL if no response yet
--     FOREIGN KEY (user_id) REFERENCES CUSTOMERS(user_id) ON DELETE CASCADE,
--     FOREIGN KEY (manager_id) REFERENCES MANAGERS(user_id) ON DELETE CASCADE,
--     CHECK (
--         answer IS NOT NULL
--         OR manager_id IS NULL
--     ) -- If there is an answer, there must be a manager
-- );
/* Ingredients table: Stores ingredients available for products */
CREATE TABLE INGREDIENTS (
    ingredient_id INT PRIMARY KEY AUTO_INCREMENT,
    ingredient_name VARCHAR(30) NOT NULL UNIQUE,
    cost DECIMAL(5, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    vegan BOOLEAN NOT NULL DEFAULT FALSE
);

/* Allergens table: Stores allergen information */
CREATE TABLE ALLERGENS (
    allergen_id INT PRIMARY KEY AUTO_INCREMENT,
    allergen_name VARCHAR(30) NOT NULL UNIQUE,
    img_src VARCHAR(255)
);

/* Links: INGREDIENTS - ALLERGENS */
CREATE TABLE INGREDIENTS_ALLERGENS (
    ingredient_id INT NOT NULL,
    allergen_id INT NOT NULL,
    PRIMARY KEY (ingredient_id, allergen_id),
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE,
    FOREIGN KEY (allergen_id) REFERENCES ALLERGENS(allergen_id) ON DELETE CASCADE
);

/* Products table: Stores menu items available for order */
CREATE TABLE PRODUCTS (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(30) NOT NULL UNIQUE,
    product_price DECIMAL(5, 2) NOT NULL,
    -- Price at the time of creation (can change)
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
    -- Only if category = 'Drink' or 'Dessert':
    cost DECIMAL(5, 2) DEFAULT NULL,
    stock INT DEFAULT NULL
);

/* Links: PRODUCTS (Menus) - PRODUCTS (Items inside the menu) */
CREATE TABLE MENUS_CONTENTS (
    menu_product_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    PRIMARY KEY (menu_product_id, product_id),
    FOREIGN KEY (menu_product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    -- The menu product must be a menu   
    CHECK (menu_product_id != product_id) -- A menu can't contain itself
);

/* Links: PRODUCTS - INGREDIENTS */
CREATE TABLE PRODUCTS_INGREDIENTS (
    product_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    PRIMARY KEY (product_id, ingredient_id),
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE
);

/* Links: PRODUCTS_NO_INGREDIENTS - ALLERGENS */
CREATE TABLE PRODUCTS_NO_INGREDIENTS_ALLERGENS (
    product_id INT NOT NULL,
    allergen_id INT NOT NULL,
    PRIMARY KEY (product_id, allergen_id),
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    FOREIGN KEY (allergen_id) REFERENCES ALLERGENS(allergen_id) ON DELETE CASCADE
);

/* Orders table: Stores customer order details */
CREATE TABLE ORDERS (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_date DATE NOT NULL,
    order_status ENUM('pending', 'delivered', 'cancelled') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES CUSTOMERS(user_id) ON DELETE CASCADE
);

/* Order items table: Stores individual items within an order */
CREATE TABLE ORDER_ITEMS (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(5, 2) NOT NULL,
    -- Price at the time of the order (cant change)
    FOREIGN KEY (order_id) REFERENCES ORDERS(order_id),
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id)
);

/* Links: ORDER_ITEMS - INGREDIENTS (modifications per order) */
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

/* Offers table: Stores available offers */
CREATE TABLE OFFERS(
    offer_id INT PRIMARY KEY AUTO_INCREMENT,
    prod_id INT NOT NULL,
    cost INT NOT NULL DEFAULT 100,
    discount DECIMAL(5, 2) NOT NULL,
    offer_text TEXT 
);

/* Links: CUSTOMERS - OFFERS */
CREATE TABLE CUSTOMERS_OFFERS (
    user_id INT NOT NULL,
    offer_id INT NOT NULL,
    activation_date DATE NOT NULL,
    PRIMARY KEY (user_id, offer_id),
    FOREIGN KEY (user_id) REFERENCES CUSTOMERS(user_id) ON DELETE CASCADE,
    FOREIGN KEY (offer_id) REFERENCES OFFERS(offer_id) ON DELETE CASCADE
);

/* Replenishments table: Stores manager replenishment details */
CREATE TABLE REPLENISHMENTS (
    replenishment_id INT PRIMARY KEY AUTO_INCREMENT,
    manager_id INT NOT NULL,
    replenishment_date DATE NOT NULL,
    FOREIGN KEY (manager_id) REFERENCES MANAGERS(user_id) ON DELETE CASCADE
);

/* Replenishments details table: Stores individual items within a replenishment */
CREATE TABLE REPLENISHMENTS_DETAILS (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    replenishment_id INT NOT NULL,
    ingredient_id INT ,
    prod_id INT,
    quantity INT NOT NULL,
    FOREIGN KEY (replenishment_id) REFERENCES REPLENISHMENTS(replenishment_id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE,
    FOREIGN KEY (prod_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    CHECK (
        ingredient_id IS NOT NULL
        OR prod_id IS NOT NULL
    )
);

/* Transactions table: Stores transaction details */
CREATE TABLE TRANSACTIONS (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    replenishment_id INT NOT NULL,
    transaction_money DECIMAL(5, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES ORDERS(order_id) ON DELETE CASCADE,
    FOREIGN KEY (replenishment_id) REFERENCES REPLENISHMENTS(replenishment_id) ON DELETE CASCADE,
    CHECK (
        order_id IS NOT NULL
        OR replenishment_id IS NOT NULL
    )
);