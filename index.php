<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kebab</title>
  <link rel="stylesheet" href="./assets/css/styles.css">
  <link rel="stylesheet" href="./assets/css/carrusel.css">
  <script src="./assets/js/carrusel.js"></script>
</head>

<body>
  <header>
    <nav class="navbar">
      <h1>Bienvenido a KEBAB!</h1>
      <a href="./index.php" class="menu-link">Inicio</a>
      <a href="" class="menu-link">Carta</a>
      <a href="./functions/contact.php" class="menu-link">Contacto</a>
      <a href="./functions/login.php" class="menu-link">Iniciar Sesión</a>
      <a href="./functions/register.php" class="menu-link">Registrarse</a>
    </nav>
  </header>
  <main>
    <div class="carousel-container">
      <div class="carousel">
        <div class="slide"><img src="./assets/images/first_slide.png" alt="Kebab 1"></div>
        <div class="slide"><img src="./assets/img/kebab2.jpg" alt="Kebab 2"></div>
        <div class="slide"><img src="./assets/img/kebab3.jpg" alt="Kebab 3"></div>
      </div>
      <button class="btn btn-left" onclick="prevSlide()">&#9664;</button>
      <button class="btn btn-right" onclick="nextSlide()">&#9654;</button>
    </div>
  </main>

  <?php include('./functions/footer.php'); ?>
</body>

</html>