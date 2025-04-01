<?php 
session_start();

echo $_POST['idProdSelecCarta'];
include ".configDB.php";

require_once('.configDB.php');
if(isset($_SESSION['conexión'])) {
   $connection = $_SESSION['conexión'];
} else {
   $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD,DB_NAME);
}

/** Obtener id de Producto */
if(isset($_POST['idProdSelecCarta'])) {
   // $idProdSelecCarta = 1
   $idProdSelecCarta= $_POST['idProdSelecCarta'];
}

$cantidad = 1;

/* Obtener filas de producto seleccionado */
$query_producto = "SELECT product_id, product_name, product_price, img_src FROM PRODUCTS WHERE product_id=?";
$stmt = $connection->prepare($query_producto);
$stmt->bind_param("s",$idProdSelecCarta);
$stmt->execute();
$resultado_producto = $stmt->get_result();
echo " $resultado_producto->num_rows";
$producto = $resultado_producto->fetch_assoc();

$product_name = $producto['product_name'];
$product_price = $producto['product_price'];

/* Obtener filas de ingredientes de producto */
$query_ingredientes = "SELECT i.ingredient_id, i.ingredient_name, i.img_src FROM products_ingredients pi JOIN ingredients i ON pi.ingredient_id = i.ingredient_id WHERE pi.product_id =?";
$stmt = $connection->prepare($query_ingredientes);
$stmt->bind_param("s",$idProdSelecCarta);
$stmt->execute();
$resultado_ingredientes = $stmt->get_result();
echo "<br>$resultado_ingredientes->num_rows";

/* Obtener alérgenos de cada ingrediente */
/*$_SESSION['compra'] = [
   [ 'id' => $idProdSelecCarta, 'nombre' => $resultado_producto['product_name'], 'precio' => $resultado_producto['product_price'], 'cantidad' => $cantidad, 'lista_ingredientes' => $lista_ingredientes]
]*/

/** lista_ingredientes debe ser clave-valor, -1 indica quitar */
/** cantidad se puede probar a modificarin situ, si no 1 predeterminado */
?>


<!DOCTYPE html>
<html lang="es">
   <head>
      <meta charset="UTF-8">
      <link rel="icon" href="../assets/images/logo/DKS.ico" type="image/x-icon">

      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="../assets/css/styles.css">
      <title>KEBAB SOCIETY - CARTA</title>

      <script src="../assets/js/producto.js"></script>
   </head>

   <body>
      <?php include './navbar.php'; ?>

<!-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%||                    ||%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% -->
<!-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%||                    ||%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% -->
<!-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%|| CUERPO DE LA CARTA ||%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% -->
<!-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%||                    ||%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% -->
<!-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%||                    ||%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% -->

      <div>
         <?php
            echo "<div title='Contenido principal' style='border:1px dashed blue; height:auto; width:100%; display:flex; justify-content:center; align-items:center;'>";

               /* Contenedor modificacion producto */
               echo "<div title='Contenedor modificacion producto' style='border: 1px solid yellow; height:auto; width:50%;; display:flex; justify-content:center; align-items:center; flex-direction:column;'>";
                  /* Imagen de producto */
                  echo "<div title='prod-image'>";
                     $imgPath = '../assets/images/productos/'.basename($producto["img_src"]);
                     if (file_exists($imgPath)) {
                        echo "<img style='width:300px;height:300px;' src='".$imgPath."' alt='".htmlspecialchars($producto["img_src"])."'>";
                     } else {
                        echo "<p> Imagen no disponible </p>";
                     }
                  echo "</div>";
                  /* Nombre de producto */
                  echo "<p>".basename($producto["product_name"])."</p>";

                  /* Listado de ingredientes */
                  echo "<div title='Contenedor lista ingredientes' style='border:1px dashed green; display:flex; flex-wrap:wrap; justify-content:space-evenly;'>";
                     while ($ingredientes = $resultado_ingredientes->fetch_assoc()) {
                        $imgPath = '../assets/images/ingredientes/'.basename($ingredientes["img_src"]);
                        echo "<div class='ingredient-container' title='Contenedor ingrediente' style='border:1px solid black; display:flex; flex-direction:column; margin:10px;'>";
                        /** Cargar imagen */
                        if (file_exists($imgPath)) {
                              echo "<img style='width:100px;height:100px;' src='".$imgPath."' alt='".htmlspecialchars($ingredientes["img_src"])."'>";
                        } else {
                           echo "<p> Imagen no disponible </p>";
                        }
                        /** Nombre de ingrediente */
                        echo "<p class='ingr-nom'>".$ingredientes["ingredient_name"]."</p>";
                           /** Botones para añadir o quitar ingredientes */
                           echo "<div title='Contenedor botones ingredientes' style='border:1px solid purple; width:100%; height:50px; display:flex; justify-content:center; align-items:center;'>
                              <button class='ingr_btn' style='width:20px;height:20px;background-color:yellow;'>-</button>
                              <span class='ingr-cant' style='padding:10px'>1</span>
                              <button class='ingr_btn' style='width:20px;height:20px;background-color:yellow;'>+</button>
                           </div>";
                        echo "</div>";
                     }
                  echo "</div>";

                  /** Botón para añadir a carrito */
                  ?>
                  <form id="form_add_carrito" action="./add_prod_carrito.php" method="POST">
                     <input type="hidden" name="product_id" value="<?php echo "$idProdSelecCarta"; ?>">
                     <input type="hidden" name="product_name" value="<?php echo "$product_name"; ?>">
                     <input type="hidden" name="product_price" value="<?php echo "$product_price"; ?>">
                     <input type="hidden" id="ingr_list_info" name="ingr_list_info">
                     
                     <button id="add_to_carrito" type="submit" style="width:150px;height:20px;background-color:lightyellow;font-weight:bold;">Añadir a carrito</button>
                  </form>
                  <?php


               echo "</div>";

            echo "<div>";
         ?>
      </div>
<?php include './footer.php'; ?>
   </body>
</html>