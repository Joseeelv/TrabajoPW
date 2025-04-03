<?php
  http_response_code(404);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Error 404 - Página no encontrada</title>
  <link rel="icon" href="./assets/images/logo/DKS.ico" type="image/x-icon">
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
  <header>
    <nav class="navbar">
      <h1>DÖNER KEBAB SOCIETY</h1>
      <a href="../index.php" class="menu-link">Inicio</a>
      <a href="./Menu.php" class="menu-link">Carta</a>
      <a href="./contact.php" class="menu-link">Contacto</a>
      <a href="./login.php" class="menu-link">Iniciar Sesión</a>
      <a href="./register.php" class="menu-link">Registrarse</a>
    </nav>
  </header>

  <main>
    <h2>¡Oops! Página no encontrada (Error 404)</h2>
    <p>La página que estás buscando no existe o se ha movido. Por favor, vuelve a la <a href="./index.php">página de inicio</a>.</p>
  </main>

  <?php include('./functions/footer.php'); ?>
</body>
</html>
