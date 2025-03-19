<?php
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
  <h1>Bienvenido a KEBAB!</h1>
  <nav>
    <ul>
      <li><a href="">Inicio</a></li>
      <li><a href="">Carta</a></li>
      <li><a href="">Contacto</a></li>
      <li><a href="../TrabajoPW/functions/register.php">Regístrate</a></li>
      <li><a href="../TrabajoPW/functions/login.php">Inicia Sesión</a></li>
    </ul>
  </nav>
</header>
<body>
<div class="carousel">
  <div class="carousel-images">
    <img src="../assets/images/image1.jpg" alt="Imagen 1">
    <img src="../assets/images/image2.jpg" alt="Imagen 2">
    <img src="../assets/images/image3.jpg" alt="Imagen 3">
  </div>
</div>

<script href="/assets/js/carrousel.js"></script>
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

