<?php
require_once('.configDB.php');
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if (!$connection) {
  die("Conexión fallida: " . mysqli_connect_error());
}
return $connection;
?>