# Producto.php

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

2. **Carga de id de producto seleccionado en carta**
   - Se extrae de la sesión una variable que almacena la id de aquel producto que el cliente ha seleccionado en `Menu.php` por el que se ha accedido al archivo actual. 
   - En caso de que esta variable de sesión no esté activa, se guarda el mínimo valor posible del campo, siendo este 1.
   - En ambos casos, se guarda en una variable la cantidad del producto que se va a guardar en sesión. De momento esta variable, al ser fija, es más cómodo guardarla aquí. 

   ```php
   if (isset($_POST['idProdSelecCarta'])) {
      $idProdSelecCarta = $_POST['idProdSelecCarta'];
      $cantidad = 1;
   } else {
      $idProdSelecCarta = 1;
      $cantidad = 1;
   }
   ```

3. **Carga de atributos de producto selecccionado**
   - Se extrae de la BBDD una serie de atributos correspondientes al id de producto seleccionado con información relevante a la compra
   - El resultado se guarda en una variable, de la que se extraen los diferentes valores de varios atributos y se guardan en variables auxiliares para un uso más fácil.

   ```php
   $query_producto = "SELECT category, product_id, product_name, product_price, img_src FROM PRODUCTS WHERE product_id=?";
   $stmt = $connection->prepare($query_producto);
   $stmt->bind_param("s", $idProdSelecCarta);
   $stmt->execute();
   $resultado_producto = $stmt->get_result();
   $producto = $resultado_producto->fetch_assoc();

   $product_name = $producto['product_name'];
   $product_price = $producto['product_price'];
   $category = $producto['category'];
   ```

4. **Carga de atributos de todos los ingredientes del producto selecccionado**
   - Se extrae de la BBDD una serie de atributos correspondientes a todos aquellos ingredientes que estén asociados a la id del producto seleccionado. 
   - El resultado se guarda en una variable de la que se extraen los diferentes valores de los atributos de cada ingrediente.

   ```php
   $query_ingredientes = "SELECT i.ingredient_id, i.ingredient_name, i.img_src FROM products_ingredients pi JOIN ingredients i ON pi.ingredient_id = i.ingredient_id WHERE pi.product_id =?";
   $stmt = $connection->prepare($query_ingredientes);
   $stmt->bind_param("s", $idProdSelecCarta);
   $stmt->execute();
   $resultado_ingredientes = $stmt->get_result();
   ```

5. **Carga de alérgenos de todos los ingredientes del producto selecccionado**
   - Se extrae de la BBDD los diferentes nombres de imágenes de aquellos alérgenos asociados a todos los ingredientes asociados a la id del producto seleccionado. 
   - El resultado se guarda en una variable creada previamente, que servirá de contenedor para todos los nombres de imagen que se obtengan en total de la extracción.

   ```php
   $alergenos = [];

   $query_alergenos_ingredientes = "SELECT DISTINCT a.img_src
                           FROM ingredients_allergens ia 
                           JOIN allergens a ON ia.allergen_id = a.allergen_id 
                           WHERE ia.ingredient_id IN (
                              SELECT ingredient_id 
                              FROM products_ingredients 
                              WHERE product_id = ?
                           )";
   $stmt = $connection->prepare($query_alergenos_ingredientes);
   $stmt->bind_param("s", $idProdSelecCarta);
   $stmt->execute();
   $resultado_alergenos_ingredientes = $stmt->get_result();

   while ($fila = $resultado_alergenos_ingredientes->fetch_assoc()) {
      $alergenos[] = $fila['img_src'];
   }
   ```

6. **Comprobación de alérgenos**
   - Existen una serie de productos que no tienen ingredientes, por lo tanto la lista de alérgenos no se completa.
   - En estos casos, se comprueba en una tabla que relaciona aquellos productos sin ingredientes con sus alérgenos correspondientes, y se extraen los mismos datos que se extraerían de los ingredientes en el caso anterior.

   ```php
   if (empty($alergenos)) {
      $query_alergenos_producto = "SELECT a.img_src
                           FROM PRODUCTS_NO_INGREDIENTS_ALLERGENS pa 
                           JOIN allergens a ON pa.allergen_id = a.allergen_id 
                           WHERE pa.product_id = ?";
      $stmt = $connection->prepare($query_alergenos_producto);
      $stmt->bind_param("s", $idProdSelecCarta);
      $stmt->execute();
      $resultado_alergenos_producto = $stmt->get_result();

      while ($fila = $resultado_alergenos_producto->fetch_assoc()) {
         $alergenos[] = $fila['img_src'];
      }
   }
   ```

7. **Carga en página de imágen del producto**
   - Se crea una variable que guarda la ruta a la imagen cuyo nombre de imagen es el mismo que el almacenado en la variable que guarda el resultado de la query a producto.
   - Se comprueba la existencia de la imagen que corresponde a la ruta y se muestra por pantalla en caso positivo.
   - En caso de no existir, muestra un texto de error.

   ```html
   <div class="prod-image">
      <?php
         $imgPath = '../assets/images/productos/' . basename($producto["img_src"]);
         if (file_exists($imgPath)) {
            echo "<img src='$imgPath' alt='" . htmlspecialchars($producto["img_src"]) . "'>";
         } else {
            echo "<p> Imagen no disponible </p>";
         }
      ?>
   </div>
   ```

8. **Carga en página de nombre y precio de producto**
   - Se hace uso de las variables definidas para mostrar el nombre y precio del producto por pantalla.
   - El uso de la función `htmlspecialchars` comprueba y elimina aquellos caracteres sensibles a html que puedan causar errores.

   ```html
   <p><?php echo htmlspecialchars($product_name); ?></p>
   <p><?php echo htmlspecialchars($product_price); ?> €</p>
   ```

9. **Carga en página de lista de alérgenos**
   - Se recorre la variable definida para mostrar la lista de imágenes de alérgenos del producto y sus ingredientes por pantalla.
   - En cada iteración, se comprueba la existencia del archivo, y muestra en caso de error un texto de error.
   - Si la variable que contiene la lista de alérgenos está vacía, indica que no tiene alérgenos a mostrar, por lo que se informa al usuario con un texto.

   ```html
   <div class="allergens-container">
      <?php if (!empty($alergenos)) { ?>
         <p>Alérgenos:</p>
         <div class="allergens-list">
            <?php foreach ($alergenos as $alergeno) { 
               $imgPath = '../assets/images/alergenos/' . basename($alergeno);
               if (file_exists($imgPath)) {
                  echo "<img id=\"allergen\" src='$imgPath' alt='" . htmlspecialchars($alergeno) . "' class='allergen-img'>";
               } else {
                  echo "<p> Imagen no disponible </p>";
               }
            } ?>
         </div>
      <?php } else { ?>
         <p>No contiene alérgenos.</p>
      <?php } ?>
   </div>
   ```

10. **Inclusión de formulario oculto con lista de información sobre el producto**
   - Creación de un formulario con campos ocultos que guarda los valores de variables con información relevante respecto a su inclusión al apartado de carrito.
   - El valor del campo que corresponde a la lista de ingredientes y sus cantidades es tratada en `producto.js`.
   - La información que es enviada al pulsar el botón de submit se dirige y es tratada en `add_prod_carrito.php` por POST.

   ```html
   <form id="form_add_carrito" action="./add_prod_carrito.php" method="POST">
      <input type="hidden" name="product_id" value="<?php echo $idProdSelecCarta; ?>">
      <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product_name); ?>">
      <input type="hidden" name="product_price" value="<?php echo $product_price; ?>">
      <input type="hidden" id="ingr_list_info" name="ingr_list_info">
      <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
      <button id="add_to_carrito" type="submit">Añadir a carrito</button>
   </form>
   ```

11. **Construcción de manejo de cantidades de ingredientes del producto**
   - Creación de un contenedor dinámico que muestra la lista de ingredientes del producto, complementada con una serie de botones con funciones definidas para modificar la cantidad que el cliente quiere de cada ingrediente.
   - Se realiza comprobación ya descrita en apartados anteriores para incluir las imágenes.
   - Se extrae la información pertinente del ingrediente de la variable que almacena el resultado de la query a la tabla de ingredientes.

   ```html
   <div class="ingredients-container">
      <?php while ($ingredientes = $resultado_ingredientes->fetch_assoc()) { ?>
         <div class="ingredient-container">
            <?php
               $imgPath = '../assets/images/ingredientes/' . basename($ingredientes["img_src"]);
               if (file_exists($imgPath)) {
                  echo "<img src='$imgPath' alt='" . htmlspecialchars($ingredientes["img_src"]) . "'>";
               } else {
                  echo "<p> Imagen no disponible </p>";
               }
            ?>
            <p class="ingr-nombre"> <?php echo htmlspecialchars($ingredientes["ingredient_name"]); ?> </p>
            <div class="ingr-buttons">
               <button class="ingr_btn">-</button>
               <span class="ingr-cant">1</span>
               <button class="ingr_btn">+</button>
               <p class="ingr-id" hidden><?php echo $ingredientes["ingredient_id"]; ?></p>
            </div>
         </div>
      <?php } ?>
   </div>
   ```



# Producto.js

## Estructura y Funcionamiento

1. **Funcionamiento de botones de modificación de cantidad de ingredientes**
   - Se seleccionan todos los botones existentes en el archivo `producto.php`, y a cada instancia de botón se le añade una escucha que espera al click en el elemento
   - Al detectar el click, según el contenido textual del botón (teniendo como posibilidades '+' y '-'), modifica el contenido del elemento textual que se encuentra antes o después del propio botón pulsado.

   ```js
   botones = document.querySelectorAll('.ingr_btn');
   botones.forEach(boton => {
      boton.addEventListener('click', () => {
         if (boton.textContent == '+') {
            spanToMod = boton.previousElementSibling;
            cant_ingr = parseInt(spanToMod.textContent, 10);
            cant_ingr++;
         } else{
            spanToMod = boton.nextElementSibling;
            cant_ingr = parseInt(spanToMod.textContent, 10);
            cant_ingr = cant_ingr - 1;
            cant_ingr = Math.max(0, cant_ingr);
         }
         spanToMod.textContent = cant_ingr;
      })
      
   });
   ```

2. **Envío de información sobre ingredientes y sus cantidades**
   - Se selecciona el botón de envío del formulario con campos ocultos y se le añade una escucha al click.
   - Al detectar el click crea una variable que almacena elementos clave-valor y rellena esta variable con el nombre y cantidad de cada ingrediente, valores que se encuentran en contenedores HTML.
   - Tras llenar la variable, se le da valor al campo oculto del formulario correspondiente a la lista de ingredientes con un JSON que contiene la información almacenada en la variable creada. Tras esto, envía el formulario.

   ```js
   envio = document.getElementById("add_to_carrito");
   envio.addEventListener('click', () => {
      let lista_ingredientes = {};

      document.querySelectorAll(".ingredient-container").forEach(div => {
         let nombre = div.querySelector(".ingr-nom").textContent.trim();
         let cantidad = div.querySelector(".ingr-cant").textContent.trim();
         lista_ingredientes[nombre] = cantidad;
      });

      // Enviar el objeto como JSON en un campo oculto
      document.getElementById("ingr_list_info").value = JSON.stringify(lista_ingredientes);
      document.getElementById("form_add_carrito").submit();
   })
   ```



# Add_prod_carrito.php

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

2. **Guardado de datos del formulario en sesión**
   - En este apartado se profundiza en el guardado de la información procedente del formulario oculto en la sesión de navegador del cliente.

   2.1. **Comprobación de llegada de información**
      - Antes de tratar la información, se comprueba que ciertos campos clave hayan sido definidos en la sesión.

      ```php
      if(isset($_POST['product_id'], $_POST['product_name'], $_POST['product_price'], $_POST['category'])) {
         ...
      }
      ```

   2.2. **Comprobación de existencia de carrito**
      - Se realiza una comprobación de la existencia de una variable definida que represente al carrito, y se define en caso negativo.

      ```php
      if (!isset($_SESSION['compra'])) {
       $_SESSION['compra'] = [];
      }
      ```

   2.3. **Trata de información sobre la lista de ingredientes**
      - La información de esta variable es enviada en formato JSON. La información debe ser decodificada para poder guardarla en una variable legible.
      - En caso de que la lista de ingredientes se encuentre vacía (existen productos que no poseen ingredientes) se asegura que se envíe una lista vacía

      ```php
      $lista_ingredientes = isset($_POST['ingr_list_info']) ? json_decode($_POST['ingr_list_info'], true) : [];

      if (!is_array($lista_ingredientes)) {
         $lista_ingredientes = [];
      }
      ```

   2.4. **Guardado de valores en sesión**
      - Se guarda en la variable definida correspondiente al carrito una lista clave-valor de información correspondiente al producto seleccionado con la personalización de ingredientes y sus cantidades.

      ```php
      $_SESSION['compra'][] = [
         'id' => $_POST['product_id'],
         'nombre' => $_POST['product_name'],
         'precio' => $_POST['product_price'],
         'cantidad' => 1,
         'lista_ingredientes' => $lista_ingredientes,
         'category' => $_POST['category']
      ];
      ```

3. **Redirección del usuario**
   - Se redirige al usuario a la página de `menu.php`, de manera que el manejo de datos es imperceptible al usuario.

   ```php
   header('Location: ./menu.php');
   ```