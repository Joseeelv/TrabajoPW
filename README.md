# Carrito.php

## Overview

Este script PHP se encarga de gestionar un carrito de compras, mostrar los artículos del carrito, aplicar descuentos, calcular el precio total y actualizar los puntos del cliente en la base de datos después de confirmar la compra. También incluye manejo de errores para casos excepcionales.

## Desglose del código

### 1. Verificación de sesión e inicialización del carrito

El script comienza verificando si existe una variable de sesión `$_SESSION['compra']` que contiene los datos del carrito de compras. Si el carrito no está vacío, inicializa el precio total a `0`.

```php
if (isset($_SESSION['compra'])) {
        $v_total = 0;  // Initialize the total amount to 0
        echo "<ul>";  // Start an unordered list to display the cart items
```

- **`$_SESSION['compra']`**: Esta variable de sesión almacena los artículos del carrito de compras, que se iteran para mostrar los detalles del producto.

### 2. Recorrido del carrito y aplicación de descuentos

El script luego recorre los artículos del carrito (`$_SESSION['compra']`) y aplica cualquier descuento disponible almacenado en `$_SESSION["Ofertas"]` si la oferta coincide con el producto.

```php
// Loop through the shopping cart (stored in $_SESSION['compra'])
foreach ($_SESSION['compra'] as $p) {
    // Loop through the available offers (stored in $_SESSION["Ofertas"])
    foreach ($_SESSION["Ofertas"] as $f) {
        // Check if the offer matches the product in the cart
        if ($f["nombre"] == $p["nombre"]) {
            $descuento = $f["discount"];  // Apply the discount from the offer
        }
    }
    // Display product name, price, and discount in the list
    echo "<li>" . $p["nombre"] . " " . $p["precio"] * $descuento ."</li>";

    // Add the price of the product to the total amount
    $v_total += $p["precio"] * $descuento;
}
echo "</ul>";  // End the unordered list
```

- **`$descuento`**: Esta variable contiene el valor del descuento para cada producto si se encuentra una oferta coincidente.
- **`$v_total`**: El monto total se actualiza sumando el precio de cada producto en el carrito.

### 3. Manejo de carrito vacío

Si no hay artículos en el carrito, se muestra una lista desordenada vacía (``).

```php
} else {
    // If the shopping cart is empty, display an empty list
    echo "<ul></ul>";
};
```

### 4. Formulario para confirmar la compra

Se genera un formulario que permite al usuario confirmar la compra. Se muestra el precio total y el usuario puede hacer clic en un botón "Confirmar" para enviar el formulario.

```php
// Create a form to confirm the purchase
echo "<form action=\"Carrito.php\" method=\"POST\">";
echo "Price: " . $v_total . "<input type=\"submit\" name=\"Confirmar\" value=1/>";  // Display the total price and submit button
echo "</form>";
```

- **Botón `Confirmar`**: El usuario confirma la compra haciendo clic en el botón de envío, lo que desencadena la actualización de puntos y limpia el carrito.

### 5. Actualización de puntos del cliente en la base de datos

Si se envía el formulario y el usuario confirma la compra (`$_POST["Confirmar"] == 1`), el script actualiza los puntos del cliente en la base de datos según el precio total del carrito. Los puntos se calculan como el 10% del precio total.

```php
if ($_POST["Confirmar"] == 1) {
    // Create a new database connection
    $conn = new mysqli("localhost", "root", "");
    
    // Prepare a query to update the customer's points based on the total purchase
    $query = "UPDATE CUSTOMERS SET points = points + " . ($v_total / 10) * 100 . " WHERE user_id = " . $_SESSION["USER_ID"];
    
    // Execute the query to update the points in the database
    $conn->query($query);
    
    // Clear the shopping cart in the session after the purchase is confirmed
    $_SESSION['compra'] = null;
}
```

- **Cálculo de puntos**: Los puntos se calculan como `($v_total / 10) * 100`, lo que significa que el 10% del precio total se convierte en puntos.
- **Consulta a la base de datos**: La consulta actualiza los puntos en la tabla `CUSTOMERS` donde el `user_id` coincide con el ID de usuario de la sesión.

### 6. Manejo de errores

Si ocurre alguna excepción durante el proceso, el script las captura y redirige al usuario a la página de error apropiada.

```php
} catch (Exception $e) {
    // If a D_Error exception is thrown, redirect to the 500 error page
    header("Location: 500.php");
}
```

- **Manejo de excepciones**: El script utiliza un bloque `try-catch` para manejar excepciones y redirigir a una página de error `500.php` en caso de error.

# OFERTAS.php

This PHP script connects to a MySQL database, retrieves product information with discounts, and displays it as an unordered list on a web page. Here's a breakdown of the code:

## Database Connection

```php
$conn = new mysqli("localhost", "root", "");
```

This line establishes a connection to a MySQL database using the mysqli class. It connects to:

- Host: localhost
- Username: root
- Password: (empty string)

> Note: For security reasons, it's recommended to use a dedicated user with limited privileges instead of root, and always include a password.

## SQL Query

```php
$query = "SELECT OFFERS.discount as d, PRODUCTS.product_name as n, PRODUCTS.img_src as img FROM OFFERS JOIN PRODUCTS ON OFFERS.prod_id = PRODUCTS.product_id";
```

This SQL query:

- Joins the OFFERS and PRODUCTS tables
- Selects three fields: discount, product name, and image source
- Uses aliases (d, n, img) for easier referencing in PHP

## Query Execution

```php
$consulta = $conn->query($query);
```

Executes the SQL query and stores the result in the $consulta variable.

## Displaying Results

```php
echo "<ul>";
    while($f = mysqli_fetch_assoc($consulta)){
        echo "<li><a header=\"producto.php\"><img src=". $f["img"] ." alt=\"\"></a>". $f["n"] ." ". $f["d"] ."</li>";
    }
    echo "</ul>";
```

This section:

1. Starts an unordered list (``)
2. Loops through each row of the result set
3. For each product, creates a list item (``) containing:
   - A link (Corrected from header to href)
   - An image of the product
   - The product name
   - The discount
4. Closes the unordered list

---