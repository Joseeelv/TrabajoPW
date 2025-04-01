<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Döner Kebab Society</title>
  <link rel="icon" href="./assets/images/logo/DKS.ico" type="image/x-icon">
  <link rel="stylesheet" href="./assets/css/styles.css">
  <link rel="stylesheet" href="./assets/css/carrusel.css">
  <script src="./assets/js/carrusel.js" defer></script>
</head>

<body>
  <header>
    <nav class="navbar">
      <h1>DÖNER KEBAB SOCIETY</h1>
      <a href="./index.php" class="menu-link">Inicio</a>
      <a href="./functions/menu.php" class="menu-link">Carta</a>
      <a href="./functions/contact.php" class="menu-link">Contacto</a>
      <a href="./functions/login.php" class="menu-link">Iniciar Sesión</a>
      <a href="./functions/register.php" class="menu-link">Registrarse</a>
    </nav>
  </header>

  <main>
    <div class="carousel-container">
      <button class="btn btn-register" onclick="window.location.href='./functions/register.php'">Unete a la
        sociedad</button>
      <div class="carousel">
        <div class="slide"><img src="./assets/images/carrusel/1.png" alt="Kebab 1"></div>
        <div class="slide"><img src="./assets/images/carrusel/2.png" alt="Kebab 2"></div>
        <div class="slide"><img src="./assets/images/carrusel/3.png" alt="Kebab 3"></div>
      </div>
      <button class="btn btn-left" onclick="prevSlide()">&#9664;</button>
      <button class="btn btn-right" onclick="nextSlide()">&#9654;</button>
    </div>
  </main>

  <?php include('./functions/footer.php'); ?>
</body>

</html>