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
<?php include('header.php'); ?>
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
<?php include('footer.php'); ?>
</html>