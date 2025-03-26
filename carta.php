<?php 

session_start();

echo "holabuenastardes";
include "./functions/.configDB.php";

require_once('./functions/.configDB.php');
if(isset($_SESSION['conexión'])) {
   $connection = $_SESSION['conexión'];
} else {
   $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD,DB_NAME);
}

echo $_SESSION["product_id"];
echo "\n";
echo $_SESSION["product_name"];
echo "\n";
echo $_SESSION["product_price"];
echo "\n";
print_r($_SESSION["ingr_list_info"]); 
?>

<!DOCTYPE html>
<html lang="es">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>KEBAB SOCIETY - CARTA</title>
   </head>

   <body>

<!-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%||                    ||%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% -->
<!-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%||                    ||%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% -->
<!-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%|| CUERPO DE LA CARTA ||%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% -->
<!-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%||                    ||%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% -->
<!-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%||                    ||%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% -->

      <div>
         <?php
            
         ?>
      </div>

   </body>
</html>