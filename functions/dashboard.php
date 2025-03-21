<?php
session_start();
require_once('.configDB.php');
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if (!$connection) {
  die("Conexión fallida: " . mysqli_connect_error());
}
$user_id = htmlspecialchars($_SESSION['user_id']);
//Obtener los puntos de un usuario
$stmt = $connection->prepare("SELECT points FROM CUSTOMERS WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$_SESSION['points'] = $row['points'];


// Obtener la imagen del usuario
$stmt = $connection->prepare("SELECT img_src FROM USERS WHERE user_id = ?");
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
    <?php include('./navbar.php');?>
</header>
<body>
<h1>
    Hola
    <?php 
      // Verificar si el usuario está logueado
      if (isset($_SESSION['username']) && isset($_SESSION['user_id'])) {
        $username = $_SESSION['username'];
        echo htmlspecialchars($username);
        $image = "../assets/images/perfiles/".$row['img_src'] ?? '../assets/images/perfiles/default.jpg'; // Imagen por defecto si no hay imagen
      } else {
        echo "Invitado!";
        $image = '../assets/images/perfiles/default.jpg'; // Imagen por defecto para invitados
      }
      ?>
  </h1>
    <div style="text-align: right;">
      <img src="<?php echo htmlspecialchars($image); ?>" alt="ImagenUser" style="width: 50px; height: 50px; border-radius: 50%; vertical-align: middle; margin-left: 10px; position: absolute; top: 50px; right: 10px;">
    </div>
    <p style="margin: 0 auto; text-align: center;">Tienes <?php echo htmlspecialchars($_SESSION['points']);?> puntos</p>
  <div class="carousel">
    <div class="carousel-images">
      <img src="../assets/images/image2.jpg" alt="Imagen 2">
    </div>
  </div>
  <script href="../assets/js/carrousel.js"></script>
</body>
<footer>
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
