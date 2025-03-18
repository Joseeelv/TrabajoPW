# Funciones realizadas por Ale

## order_ingredients.php

Archivo que se encarga de pedir los ingredientes de un producto a la base de datos.

### Precondiciones

- Se debe haber iniciado sesión como manager.

```php
$_SESSION['user_id'] // debe ser manager
```

- Se debe haber enviado un POST con el id del ingrediente y su cantidad.

```php
$_POST['ingredient_id']
$_POST['quantity']
```

### Postcondiciones

- Crea la reposición en la tabla REPLENISHMENTS

```sql
INSERT INTO REPLENISHMENTS (manager_id, replenishment_date) VALUES ($_SESSION['user_id'], NOW())
-- Guardamos el id de la reposición en $replenishment_id
```

- Crea la reposición en la tabla REPLENISHMENTS_DETAILS

```sql
INSERT INTO REPLENISHMENTS_DETAILS (replenishment_id, ingredient_id, quantity) VALUES ($replenishment_id;, $_POST['ingredient_id'], $_POST['quantity'])
```

- Añade la transaccion a la tabla TRANSACTIONS

```sql
INSERT INTO TRANSACTIONS (order_id, replenishment_id, transaction_money)
VALUES (NULL, $replenishment_id, (SELECT cost * $_POST['quantity'] FROM INGREDIENTS WHERE ingredient_id = $_POST['ingredient_id']))
```

- Actualiza el stock de la tabla INGREDIENTS

```sql
UPDATE INGREDIENTS SET stock = stock + $_POST['quantity'] WHERE ingredient_id = $_POST['ingredient_id']
```

- Te redirige a manager.php

