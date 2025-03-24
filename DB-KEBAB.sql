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
    employee BOOLEAN NOT NULL DEFAULT TRUE,
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
    order_status ENUM('pendiente', 'entregado', 'cancelado') NOT NULL,
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
-- Scrip maestro de datos iniciales para la base de datos Kebab
-- Insertar usuarios

INSERT INTO USERS (username, user_secret, email, user_type, img_src) VALUES
('admin', '$2y$10$vtJ0CcA7T.Owsybcx5tAPOrWtnyNfjvf65.v9hSC5iSL5Ly/9dR02', 'admin@gmail.com', 'admin', 'default.jpg'),
('user1', '$2y$10$fD2Z7brBG3z/piz6bIcP1OxY1BEuz3IylAm.57A7StxY09Ra2NUd2', 'user1@gmail.com', 'customer', 'default.jpg'),
('Manager', '$2y$10$rVVxM.uLqE/41PqQyMjvROQo/diu2TTpIkJjKSru6s0qln0baA.fq', 'manager@gmail.com', 'manager', 'default.jpg');
-- Admin123_
-- Useruser1_
-- Managermanager1_

-- Insertar clientes
INSERT INTO CUSTOMERS (user_id, customer_address, points) VALUE
(2, 'Avenida Real 456, Ciudad B', 100);

-- Insertar managers
INSERT INTO MANAGERS (user_id, salary) VALUES
(3, 2500);

-- Insertar productos
INSERT INTO PRODUCTS (product_name, product_price, category, img_src, cost, stock) VALUES
('Döner de pollo', 6.00, 'Döner', 'döner_pollo.jpg', NULL, NULL),
('Döner de ternera', 6.00, 'Döner', 'döner_ternera.jpg', NULL, NULL),
('Döner cordero', 6.00, 'Döner', 'döner_mixto.jpg', NULL, NULL),
('Döner de falafel', 6.50, 'Döner', 'döner_vegetariano.jpg', NULL, NULL),
('Durum de pollo', 6.00, 'Durum', 'durum_pollo.jpg', NULL, NULL),
('Durum de ternera', 6.00, 'Durum', 'durum_ternera.jpg', NULL, NULL),
('Durum cordero', 6.00, 'Durum', 'durum_mixto.jpg', NULL, NULL),
('Durum de falafel', 6.50, 'Durum', 'durum_vegetariano.jpg', NULL, NULL),
('Lahmacun de pollo', 6.00, 'Lahmacun', 'lahmacun_pollo.jpg', NULL, NULL),
('Lahmacun de ternera', 6.00, 'Lahmacun', 'lahmacun_ternera.jpg', NULL, NULL),
('Lahmacun cordero', 6.00, 'Lahmacun', 'lahmacun_mixto.jpg', NULL, NULL),
('Lahmacun de falafel', 6.50, 'Lahmacun', 'lahmacun_vegetariano.jpg', NULL, NULL),
('Patatas Fritas', 3.00, 'Starter', 'patatas_fritas.jpg', NULL, NULL),
('Patatas Kebab', 3.50, 'Starter', 'patatas_kebab.jpg', NULL, NULL),
('Falafel', 4.00, 'Starter', 'falafel.jpg', NULL, NULL),
('Refresco Pequeño', 1.00, 'Drink', 'refresco_pequeño.jpg', 0.30, 20),
('Refresco Mediano', 1.50, 'Drink', 'refresco_mediano.jpg', 0.50, 20),
('Refresco Grande', 2.00, 'Drink', 'refresco_grande.jpg', 0.75, 20),
('Cerveza', 1.50, 'Drink', 'cerveza.jpg', 1.00, 20),
('Agua', 1.00, 'Drink', 'agua.jpg', 0.20, 20),
('Baklava', 2.00, 'Dessert', 'baklava.jpg', 1.00, 20),
('Helado', 2.00, 'Dessert', 'helado.jpg', 1.00, 20);

-- Insertar ingredientes
INSERT INTO INGREDIENTS (ingredient_name, cost, stock, vegan) VALUES
('Pan de pita', 0.50, 100, TRUE),
('Tortillas', 0.50, 100, TRUE),
('Base de lahmacun', 0.50, 100, TRUE),
('Carne de pollo', 2.00, 50, FALSE),
('Carne de ternera', 2.50, 50, FALSE),
('Carne de cordero', 3.00, 50, FALSE),
('Falafel', 1.50, 50, TRUE),
('Lechuga', 0.20, 100, TRUE),
('Tomate', 0.20, 100, TRUE),
('Cebolla', 0.20, 100, TRUE),
('Pimiento', 0.20, 100, TRUE),
('Zanahoria', 0.20, 100, TRUE),
('Pepino', 0.20, 100, TRUE),
('Salsa de yogur', 0.50, 50, TRUE),
('Salsa picante', 0.50, 50, TRUE),
('Patatas congeladas', 1.00, 100, TRUE),
('Aceite de oliva', 0.50, 100, TRUE),
('Sal', 0.10, 100, TRUE);

-- Insertar alergenos
INSERT INTO ALLERGENS
(allergen_name, img_src) VALUES
('Gluten', 'gluten.jpg'),
('Crustáceos', 'crustaceos.jpg'),
('Huevos', 'huevo.jpg'),
('Pescado', 'pescado.jpg'),
('Cacahuetes', 'cacahuetes.jpg'),
('Soja', 'soja.jpg'),
('Lácteos', 'lacteos.jpg'),
('Frutos secos', 'frutos_cascara.jpg'),
('Apio', 'Apio.jpg'),
('Mostaza', 'mostaza.jpg'),
('Sésamo', 'sesamo.jpg'),
('Sulfitos', 'sulfitos.jpg'),
('Altramuz', 'altramuz.jpg'),
('Moluscos', 'moluscos.jpg');

-- Insertar ingredientes_alergenos
INSERT INTO INGREDIENTS_ALLERGENS (ingredient_id, allergen_id) VALUES
-- Pan de pita, Tortillas y Base de lahmacun contienen gluten
(1, 1),  -- Pan de pita - Gluten
(2, 1),  -- Tortillas - Gluten
(3, 1),  -- Base de lahmacun - Gluten

-- Salsa de yogur contiene lácteos
(14, 7),  -- Salsa de yogur - Lácteos

-- Falafel puede contener gluten y sésamo
(7, 1),  -- Falafel - Gluten
(7, 11), -- Falafel - Sésamo

-- Salsa picante puede contener sulfitos y mostaza
(15, 12), -- Salsa picante - Sulfitos
(15, 10), -- Salsa picante - Mostaza

-- Carne de pollo, ternera y cordero pueden contener sulfitos (conservantes)
(4, 12),  -- Carne de pollo - Sulfitos
(5, 12),  -- Carne de ternera - Sulfitos
(6, 12);  -- Carne de cordero - Sulfitos

INSERT INTO PRODUCTS_NO_INGREDIENTS_ALLERGENS (product_id, allergen_id) VALUES
-- Cerveza contiene gluten
(5, 1),  -- Cerveza - Gluten

-- Baklava contiene gluten y frutos secos
(7, 1),  -- Baklava - Gluten
(7, 8),  -- Baklava - Frutos secos

-- Helado puede contener lácteos y frutos secos (según el tipo)
(8, 7),  -- Helado - Lácteos
(8, 8)   -- Helado - Frutos secos
;

-- Insertar productos_ingredientes
INSERT INTO PRODUCTS_INGREDIENTS (product_id, ingredient_id) VALUES
-- Döner de pollo
(1, 1),  -- Pan de pita
(1, 4),  -- Carne de pollo
(1, 8),  -- Lechuga
(1, 9),  -- Tomate
(1, 10), -- Cebolla
(1, 14), -- Salsa de yogur
(1, 15), -- Salsa picante

-- Döner de ternera
(2, 1),
(2, 5),
(2, 8),
(2, 9),
(2, 10),
(2, 14),
(2, 15),

-- Döner cordero
(3, 1),
(3, 6),
(3, 8),
(3, 9),
(3, 10),
(3, 14),
(3, 15),

-- Döner de falafel
(4, 1),
(4, 7),
(4, 8),
(4, 9),
(4, 10),
(4, 14),
(4, 15),

-- Durum de pollo
(5, 2),
(5, 4),
(5, 8),
(5, 9),
(5, 10),
(5, 14),
(5, 15),

-- Durum de ternera
(6, 2),
(6, 5),
(6, 8),
(6, 9),
(6, 10),
(6, 14),
(6, 15),

-- Durum cordero
(7, 2),
(7, 6),
(7, 8),
(7, 9),
(7, 10),
(7, 14),
(7, 15),

-- Durum de falafel
(8, 2),
(8, 7),
(8, 8),
(8, 9),
(8, 10),
(8, 14),
(8, 15),

-- Lahmacun de pollo
(9, 3),
(9, 4),
(9, 8),
(9, 9),
(9, 10),
(9, 14),
(9, 15),

-- Lahmacun de ternera
(10, 3),
(10, 5),
(10, 8),
(10, 9),
(10, 10),
(10, 14),
(10, 15),

-- Lahmacun cordero
(11, 3),
(11, 6),
(11, 8),
(11, 9),
(11, 10),
(11, 14),
(11, 15),

-- Lahmacun de falafel
(12, 3),
(12, 7),
(12, 8),
(12, 9),
(12, 10),
(12, 14),
(12, 15),

-- Patatas Fritas
(13, 16), -- Patatas congeladas
(13, 17), -- Aceite de oliva
(13, 18), -- Sal

-- Patatas Kebab
(14, 16), -- Patatas congeladas
(14, 17), -- Aceite de oliva
(14, 18), -- Sal
(14, 14), -- Salsa de yogur
(14, 15), -- Salsa picante

-- Falafel
(15, 7) -- Falafel
;

-- Insertar menús en la tabla PRODUCTS
INSERT INTO PRODUCTS (product_name, product_price, category, img_src, cost, stock) VALUES
('Menú Familiar', 20.00, 'Menu', 'menu_familiar.jpg', NULL, NULL),
('Menú Pareja', 12.00, 'Menu', 'menu_pareja.jpg', NULL, NULL),
('Menú 3x2 Döner', 15.00, 'Menu', 'menu_3x2_doner.jpg', NULL, NULL),
('Menú Ahorro', 10.00, 'Menu', 'menu_ahorro.jpg', NULL, NULL);

-- Menú Familiar: 4 Döner (2 pollo, 2 ternera), 2 patatas fritas, 4 refrescos medianos
INSERT INTO MENUS_CONTENTS (menu_product_id, product_id, quantity) VALUES
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú Familiar'), 
 (SELECT product_id FROM PRODUCTS WHERE product_name = 'Döner de pollo'), 2),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú Familiar'), 
 (SELECT product_id FROM PRODUCTS WHERE product_name = 'Döner de ternera'), 2),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú Familiar'), 
 (SELECT product_id FROM PRODUCTS WHERE product_name = 'Patatas Fritas'), 2),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú Familiar'), 
 (SELECT product_id FROM PRODUCTS WHERE product_name = 'Refresco Mediano'), 4);

-- Menú Pareja: 2 Durum (1 pollo, 1 ternera), 1 patatas fritas, 2 refrescos medianos
INSERT INTO MENUS_CONTENTS (menu_product_id, product_id, quantity) VALUES
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú Pareja'), 
 (SELECT product_id FROM PRODUCTS WHERE product_name = 'Durum de pollo'), 1),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú Pareja'), 
 (SELECT product_id FROM PRODUCTS WHERE product_name = 'Durum de ternera'), 1),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú Pareja'), 
 (SELECT product_id FROM PRODUCTS WHERE product_name = 'Patatas Fritas'), 1),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú Pareja'), 
 (SELECT product_id FROM PRODUCTS WHERE product_name = 'Refresco Mediano'), 2);

-- Menú 3x2 Döner: 3 Döner (pueden ser diferentes), 2 refrescos grandes
INSERT INTO MENUS_CONTENTS (menu_product_id, product_id, quantity) VALUES
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú 3x2 Döner'), 
 (SELECT product_id FROM PRODUCTS WHERE product_name = 'Döner de pollo'), 1),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú 3x2 Döner'), 
 (SELECT product_id FROM PRODUCTS WHERE product_name = 'Döner de ternera'), 1),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú 3x2 Döner'), 
 (SELECT product_id FROM PRODUCTS WHERE product_name = 'Döner cordero'), 1),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú 3x2 Döner'), 
 (SELECT product_id FROM PRODUCTS WHERE product_name = 'Refresco Grande'), 2);

-- Menú Ahorro: 1 Lahmacun de pollo, 1 patatas fritas, 1 refresco pequeño
INSERT INTO MENUS_CONTENTS (menu_product_id, product_id, quantity) VALUES
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú Ahorro'), 
 (SELECT product_id FROM PRODUCTS WHERE product_name = 'Lahmacun de pollo'), 1),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú Ahorro'), 
 (SELECT product_id FROM PRODUCTS WHERE product_name = 'Patatas Fritas'), 1),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú Ahorro'), 
 (SELECT product_id FROM PRODUCTS WHERE product_name = 'Refresco Pequeño'), 1);

-- Insertar ofertas en la tabla OFFERS
INSERT INTO OFFERS (prod_id, cost, discount, offer_text) VALUES
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Döner de pollo'), 300, 20.00, '¡20% de descuento en Döner de pollo esta semana!'),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú Familiar'), 500, 15.00, 'Menú Familiar con un 15% de descuento por tiempo limitado.'),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Refresco Grande'), 100, 10.00, 'Refresco Grande con 10% de descuento al pedir cualquier menú.'),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Durum de ternera'), 50, 25.00, '¡OFERTA FLASH! 25% de descuento en Durum de ternera solo hoy.'),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Menú 3x2 Döner'), 1000, 18.00, 'Pide el Menú 3x2 Döner y ahorra un 18% en tu compra.'),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Lahmacun de pollo'), 200, 30.00, 'Lahmacun de pollo con un 30% de descuento esta semana.');
