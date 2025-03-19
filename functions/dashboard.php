<?php
session_start();
require_once('.configDB.php');
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if (!$connection) {
  die("Conexión fallida: " . mysqli_connect_error());
}
//Obtener la imagen del usuario
$stmt = $connection->prepare("SELECT img_src, user_id FROM USERS WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

//Obtener los puntos de un usuario
$stmt = $connection->prepare("SELECT points FROM CUSTOMERS WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Kebab</title>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
	<link rel="stylesheet" href="../assets/styles.css">
</head>
<header>
  <h1>
    Hola
    <?php 
      // Verificar si el usuario está logueado
      if (isset($_SESSION['username'])) {
        echo htmlspecialchars($_SESSION['username']);
      }else{
        echo "Invitado!";
      }
    ?>
    <img src="<?php echo htmlspecialchars($image); ?>" alt="ImagenUser">
  </h1>
  <nav>
    <ul>
      <li><a href="">Inicio</a></li>
      <li><a href="">Carta</a></li>
      <li><a href="../functions/perfil.php">Perfil</a></li>
      <li><a href="../functions/logout.php">Cerrar Sesión</a></li>
    </ul>
    <a href="../functions/puntos.php">Puntos Obtenidos</a>
  </nav>
</header>
<body>
  <div class="carousel">
    <div class="carousel-images">
      <img src="../assets/images/image1.jpeg" alt="Imagen 1">
      <img src="../assets/images/image2.jpg" alt="Imagen 2">
      <img src="../assets/images/image3.jpeg" alt="Imagen 3">
    </div>
  </div>
  <script href="../assets/js/carrousel.js"></script>
</body>
<footer>
  <p>KEBAB - Todos los derechos reservados</p>
  <p><strong>Información Legal:</strong> Este sitio web cumple con las normativas vigentes.</p>
  <p><strong>Ubicación:</strong> Calle Falsa 123, Ciudad Ejemplo, País.</p>
  <p><strong>Copyright:</strong> &copy; <?php echo date("Y"); ?> KEBAB. Todos los derechos reservados.</p>
  <p><strong>Síguenos en:</strong> 
    <a href="https://facebook.com/kebab" target="_blank">Facebook</a> | 
    <a href="https://twitter.com/kebab" target="_blank">Twitter</a> | 
    <a href="https://instagram.com/kebab" target="_blank">Instagram</a>
  </p>
</footer>
</html>
