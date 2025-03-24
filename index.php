<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kebab</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
  <link rel="stylesheet" href="./assets/css/styles.css">
</head>

<body>
  <header>
    <nav class="navbar">
      <h1>Bienvenido a KEBAB!</h1>
      <a href="./index.php" class="menu-link">Inicio</a>
      <a href="" class="menu-link">Carta</a>
      <a href="./functions/contact.php" class="menu-link">Contacto</a>
      <a href="./functions/register.php" class="menu-link">Regístrate</a>
      <a href="./functions/login.php" class="menu-link">Inicia Sesión</a>
    </nav>
  </header>
  <main>
    <div class="carousel">
      <div class="carousel-images">
        <img src="./assets/images/image2.jpg" alt="Imagen 2">
      </div>
    </div>

    <script href="./assets/js/carrousel.js"></script>
  </main>
  <?php include('./functions/footer.php'); ?>
</body>

</html>