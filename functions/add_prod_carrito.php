<?php 
session_start();

include './.configDB.php';

require_once('./.configDB.php');
if(isset($_SESSION['conexión'])) {
   $connection = $_SESSION['conexión'];
} else {
   $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
}

/** Guardar datos de formulario de producto seleccionado en sesión */
if(isset($_POST['product_id'], $_POST['product_name'], $_POST['product_price'], $_POST['category'])) {
   // Inicializar el carrito si no existe
   if (!isset($_SESSION['compra'])) {
       $_SESSION['compra'] = [];
   }

   // Decodificar la lista de ingredientes de JSON a array
   $lista_ingredientes = isset($_POST['ingr_list_info']) ? json_decode($_POST['ingr_list_info'], true) : [];

   // Si la decodificación falla, aseguramos que sea un array vacío
   if (!is_array($lista_ingredientes)) {
       $lista_ingredientes = [];
   }

   // Agregar el producto al carrito
   $_SESSION['compra'][] = [
      'id' => $_POST['product_id'],
      'nombre' => $_POST['product_name'],
      'precio' => $_POST['product_price'],
      'cantidad' => 1,
      'lista_ingredientes' => $lista_ingredientes,
      'category' => $_POST['category']
   ];
}

header('Location: ./menu.php');
?>
