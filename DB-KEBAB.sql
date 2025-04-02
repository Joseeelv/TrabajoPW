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
    img_src VARCHAR(255),
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
        'Durum',
        'Döner',
        'Lahmacun',
        'Entrante',
        'Bebida',
        'Postre'
    ) NOT NULL,
    img_src VARCHAR(255),
    /* Solo si la categoría es 'Bebida' o 'Postre' */
    cost DECIMAL(5, 2) DEFAULT NULL,
    stock INT DEFAULT NULL
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
    quantity INT NOT NULL,
    FOREIGN KEY (order_item_id) REFERENCES ORDER_ITEMS(order_item_id),
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id),
    CHECK (quantity = 1 OR quantity = -1)
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
('admin', '$2y$10$vtJ0CcA7T.Owsybcx5tAPOrWtnyNfjvf65.v9hSC5iSL5Ly/9dR02', 'admin@donerkebab.com', 'admin', 'default.jpg'),
('user1', '$2y$10$fD2Z7brBG3z/piz6bIcP1OxY1BEuz3IylAm.57A7StxY09Ra2NUd2', 'user1@gmail.com', 'customer', 'default.jpg'),
('Manager', '$2y$10$rVVxM.uLqE/41PqQyMjvROQo/diu2TTpIkJjKSru6s0qln0baA.fq', 'manager@donerkebab.com', 'manager', 'default.jpg');
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
('Döner de pollo', 6.00, 'Döner', 'döner_pollo.png', NULL, NULL),
('Döner de ternera', 6.00, 'Döner', 'döner_ternera.png', NULL, NULL),
('Döner cordero', 6.00, 'Döner', 'döner_cordero.png', NULL, NULL),
('Döner de falafel', 6.50, 'Döner', 'döner_vegetariano.png', NULL, NULL),
('Durum de pollo', 6.00, 'Durum', 'durum_pollo.png', NULL, NULL),
('Durum de ternera', 6.00, 'Durum', 'durum_ternera.png', NULL, NULL),
('Durum cordero', 6.00, 'Durum', 'durum_cordero.png', NULL, NULL),
('Durum de falafel', 6.50, 'Durum', 'durum_vegetariano.png', NULL, NULL),
('Lahmacun de pollo', 6.00, 'Lahmacun', 'lahmacun_pollo.png', NULL, NULL),
('Lahmacun de ternera', 6.00, 'Lahmacun', 'lahmacun_ternera.png', NULL, NULL),
('Lahmacun cordero', 6.00, 'Lahmacun', 'lahmacun_cordero.png', NULL, NULL),
('Lahmacun de falafel', 6.50, 'Lahmacun', 'lahmacun_vegetariano.png', NULL, NULL),
('Patatas Fritas', 3.00, 'Entrante', 'patatas_fritas.png', NULL, NULL),
('Patatas Kebab', 3.50, 'Entrante', 'patatas_kebab.png', NULL, NULL),
('Falafel', 4.00, 'Entrante', 'falafel.png', NULL, NULL),
('Refresco Pequeño', 1.00, 'Bebida', 'refresco_pequeño.png', 0.30, 20),
('Refresco Mediano', 1.50, 'Bebida', 'refresco_mediano.png', 0.50, 20),
('Refresco Grande', 2.00, 'Bebida', 'refresco_grande.png', 0.75, 20),
('Cerveza', 1.50, 'Bebida', 'cerveza.png', 1.00, 20),
('Agua', 1.00, 'Bebida', 'agua.png', 0.20, 20),
('Baklava', 2.00, 'Postre', 'baklava.png', 1.00, 20),
('Helado', 2.00, 'Postre', 'helado.png', 1.00, 20);

-- Insertar ingredientes
INSERT INTO INGREDIENTS (ingredient_name, cost, stock, vegan, img_src) VALUES
('Pan de pita', 0.50, 100, TRUE, 'pan_de_pita.png'),
('Tortillas', 0.50, 100, TRUE, 'tortillas.png'),
('Base de lahmacun', 0.50, 100, TRUE, 'base_de_lahmacun.png'),
('Carne de pollo', 2.00, 50, FALSE, 'carne_de_pollo.png'),
('Carne de ternera', 2.50, 50, FALSE, 'carne_de_ternera.png'),
('Carne de cordero', 3.00, 50, FALSE, 'carne_de_cordero.png'),
('Falafel', 1.50, 50, TRUE, 'falafel.png'),
('Lechuga', 0.20, 100, TRUE, 'lechuga.png'),
('Tomate', 0.20, 100, TRUE, 'tomate.png'),
('Cebolla', 0.20, 100, TRUE, 'cebolla.png'),
('Pimiento', 0.20, 100, TRUE, 'pimiento.png'),
('Zanahoria', 0.20, 100, TRUE, 'zanahoria.png'),
('Pepino', 0.20, 100, TRUE, 'pepino.png'),
('Salsa de yogur', 0.50, 50, TRUE, 'salsa_de_yogur.png'),
('Salsa picante', 0.50, 50, TRUE, 'salsa_picante.png'),
('Patatas congeladas', 1.00, 100, TRUE, 'patatas_congeladas.png'),
('Aceite de oliva', 0.50, 100, TRUE, 'aceite_de_oliva.png'),
('Sal', 0.10, 100, TRUE, 'sal.png'),
('Queso', 0.50, 50, FALSE, 'queso.png');

-- Insertar alergenos
INSERT INTO ALLERGENS
(allergen_name, img_src) VALUES
('Gluten', 'gluten.png'),
('Crustáceos', 'crustaceos.png'),
('Huevos', 'huevo.png'),
('Pescado', 'pescado.png'),
('Cacahuetes', 'cacahuetes.png'),
('Soja', 'soja.png'),
('Lácteos', 'lacteos.png'),
('Frutos secos', 'frutos_cascara.png'),
('Apio', 'Apio.png'),
('Mostaza', 'mostaza.png'),
('Sésamo', 'sesamo.png'),
('Sulfitos', 'sulfitos.png'),
('Altramuz', 'altramuz.png'),
('Moluscos', 'moluscos.png');

-- Insertar ingredientes_alergenos
INSERT INTO INGREDIENTS_ALLERGENS (ingredient_id, allergen_id) VALUES
-- Pan de pita, Tortillas y Base de lahmacun contienen gluten
(1, 1),  -- Pan de pita - Gluten
(2, 1),  -- Tortillas - Gluten
(3, 1),  -- Base de lahmacun - Gluten

-- Salsa de yogur contiene lácteos
(14, 7),  -- Salsa de yogur - Lácteos
(19, 7),  -- Queso - Lácteos

-- Falafel puede contener gluten y sésamo
(7, 1),  -- Falafel - Gluten
(7, 11), -- Falafel - Sésamo

-- Salsa picante puede contener sulfitos y mostaza
(15, 12), -- Salsa picante - Sulfitos
(15, 10), -- Salsa picante - Mostaza

-- Carne de pollo, ternera, cordero y sal pueden contener sulfitos (conservantes)
(4, 12),  -- Carne de pollo - Sulfitos
(5, 12),  -- Carne de ternera - Sulfitos
(6, 12),  -- Carne de cordero - Sulfitos
(18, 12); -- Sal - Sulfitos

INSERT INTO PRODUCTS_NO_INGREDIENTS_ALLERGENS (product_id, allergen_id) VALUES
-- Cerveza contiene gluten
(19, 1), -- Cerveza - Gluten

-- Baklava contiene frutos secos y gluten
(21, 1), -- Baklava - Gluten
(21, 8), -- Baklava - Frutos secos

-- helado lacteos
(22, 7); -- Helado - Lácteos

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
(14, 16),-- Patatas congeladas
(14, 17), -- Aceite de oliva
(14, 18), -- Sal
(14, 14), -- Salsa de yogur
(14, 15), -- Salsa picante    
(14, 19), -- Queso

-- Falafel
(15, 7) -- Falafel
;

-- Insertar ofertas en la tabla OFFERS
INSERT INTO OFFERS (prod_id, cost, discount, offer_text) VALUES
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Döner de pollo'), 300, 20.00, '20% de descuento en Döner de pollo'),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Refresco Grande'), 100, 10.00, '10% de descuento en refresco grande'),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Döner de falafel'), 200, 15.00, '15% de descuento en Döner de falafel'),
((SELECT product_id FROM PRODUCTS WHERE product_name = 'Durum de pollo'), 150, 20.00, '20% de descuento en Durum de pollo');