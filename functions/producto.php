<?php
session_start();

include ".configDB.php";
require_once('.configDB.php');

if (isset($_SESSION['conexión'])) {
   $connection = $_SESSION['conexión'];
} else {
   $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
}

/** Obtener id de Producto */
if (isset($_POST['idProdSelecCarta'])) {
   $idProdSelecCarta = $_POST['idProdSelecCarta'];
}

$cantidad = 1;

/* Obtener filas de producto seleccionado */
$query_producto = "SELECT category, product_id, product_name, product_price, img_src FROM PRODUCTS WHERE product_id=?";
$stmt = $connection->prepare($query_producto);
$stmt->bind_param("s", $idProdSelecCarta);
$stmt->execute();
$resultado_producto = $stmt->get_result();
$producto = $resultado_producto->fetch_assoc();

$product_name = $producto['product_name'];
$product_price = $producto['product_price'];
$category = $producto['category'];

/* Obtener filas de ingredientes de producto */
$query_ingredientes = "SELECT i.ingredient_id, i.ingredient_name, i.img_src FROM products_ingredients pi JOIN ingredients i ON pi.ingredient_id = i.ingredient_id WHERE pi.product_id =?";
$stmt = $connection->prepare($query_ingredientes);
$stmt->bind_param("s", $idProdSelecCarta);
$stmt->execute();
$resultado_ingredientes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="../assets/css/styles.css">
   <link rel="stylesheet" href="../assets/css/producto.css">
   <title>KEBAB SOCIETY - CARTA</title>
   <script src="../assets/js/producto.js"></script>
</head>

<body>
   <?php include './navbar.php'; ?>

   <div class="main-container">
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
         <form id="form_add_carrito" action="./add_prod_carrito.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo $idProdSelecCarta; ?>">
            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product_name); ?>">
            <input type="hidden" name="product_price" value="<?php echo $product_price; ?>">
            <input type="hidden" id="ingr_list_info" name="ingr_list_info">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <button id="add_to_carrito" type="submit">Añadir a carrito</button>
         </form>
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

      </div>
   </div>

   <?php include './footer.php'; ?>
</body>

</html>