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
En esta tabla se almacenan los datos de los usuarios registrados en la pagina web.

- **user_id**: Identificador único del usuario.
- **username**: Nombre de usuario.
- **user_secret**: Contraseña del usuario.
- **email**: Correo electrónico del usuario.
- **img_src**: Ruta de la imagen de perfil del usuario.

De esta tabla se diferencian dos tipos de usuarios:

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
En esta tabla se almacenan los ingredientes que se utilizan en los productos personalizables, es decir, los que se pueden añadir o quitar infredientes.

- **ingredient_id**: Identificador único del ingrediente.
- **ingredient_name**: Nombre del ingrediente.
- **cost**: Coste del ingrediente en euros.
- **stock**: Stock del ingrediente. Se toma como stock inicial 0, y se va actualizando con las compras y ventas. Se mide en unidades. Por ejemplo: 5 unidades de tomate.
- **vegan**: Indica si el ingrediente es vegano.

## Tabla ALLEGENS
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

En esta tabla se almacenan las relaciones entre los ingredientes y los alérgenos.

- **ingredient_id**: Identificador único del ingrediente, clave foránea de la tabla INGREDIENTS.
- **allergen_id**: Identificador único del alérgeno, clave foránea de la tabla ALLERGENS.

## Tabla PRODUCTS
```sql
CREATE TABLE PRODUCTS (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(30) NOT NULL UNIQUE,
    product_price DECIMAL(5, 2) NOT NULL,
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
    cost DECIMAL(5, 2) DEFAULT NULL,
    stock INT DEFAULT NULL
);
```

En esta tabla se almacenan los productos que se pueden comprar en la página web.

- **product_id**: Identificador único del producto.
- **product_name**: Nombre del producto.
- **product_price**: Precio del producto en euros.
- **category**: Categoría del producto. Las categorías posibles son: 'Menu', 'Durum', 'Döner', 'Lahmacun', 'Starter', 'Drink' y 'Dessert'.
- **img_src**: Ruta de la imagen del producto.
- **cost**: Coste del producto en euros. Solo se aplicará a los productos NO personalizables, es decir, drinks y dessert. 
- **stock**: Stock del producto. Se toma como stock inicial 0, y se va actualizando con las compras y ventas. Se mide en unidades. Solo se aplicará a los productos NO personalizables, es decir, drinks y dessert. 

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
En esta tabla se almacenan las relaciones entre los productos que forman parte de un menú.

- **menu_product_id**: Identificador único del menú, clave foránea de la tabla PRODUCTS.
- **product_id**: Identificador único del producto, clave foránea de la tabla PRODUCTS. No puede ser igual al menu_product_id.
- **quantity**: Cantidad de unidades del producto que forman parte del menú.


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

En esta tabla se almacenan las relaciones entre los productos y los ingredientes que los componen.

- **product_id**: Identificador único del producto, clave foránea de la tabla PRODUCTS.
- **ingredient_id**: Identificador único del ingrediente, clave foránea de la tabla INGREDIENTS.

## Tabla PRODUCTS_NO_INGREDIENTS_ALLERGENS
```sql
CREATE TABLE PRODUCTS_NO_INGREDIENTS_ALLERGENS (
    product_id INT NOT NULL,
    allergen_id INT NOT NULL,
    PRIMARY KEY (product_id, allergen_id),
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    FOREIGN KEY (allergen_id) REFERENCES ALLERGENS(allergen_id) ON DELETE CASCADE
);
```

En esta tabla se almacenan las relaciones entre los productos que no contienen ingredientes con alérgenos.

- **product_id**: Identificador único del producto, clave foránea de la tabla PRODUCTS.
- **allergen_id**: Identificador único del alérgeno, clave foránea de la tabla ALLERGENS.

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

En esta tabla se almacenan los pedidos realizados por los clientes.

- **order_id**: Identificador único del pedido.
- **user_id**: Identificador único del cliente, clave foránea de la tabla CUSTOMERS.
- **order_date**: Fecha en la que se realizó el pedido.
- **order_status**: Estado del pedido. Los estados posibles son: 'pending', 'delivered' y 'cancelled'.


## Tabla ORDER_ITEMS
```sql
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
```

En esta tabla se almacenan los productos que forman parte de un pedido.

- **order_item_id**: Identificador único del producto en el pedido.
- **order_id**: Identificador único del pedido, clave foránea de la tabla ORDERS.
- **product_id**: Identificador único del producto, clave foránea de la tabla PRODUCTS.
- **quantity**: Cantidad de unidades del producto en el pedido.
- **price**: Precio del producto en el momento de realizar el pedido.

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
    CHECK (
        NOT (
            extra = TRUE
            AND removed = TRUE
        )
    )
);
```

En esta tabla se almacenan los ingredientes extra y eliminados de los productos que forman parte de un pedido.

- **order_item_ingredient_id**: Identificador único del ingrediente en el pedido.
- **order_item_id**: Identificador único del producto en el pedido, clave foránea de la tabla ORDER_ITEMS.
- **ingredient_id**: Identificador único del ingrediente, clave foránea de la tabla INGREDIENTS.
- **extra**: Indica si el ingrediente es extra.
- **removed**: Indica si el ingrediente ha sido eliminado.

## Tabla OFFERS
```sql
CREATE TABLE OFFERS(
    offer_id INT PRIMARY KEY AUTO_INCREMENT,
    prod_id INT NOT NULL,
    cost INT NOT NULL DEFAULT 100,
    discount DECIMAL(5, 2) NOT NULL,
    offer_text TEXT 
);

```
En esta tabla se almacenan las ofertas que se pueden aplicar a los productos.

- **offer_id**: Identificador único de la oferta.
- **prod_id**: Identificador único del producto, clave foránea de la tabla PRODUCTS.
- **cost**: Coste del producto en euros.
- **discount**: Descuento de la oferta en euros.
- **offer_text**: Descripción de la oferta.

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

En esta tabla se almacenan las relaciones entre los clientes y las ofertas que han activado.

- **user_id**: Identificador único del cliente, clave foránea de la tabla CUSTOMERS.
- **offer_id**: Identificador único de la oferta, clave foránea de la tabla OFFERS.
- **activation_date**: Fecha en la que se activó la oferta.

## Tabla REPLENISHMENTS
```sql
CREATE TABLE REPLENISHMENTS (
    replenishment_id INT PRIMARY KEY AUTO_INCREMENT,
    manager_id INT NOT NULL,
    replenishment_date DATE NOT NULL,
    ingredient_id INT,
    prod_id INT,
    quantity INT NOT NULL,
    FOREIGN KEY (manager_id) REFERENCES MANAGERS(user_id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES INGREDIENTS(ingredient_id) ON DELETE CASCADE,
    FOREIGN KEY (prod_id) REFERENCES PRODUCTS(product_id) ON DELETE CASCADE,
    CHECK (
        ingredient_id IS NOT NULL
        OR prod_id IS NOT NULL
    )
);
```

En esta tabla se almacenan los reabastecimientos de stock realizados por los managers.

- **replenishment_id**: Identificador único del reabastecimiento.
- **manager_id**: Identificador único del manager, clave foránea de la tabla MANAGERS.
ánea de la tabla MANAGERS.
- **replenishment_date**: Fecha en la que se realizó el reabastecimiento.
- **ingredient_id**: Identificador único del ingrediente, clave foránea de la tabla INGREDIENTS.
- **prod_id**: Identificador único del producto, clave foránea de la tabla PRODUCTS.
- **quantity**: Cantidad de unidades reabastecidas.

## Tabla TRANSACTIONS
```sql
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
```

En esta tabla se almacenan las transacciones realizadas por los clientes y los managers.

- **transaction_id**: Identificador único de la transacción.
- **order_id**: Identificador único del pedido, clave foránea de la tabla ORDERS.
- **replenishment_id**: Identificador único del reabastecimiento, clave foránea de la tabla REPLENISHMENTS.
- **transaction_money**: Cantidad de dinero de la transacción.