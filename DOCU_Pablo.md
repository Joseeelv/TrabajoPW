# Carrito.php

## Estructura y Funcionamiento

1. **Conexión a la Base de Datos**
   - La conexión con la base de datos se realiza utilizando la configuración almacenada en un archivo `.configDB.php`.
   - Si ya existe una sesión abierta con una conexión válida (`$_SESSION['conexión']`), se reutiliza esta conexión. Si no, se establece una nueva conexión a la base de datos utilizando las credenciales definidas en el archivo de configuración.

   ```php
   if (isset($_SESSION['conexión'])) {
       $connection = $_SESSION['conexión'];
   } else {
       $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
   }
   ```

2. **Carga de Ofertas**
   - Si no se han cargado las ofertas en la sesión (`$_SESSION['ofertas']`), el sistema las consulta en la base de datos.
   - Se obtiene una lista de ofertas que están asociadas a productos (por ejemplo, descuentos en ciertos productos).

   ```php
   if (!isset($_SESSION['ofertas'])) {
       $query = "SELECT OFFERS.offer_text as of_name, OFFERS.discount as discount, PRODUCTS.product_name as nombre, PRODUCTS.img_src as img FROM OFFERS JOIN PRODUCTS ON OFFERS.prod_id = PRODUCTS.product_id";
       $stmt = $connection->prepare($query);
       $stmt->execute();
       $_SESSION['ofertas'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
   }
   ```

3. **Visualización de Productos del Carrito**
   - Si existen productos en la sesión (`$_SESSION['compra']`), se muestran en una lista con sus detalles (nombre, precio, ingredientes adicionales).
   - Para cada producto, se verifica si hay alguna oferta activa que aplique un descuento y se ajusta el precio final.
   - Además, se muestra un resumen del total de la compra hasta el momento.

   ```php
   if (isset($_SESSION['compra'])) {
       foreach ($_SESSION['compra'] as $p) {
           // Calcula y muestra los productos y sus precios con descuento
       }
   }
   ```

4. **Confirmación de Compra**
   - El usuario puede confirmar la compra a través de un formulario que, al ser enviado, genera un registro de la orden en la base de datos.
   - Se actualizan los puntos del cliente basados en el total de la compra (por cada 10 unidades monetarias gastadas, el cliente recibe 100 puntos).
   - Se crea una nueva entrada en la tabla `ORDERS` con el estado de la orden como "Pending".
   - Los productos del carrito se insertan en la tabla `ORDER_ITEMS`, y si los productos son de una categoría específica (como bebidas o postres), el sistema actualiza el stock de los productos.

   ```php
   if (isset($_POST["Confirmar"]) && $_POST["Confirmar"] == "Confirmar" && isset($_SESSION['compra'])) {
       // Actualiza puntos, inserta la orden, y ajusta el stock de productos.
   }
   ```

5. **Manejo de Stock de Ingredientes**
   - Cuando un producto se compra, si tiene ingredientes adicionales (como en productos de categorías distintas a bebidas o postres), el sistema ajusta el stock de cada ingrediente.
   - El stock de ingredientes se disminuye según la cantidad utilizada en la compra.

   ```php
   foreach($p['lista_ingredientes'] as $i) {
       // Actualiza el stock de ingredientes.
   }
   ```

6. **Redirección en caso de error**
   - Si ocurre alguna excepción (por ejemplo, problemas con la base de datos o la ejecución de la consulta), se redirige al usuario a una página de error (`500.php`).

   ```php
   } catch (Exception $e) {
       header("Location: 500.php");
   }
   ```

7. **Formulario de Confirmación de Compra**
   - Se crea un formulario con un botón para que el usuario confirme la compra. Al presionar el botón, se envía la solicitud para procesar la compra y actualizar la base de datos.

   ```php
   echo "";
   echo "Price: " . $v_total . "\t";
   echo "";
   ```

### Manejo de Sesiones

- **`$_SESSION['conexión']`**: Mantiene la conexión a la base de datos si ya está abierta en la sesión.
- **`$_SESSION['ofertas']`**: Guarda las ofertas disponibles que se cargan de la base de datos.
- **`$_SESSION['compra']`**: Contiene los productos añadidos al carrito de compras por el usuario.

# Ofertas.php

## Estructura del Código


1. **Inicio de Sesión y Conexión a la Base de Datos**
El código inicia una sesión utilizando `session_start()` y establece una conexión con la base de datos MySQL mediante `mysqli`. La conexión se almacena en `$_SESSION['connection']` para su reutilización.

```
session_start();
$_SESSION['connection'] = new mysqli("localhost", "root", "", "DB_Kebab");
```

2. **Carga de Ofertas**
Si las ofertas no están cargadas en la sesión (`$_SESSION['ofertas']`), el código consulta la base de datos para obtener información sobre las ofertas disponibles. Los resultados incluyen detalles como el texto de la oferta, el ID, el costo en coronas, el descuento aplicado, el nombre del producto y su imagen.

```
if (!isset($_SESSION['ofertas'])) {
    $query = "SELECT OFFERS.offer_text as of_name, OFFERS.offer_id as id, OFFERS.cost as coronas, OFFERS.discount as discount, PRODUCTS.product_name as nombre, PRODUCTS.img_src as img FROM OFFERS JOIN PRODUCTS ON OFFERS.prod_id = PRODUCTS.product_id";
    $stmt = $_SESSION['connection']->prepare($query);
    $stmt->execute();
    $_SESSION['ofertas'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
```

3. **Visualización de Ofertas**
Las ofertas se muestran dentro de una lista desordenada (``). Para cada oferta:
- Se verifica si el usuario ya ha aceptado la oferta consultando la tabla `CUSTOMERS_OFFERS`.
- Si el usuario activa una oferta (mediante un formulario), se inserta un registro en `CUSTOMERS_OFFERS` con los datos correspondientes.

## Ejemplo del Código:
```
foreach ($_SESSION['ofertas'] as $f) {
    $query = "SELECT * FROM CUSTOMERS_OFFERS WHERE user_id = ? AND offer_id = ?";
    $stmt = $_SESSION['connection']->prepare($query);
    $stmt->bind_param("ii", $_SESSION['user_id'], $f['id']);
    $stmt->execute();
    $_SESSION['Aceptada'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if(isset($_POST['Oferta']) && $_POST['Oferta'] == $f['id'] && empty($_SESSION['Aceptada'])) {
        $query = "INSERT INTO CUSTOMERS_OFFERS(user_id,offer_id,activation_date) values(?,?,?)";
        $stmt = $_SESSION['connection']->prepare($query);
        $fecha = date('Y-m-d');
        $stmt->bind_param("iis", $_SESSION['user_id'], $f['id'], $fecha);
        $stmt->execute();
        $_SESSION['Aceptada'] = True;
    }

    echo "
            
            
            
            ";

            if(!empty($_SESSION['Aceptada'])) {
                echo "Oferta: " . $f["nombre"] . " Precio: " . $f["coronas"] . " Coronas Descuento: " . $f["discount"]
                . "% Activa";
            } else {
                echo "Oferta: " . $f["nombre"] . " Precio: " . $f["coronas"] . " Coronas Descuento: " . $f["discount"]
                . "% No Activa";
            }
}
```

4. **Redirección en Caso de Error**
Si ocurre algún error durante la ejecución (por ejemplo, problemas con la conexión o consultas SQL), se captura mediante un bloque `try-catch` y se redirige al usuario a una página de error (`500.php`).

```
} catch (Exception $e) {
    header("Location: 500.php");
}
```
```markdown

# Menu.php

## Descripción General
Este archivo PHP genera una página web que permite a los usuarios visualizar un menú de productos organizados por categorías. Los usuarios pueden seleccionar una categoría específica para filtrar los productos o ver el menú completo. Además, el código incluye medidas de seguridad para prevenir vulnerabilidades como XSS (Cross-Site Scripting) y utiliza una conexión persistente a la base de datos.

---

## Estructura del Código

1. **Inicio de Sesión y Conexión a la Base de Datos**
El código inicia una sesión utilizando `session_start()` y establece una conexión con la base de datos MySQL mediante `mysqli`. La conexión se almacena en `$_SESSION['connection']` para su reutilización.

```
session_start();
$_SESSION['connection'] = new mysqli("localhost", "root", "", "DB_Kebab");
```

### 2. **Carga de Categorías**
Si las categorías no están cargadas en la sesión (`$_SESSION['categoria']`), el código consulta la base de datos para obtener todas las categorías disponibles en la tabla `PRODUCTS`. Los resultados se agrupan por categoría.

```
if (!isset($_SESSION['categoria'])) {
    $query = "SELECT PRODUCTS.category as cat FROM PRODUCTS group by PRODUCTS.category";
    $stmt = $_SESSION['connection']->prepare($query);
    $stmt->execute();
    $_SESSION['categoria'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
```

3. **Carga del Menú**
El menú se carga dinámicamente según la categoría seleccionada por el usuario. Si no se selecciona ninguna categoría, se muestran todos los productos.

- **Filtrar por Categoría**: Si el usuario selecciona una categoría, se realiza una consulta para obtener los productos que pertenecen a esa categoría.
- **Mostrar Todos los Productos**: Si no se selecciona ninguna categoría, se obtienen todos los productos de la base de datos.

```
if (!isset($_SESSION['menu']) || isset($_POST['category'])) {
    $category = $_POST['category'];
    if ($category != "Ninguna") {
        $query = "SELECT PRODUCTS.product_id as id, PRODUCTS.product_name as nombre, PRODUCTS.img_src as img FROM PRODUCTS where PRODUCTS.category = ?";
        $stmt = $_SESSION['connection']->prepare($query);
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $_SESSION['menu'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $query = "SELECT PRODUCTS.product_id as id, PRODUCTS.product_name as nombre, PRODUCTS.img_src as img FROM PRODUCTS";
        $stmt = $_SESSION['connection']->prepare($query);
        $stmt->execute();
        $_SESSION['menu'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
```

4. **Manejo de Excepciones**
Si ocurre algún error durante la ejecución (por ejemplo, problemas con la conexión o consultas SQL), se captura mediante un bloque `try-catch` y se redirige al usuario a una página de error (`500.php`).

```
} catch (Exception $e) {
    header("Location: 500.php");
}
```

## Formulario para Seleccionar Categoría

```
echo "";
echo "";
echo "Ninguna";
foreach ($_SESSION['categoria'] as $c) {
    echo "" . $c['cat'] . "";
}
echo "";
echo "";
```

## Visualización del Menú

El menú muestra cada producto como un elemento de lista (``). Dependiendo del estado del usuario:
- **Usuario Autenticado**: Los productos incluyen un botón que redirige a `producto.php` con el ID del producto seleccionado.
- **Usuario No Autenticado**: Los productos incluyen un enlace que redirige a la página de inicio de sesión (`login.php`).

Se utilizan funciones como `htmlspecialchars()` para evitar vulnerabilidades XSS al mostrar contenido dinámico.

```
foreach ($_SESSION['menu'] as $f) {
    // Escapar caracteres especiales para prevenir XSS
    $productName = htmlspecialchars($f["nombre"], ENT_QUOTES, 'UTF-8');
    $productImg = htmlspecialchars("../assets/images/productos/" . $f["img"], ENT_QUOTES, 'UTF-8');
    $id = htmlspecialchars($f["id"], ENT_QUOTES, 'UTF-8');

    if (isset($_SESSION['user_id'])) {
        echo "
                
                    
                    
                        
                        " . $productName . "
                    
                
              ";
    } else {
        echo "
                
                " . $productName . "";
    }
}
```
