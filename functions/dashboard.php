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
  <link rel="icon" href="../assets/images/logo/DKS.ico" type="image/x-icon">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kebab Society</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/carrusel.css">
  <script src="../assets/js/carrusel.js" defer></script>
</head>

<body>
  <header>
    <?php include('./navbar.php'); ?>
  </header>
    <main>
      <div class="carousel-container">
        <div class="carousel">
          <div class="slide"><img src="../assets/images/carrusel/1.png" alt="Kebab 1"></div>
          <div class="slide"><img src="../assets/images/carrusel/2.png" alt="Kebab 2"></div>
          <div class="slide"><img src="../assets/images/carrusel/3.png" alt="Kebab 3"></div>
        </div>
        <button class="btn btn-left" onclick="prevSlide()">&#9664;</button>
        <button class="btn btn-right" onclick="nextSlide()">&#9654;</button>
      </div>
    </main>
    <?php include('./footer.php'); ?>

  </body>

  </html>