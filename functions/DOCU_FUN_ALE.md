# Funciones realizadas por Ale

## replenishment.php

Archivo que se encarga de pedir los ingredientes de un producto y los productos que tengan stock a la base de datos.

### Precondiciones

- Se debe haber iniciado sesión como manager.

```php
$_SESSION['user_id'] // debe ser manager
```

- Se debe haber enviado un POST con el id del ingrediente o de producto, su cantidad y su coste.

```php
$_POST['ingredient_id']
$_POST['product_id']
$_POST['quantity']
$_POST['cost']
```

### Postcondiciones

- Crea la reposición en la tabla 
```sql
INSERT INTO REPLENISHMENTS (manager_id, replenishment_date, ingredient_id/product_id, quantity) VALUES ($manager_id, NOW(), $ingredient_id/$product_id, $quantity)
```

- Añade la transaccion a la tabla TRANSACTIONS

```sql
$total_cost = $quantity * $cost;

INSERT INTO TRANSACTIONS (order_id, replenishment_id, transaction_money)
VALUES (NULL, $replenishment_id, $total_cost)
```

- Actualiza el stock de la tabla INGREDIENTS o PRODUCTS

```sql
UPDATE INGREDIENTS SET stock = stock + $_POST['quantity'] WHERE ingredient_id = $_POST['ingredient_id']
```
```sql
UPDATE PRODUCTS SET stock = stock + $_POST['quantity'] WHERE product_id = $_POST['product_id']
```
- Te redirige a manager.php

