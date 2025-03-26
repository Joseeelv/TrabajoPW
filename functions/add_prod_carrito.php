<?php 
session_start();

echo 'holabuenastardes';
include '../functions/.configDB.php';

require_once('../functions/.configDB.php');
if(isset($_SESSION['conexión'])) {
   $connection = $_SESSION['conexión'];
} else {
   $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD,DB_NAME);
}

/** Guardar datos de formulario de producto seleccionado en sesión */
if(isset($_POST['product_id'], $_POST['product_name'], $_POST['product_price'], $_POST['ingr_list_info'])) {
   $_SESSION['product_id'] = $_POST['product_id'];
   $_SESSION['product_name'] = $_POST['product_name'];
   $_SESSION['product_price'] = $_POST['product_price'];   
   $lista_ingredientes = json_decode($_POST['ingr_list_info'], true);
   $_SESSION['ingr_list_info'] = $lista_ingredientes;
}

header('Location: ../carta.php');
?>