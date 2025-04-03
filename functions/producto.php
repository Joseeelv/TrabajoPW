<?php
session_start();

//Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);


$connection = include('./conexion.php');

/** Obtener id de Producto */
$idProdSelecCarta = $_POST['idProdSelecCarta'] ?? null;
if (!$idProdSelecCarta) {
   die("Error: No se recibió un ID de producto.");
}

$cantidad = 1;

/* Obtener datos del producto */
$query_producto = "SELECT category, product_id, product_name, product_price, img_src FROM PRODUCTS WHERE product_id=?";
$stmt = $connection->prepare($query_producto);
$stmt->bind_param("s", $idProdSelecCarta);
$stmt->execute();
$resultado_producto = $stmt->get_result();
$producto = $resultado_producto->fetch_assoc();

if (!$producto) {
   die("Error: Producto no encontrado.");
}

$product_name = $producto['product_name'];
$product_price = $producto['product_price'];
$category = $producto['category'];

/* Verificar si la tabla products_ingredients existe */
$query_check_table = "SHOW TABLES LIKE 'products_ingredients'";
$resultado_check = $connection->query($query_check_table);
$tabla_existe = $resultado_check->num_rows > 0;

$ingredientes = [];
if ($tabla_existe) {
   /* Obtener ingredientes del producto */
   $query_ingredientes = "SELECT i.ingredient_id, i.ingredient_name, i.img_src 
                           FROM PRODUCTS_INGREDIENTS pi 
                           JOIN ingredients i ON pi.ingredient_id = i.ingredient_id 
                           WHERE pi.product_id = ?";
   $stmt = $connection->prepare($query_ingredientes);
   $stmt->bind_param("s", $idProdSelecCarta);
   $stmt->execute();
   $resultado_ingredientes = $stmt->get_result();
   $ingredientes = $resultado_ingredientes->fetch_all(MYSQLI_ASSOC);
}

/* Obtener alérgenos */
$alergenos = [];

// Alérgenos de ingredientes
$query_alergenos_ingredientes = "SELECT DISTINCT a.img_src
                                 FROM INGREDIENTS_ALLERGENS ia 
                                 JOIN ALLERGENS a ON ia.allergen_id = a.allergen_id 
                                 WHERE ia.ingredient_id IN (
                                     SELECT ingredient_id 
                                     FROM PRODUCTS_INGREDIENTS 
                                     WHERE product_id = ?
                                 )";
$stmt = $connection->prepare($query_alergenos_ingredientes);
$stmt->bind_param("s", $idProdSelecCarta);
$stmt->execute();
$resultado_alergenos_ingredientes = $stmt->get_result();

while ($fila = $resultado_alergenos_ingredientes->fetch_assoc()) {
   $alergenos[] = $fila['img_src'];
}

// Alérgenos del producto si no hay en los ingredientes
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
?>

<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="icon" href="../assets/images/logo/DKS.ico" type="image/x-icon">
   <link rel="stylesheet" href="../assets/css/styles.css">
   <link rel="stylesheet" href="../assets/css/producto.css">
   <title>KEBAB SOCIETY - CARTA</title>
   <script src="../assets/js/producto.js"></script>
</head>

<body>
   <?php include './navbar.php'; ?>

   <main>
      <div class="product-container">
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

         <p><?php echo htmlspecialchars($product_name); ?></p>
         <p><?php echo htmlspecialchars($product_price); ?> €</p>

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

         <form id="form_add_carrito" action="./add_prod_carrito.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo $idProdSelecCarta; ?>">
            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product_name); ?>">
            <input type="hidden" name="product_price" value="<?php echo $product_price; ?>">
            <input type="hidden" id="ingr_list_info" name="ingr_list_info">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <button id="add_to_carrito" type="submit">Añadir a carrito</button>
         </form>

         <div class="ingredients-container">
            <?php
            if (!empty($ingredientes)) {
               foreach ($ingredientes as $ingrediente) { ?>
                  <div class="ingredient-container">
                     <?php
                     $imgPath = '../assets/images/ingredientes/' . basename($ingrediente["img_src"]);
                     if (file_exists($imgPath)) {
                        echo "<img src='$imgPath' alt='" . htmlspecialchars($ingrediente["img_src"]) . "'>";
                     } else {
                        echo "<p> Imagen no disponible </p>";
                     }
                     ?>
                     <p class="ingr-nombre"> <?php echo htmlspecialchars($ingrediente["ingredient_name"]); ?> </p>
                     <div class="ingr-buttons">
                        <button class="ingr_btn">-</button>
                        <span class="ingr-cant">1</span>
                        <button class="ingr_btn">+</button>
                        <p class="ingr-id" hidden><?php echo $ingrediente["ingredient_id"]; ?></p>
                     </div>
                  </div>
            <?php }
            } else {
               echo "<p>No hay ingredientes disponibles.</p>";
            }
            ?>
         </div>
      </div>
   </main>

   <?php include './footer.php'; ?>
</body>

</html>