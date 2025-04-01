<?php
session_start();

$connection = include('./conexion.php');
$user_id = htmlspecialchars($_SESSION['user_id']);
//Obtener los puntos de un usuario
$stmt = $connection->prepare("SELECT points FROM CUSTOMERS WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$_SESSION['points'] = $row['points'];


?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kebab Society</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>
  <header>
    <?php include('./navbar.php'); ?>
  </header>
  <main>
    <h1>
      Hola
      <?php
      // Verificar si el usuario está logueado
      if (isset($_SESSION['username']) && isset($_SESSION['user_id'])) {
        $username = $_SESSION['username'];
        echo htmlspecialchars($username);
        $image = "../assets/images/perfiles/" . $_SESSION['img_src'] ?? '../assets/images/perfiles/default.jpg'; // Imagen por defecto si no hay imagen
      } else {
        echo "Invitado!";
        $image = '../assets/images/perfiles/default.jpg'; // Imagen por defecto para invitados
      }
      ?>
    </h1>
    <div style="text-align: right;">
      <img src="<?php echo htmlspecialchars($image); ?>" alt="ImagenUser"
        style="width: 50px; height: 50px; border-radius: 50%; vertical-align: middle; margin-left: 10px; position: absolute; top: 50px; right: 10px;">
    </div>
    <p style="margin: 0 auto; text-align: center;">Tienes <?php echo htmlspecialchars($_SESSION['points']); ?> puntos</p>
    <div class="carousel">
      <div class="carousel-images">
        <img src="../assets/images/image2.jpg" alt="Imagen 2">
      </div>
    </div>
    <script href="../assets/js/carrousel.js"></script>
  </main>

  <?php include('./footer.php'); ?>
</body>

</html>