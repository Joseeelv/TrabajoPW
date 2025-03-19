<?php
session_start();
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
  <?php include('../functions/navbar.php'); ?>
</header>
<body>
  <div class="container">
    <h2>Modificar Perfil</h2>
    <form action="../functions/actualizar_perfil.php" method="POST">
      <div class="form-group">
        <label for="email">Nuevo Email:</label>
        <input type="email" id="email" name="email" placeholder="Nuevo email">
      </div>
      <div class="form-group">
        <label for="password">Nueva Contraseña:</label>
        <input type="password" id="password" name="password" placeholder="Nueva contraseña">
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirmar Contraseña:</label>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar contraseña">
      </div>
      <button type="submit">Actualizar Perfil</button>
    </form>
  </div>
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