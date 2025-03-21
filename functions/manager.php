<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Manager Panel</title>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
	<link rel="stylesheet" href="../assets/styles.css">
</head>
<header>
  <?php include('../functions/navbar.php'); ?>
</header>
<body>
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