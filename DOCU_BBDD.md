# Documentación de la BBDD

Se describe a continuación la BBDD utilizada en el proyecto:

![Diagrama de la BBDD](/images/DB-KEBAB.jpg)

## Tabla USERS
```sql
CREATE TABLE USERS (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(30) NOT NULL UNIQUE,
    user_secret VARCHAR(100) NOT NULL,
    email VARCHAR(30) NOT NULL UNIQUE,
    img_src VARCHAR(255) NOT NULL DEFAULT 'default.jpg'
);
```
En esta tabla se almacenan los datos de los usuarios registrados en la página web.

- **user_id**: Identificador único del usuario.
- **username**: Nombre de usuario.
- **user_secret**: Contraseña del usuario.
- **email**: Correo electrónico del usuario.
- **img_src**: Ruta de la imagen de perfil del usuario.

Los usuarios pueden clasificarse en dos tipos: **clientes** (almacenados en la tabla `CUSTOMERS`) y **managers** (almacenados en la tabla `MANAGERS`).

## Tabla CUSTOMERS
```sql
CREATE TABLE CUSTOMERS (
    user_id INT PRIMARY KEY,
    customer_address VARCHAR(255) NOT NULL,
    points INT NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id) ON DELETE CASCADE
);
```
En esta tabla se almacenan los datos de los usuarios que son clientes.

- **user_id**: Identificador único del usuario, clave foránea de la tabla USERS.
- **customer_address**: Dirección del cliente.
- **points**: Puntos acumulados por el cliente.

## Tabla MANAGERS
```sql
CREATE TABLE MANAGERS (
    user_id INT PRIMARY KEY,
    salary INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id) ON DELETE CASCADE
);
```
En esta tabla se almacenan los datos de los usuarios que son managers.

- **user_id**: Identificador único del usuario, clave foránea de la tabla USERS.
- **salary**: Salario del manager.

## Tabla INGREDIENTS
```sql
CREATE TABLE INGREDIENTS (
    ingredient_id INT PRIMARY KEY AUTO_INCREMENT,
    ingredient_name VARCHAR(30) NOT NULL UNIQUE,
    cost DECIMAL(5, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    vegan BOOLEAN NOT NULL DEFAULT FALSE
);
```
En esta tabla se almacenan los ingredientes que se utilizan en los productos personalizables.

- **ingredient_id**: Identificador único del ingrediente.
- **ingredient_name**: Nombre del ingrediente.
- **cost**: Coste del ingrediente en euros.
- **stock**: Stock del ingrediente (en unidades).
- **vegan**: Indica si el ingrediente es vegano.

## Tabla ALLERGENS
```sql
CREATE TABLE ALLERGENS (
    allergen_id INT PRIMARY KEY AUTO_INCREMENT,
    allergen_name VARCHAR(30) NOT NULL UNIQUE,
    img_src VARCHAR(255)
);
```
En esta tabla se almacenan los alérgenos que pueden contener los productos.

- **allergen_id**: Identificador único del alérgeno.
- **allergen_name**: Nombre del alérgeno.
- **img_src**: Ruta de la imagen del alérgeno.

## Tabla INGREDIENTS_ALLERGENS
```sql
CREATE TABLE INGREDIENTS_ALLERGENS (
    ingredient_id INT NOT NULL,
    allergen_id INT NOT NULL,
    PRIMARY KEY (ingredient_id, allergen_id),
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE,
    FOREIGN KEY (allergen_id) REFERENCES ALLERGENS(allergen_id) ON DELETE CASCADE
);
```
Relaciona ingredientes con sus alérgenos.

## Tabla PRODUCTS
```sql
CREATE TABLE PRODUCTS (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(30) NOT NULL UNIQUE,
    product_price DECIMAL(5, 2) NOT NULL,
    category ENUM(
        'Menu', 'Durum', 'Döner', 'Lahmacun', 'Starter', 'Drink', 'Dessert'
    ) NOT NULL,
    img_src VARCHAR(255),
    cost DECIMAL(5, 2) DEFAULT NULL,
    stock INT DEFAULT NULL
);
```
Contiene los productos que se pueden comprar.

- **cost** y **stock** solo aplican a productos no personalizables.

## Tabla MENUS_CONTENTS
```sql
CREATE TABLE MENUS_CONTENTS (
    menu_product_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    PRIMARY KEY (menu_product_id, product_id),
    FOREIGN KEY (menu_product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    CHECK (menu_product_id != product_id)
);
```
Relaciona menús con los productos que contienen.

## Tabla PRODUCTS_INGREDIENTS
```sql
CREATE TABLE PRODUCTS_INGREDIENTS (
    product_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    PRIMARY KEY (product_id, ingredient_id),
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE
);
```
Relaciona productos personalizables con sus ingredientes.

## Tabla PRODUCTS_EXTRA_ALLERGENS
```sql
CREATE TABLE PRODUCTS_EXTRA_ALLERGENS (
    product_id INT NOT NULL,
    allergen_id INT NOT NULL,
    PRIMARY KEY (product_id, allergen_id),
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    FOREIGN KEY (allergen_id) REFERENCES ALLERGENS(allergen_id) ON DELETE CASCADE
);
```
Para productos sin ingredientes pero con alérgenos manuales (bebidas, etc.).

## Tabla ORDERS
```sql
CREATE TABLE ORDERS (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_date DATE NOT NULL,
    order_status ENUM('pending', 'delivered', 'cancelled') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES CUSTOMERS(user_id) ON DELETE CASCADE
);
```
Representa los pedidos realizados por clientes.

## Tabla ORDER_ITEMS
```sql
CREATE TABLE ORDER_ITEMS (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(5, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES ORDERS(order_id),
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id)
);
```
Contiene los productos de cada pedido.

## Tabla ORDER_ITEMS_INGREDIENTS
```sql
CREATE TABLE ORDER_ITEMS_INGREDIENTS (
    order_item_ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    extra BOOLEAN DEFAULT FALSE,
    removed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (order_item_id) REFERENCES ORDER_ITEMS(order_item_id),
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id),
    CHECK (NOT (extra = TRUE AND removed = TRUE))
);
```
Almacena los ingredientes personalizados de un producto pedido.

## Tabla OFFERS
```sql
CREATE TABLE OFFERS(
    offer_id INT PRIMARY KEY AUTO_INCREMENT,
    prod_id INT NOT NULL,
    points_required INT NOT NULL DEFAULT 100,
    discount DECIMAL(5, 2) NOT NULL,
    offer_text TEXT,
    FOREIGN KEY (prod_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE
);
```
Contiene las ofertas disponibles.

- **points_required**: Puntos necesarios para activarla.

## Tabla CUSTOMERS_OFFERS
```sql
CREATE TABLE CUSTOMERS_OFFERS (
    user_id INT NOT NULL,
    offer_id INT NOT NULL,
    activation_date DATE NOT NULL,
    PRIMARY KEY (user_id, offer_id),
    FOREIGN KEY (user_id) REFERENCES CUSTOMERS(user_id) ON DELETE CASCADE,
    FOREIGN KEY (offer_id) REFERENCES OFFERS(offer_id) ON DELETE CASCADE
);
```
Relaciones entre clientes y ofertas activadas.

## Tabla REPLENISHMENTS
```sql
CREATE TABLE REPLENISHMENTS (
    replenishment_id INT PRIMARY KEY AUTO_INCREMENT,
    manager_id INT NOT NULL,
    replenishment_date DATE NOT NULL,
    FOREIGN KEY (manager_id) REFERENCES MANAGERS(user_id) ON DELETE CASCADE
);
```
Registra los reabastecimientos realizados por managers.

## Tabla REPLENISHMENTS_DETAILS
```sql
CREATE TABLE REPLENISHMENTS_DETAILS (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    replenishment_id INT NOT NULL,
    ingredient_id INT,
    prod_id INT,
    quantity INT NOT NULL,
    FOREIGN KEY (replenishment_id) REFERENCES REPLENISHMENTS(replenishment_id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE,
    FOREIGN KEY (prod_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    CHECK (ingredient_id IS NOT NULL OR prod_id IS NOT NULL)
);
```
Detalles de los productos/ingredientes reabastecidos.

## Tabla TRANSACTIONS
```sql
CREATE TABLE TRANSACTIONS (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    replenishment_id INT,
    transaction_money DECIMAL(5, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES ORDERS(order_id) ON DELETE CASCADE,
    FOREIGN KEY (replenishment_id) REFERENCES REPLENISHMENTS(replenishment_id) ON DELETE CASCADE,
    CHECK (order_id IS NOT NULL OR replenishment_id IS NOT NULL)
);
```
Registra transacciones monetarias de pedidos o reabastecimientos.

